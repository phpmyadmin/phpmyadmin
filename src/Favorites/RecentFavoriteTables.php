<?php
/**
 * Recent and Favorite table list handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Favorites;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_key_exists;
use function array_pop;
use function array_unique;
use function array_unshift;
use function array_values;
use function count;
use function in_array;
use function is_string;
use function json_decode;
use function json_encode;
use function max;
use function md5;
use function ucfirst;

use const SORT_REGULAR;

/**
 * Handles the recently used and favorite tables.
 *
 * @TODO Change the release version in table pma_recent
 * (#recent in documentation)
 */
class RecentFavoriteTables
{
    /** @var RecentFavoriteTable[] */
    private array $tables = [];

    /**
     * RecentFavoriteTable instances.
     *
     * @var array<string,RecentFavoriteTables>
     */
    private static array $instances = [];

    /** @psalm-param int<0, max> $serverId */
    private function __construct(
        public Template $template,
        private readonly TableType $tableType,
        private readonly int $serverId,
        private readonly DatabaseInterface $dbi,
        private readonly Relation $relation,
        private readonly DbTableExists $dbTableExists,
    ) {
        // Code search hint: recentTables
        // Code search hint: favoriteTables
        if (! isset($_SESSION['tmpval'][$this->tableType->value . 'Tables'][$this->serverId])) {
            $_SESSION['tmpval'][$this->tableType->value . 'Tables'][$this->serverId] = $this->getPmaTable() !== null
                ? $this->getFromDb()
                : [];
        }

        foreach ($_SESSION['tmpval'][$this->tableType->value . 'Tables'][$this->serverId] as $table) {
            $this->tables[] = RecentFavoriteTable::fromArray($table);
        }
    }

    public function __destruct()
    {
        $_SESSION['tmpval'][$this->tableType->value . 'Tables'][$this->serverId] = [];
        foreach ($this->tables as $table) {
            $_SESSION['tmpval'][$this->tableType->value . 'Tables'][$this->serverId][] = $table->toArray();
        }
    }

    /**
     * Returns class instance.
     */
    public static function getInstance(TableType $type): RecentFavoriteTables
    {
        if (! array_key_exists($type->value, self::$instances)) {
            $template = new Template();
            $dbi = DatabaseInterface::getInstance();
            self::$instances[$type->value] = new RecentFavoriteTables(
                $template,
                $type,
                Current::$server,
                $dbi,
                new Relation($dbi),
                new DbTableExists($dbi),
            );
        }

        return self::$instances[$type->value];
    }

    /**
     * Returns the recent/favorite tables array
     *
     * @return RecentFavoriteTable[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Returns recently used tables or favorite from phpMyAdmin database.
     *
     * @return array{db:string, table:string}[]
     */
    private function getFromDb(): array
    {
        // Read from phpMyAdmin database, if recent tables is not in session
        $sqlQuery = 'SELECT `tables` FROM ' . $this->getPmaTable()
            . ' WHERE `username` = '
            . $this->dbi->quoteString(Config::getInstance()->selectedServer['user'], ConnectionType::ControlUser);

        $result = $this->dbi->tryQueryAsControlUser($sqlQuery);
        if ($result !== false) {
            $value = $result->fetchValue();
            if (is_string($value)) {
                return json_decode($value, true);
            }
        }

        return [];
    }

    /**
     * Save recent/favorite tables into phpMyAdmin database.
     *
     * @return true|Message
     */
    private function saveToDb(): bool|Message
    {
        $username = Config::getInstance()->selectedServer['user'];
        $sqlQuery = ' REPLACE INTO ' . $this->getPmaTable() . ' (`username`, `tables`)'
            . ' VALUES (' . $this->dbi->quoteString($username) . ', '
            . $this->dbi->quoteString(json_encode($this->tables)) . ')';

        $success = $this->dbi->tryQuery($sqlQuery, ConnectionType::ControlUser);

        if ($success === false) {
            $message = Message::error(match ($this->tableType) {
                TableType::Recent => __('Could not save recent table!'),
                TableType::Favorite => __('Could not save favorite table!'),
            });

            $message->addMessage(
                Message::rawError($this->dbi->getError(ConnectionType::ControlUser)),
                '<br><br>',
            );

            return $message;
        }

        return true;
    }

    /**
     * Trim recent/favorite table according to the
     * NumRecentTables/NumFavoriteTables configuration.
     */
    private function trim(): void
    {
        $max = max(
            Config::getInstance()->settings['Num' . ucfirst($this->tableType->value) . 'Tables'],
            0,
        );

        while (count($this->tables) > $max) {
            array_pop($this->tables);
        }
    }

    /**
     * Return HTML ul.
     */
    public function getHtmlList(): string
    {
        if ($this->tables !== []) {
            if ($this->tableType === TableType::Recent) {
                $tables = [];
                foreach ($this->tables as $table) {
                    $tables[] = $table->toArray();
                }

                return $this->template->render('recent_favorite_table_recent', ['tables' => $tables]);
            }

            $tables = [];
            foreach ($this->tables as $table) {
                $removeParameters = [
                    'db' => $table->db->getName(),
                    'favorite_table' => $table->table->getName(),
                    'ajax_request' => true,
                    'remove_favorite' => true,
                ];
                $tableParameters = [
                    'db' => $table->db->getName(),
                    'table' => $table->table->getName(),
                    'md5' => md5($table->db . '.' . $table->table),
                ];

                $tables[] = ['remove_parameters' => $removeParameters, 'table_parameters' => $tableParameters];
            }

            return $this->template->render('recent_favorite_table_favorite', ['tables' => $tables]);
        }

        return $this->template->render('recent_favorite_table_no_tables', [
            'is_recent' => $this->tableType === TableType::Recent,
        ]);
    }

    public function getHtml(): string
    {
        $html = '<div class="drop_list">';
        if ($this->tableType === TableType::Recent) {
            $html .= '<button title="' . __('Recent tables')
                . '" class="drop_button btn btn-sm btn-outline-secondary">'
                . __('Recent') . '</button><ul id="pma_recent_list">';
        } else {
            $html .= '<button title="' . __('Favorite tables')
                . '" class="drop_button btn btn-sm btn-outline-secondary">'
                . __('Favorites') . '</button><ul id="pma_favorite_list">';
        }

        $html .= $this->getHtmlList();
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Add recently used or favorite tables.
     *
     * @return true|Message True if success, Message if not
     */
    public function add(RecentFavoriteTable $newTable): bool|Message
    {
        if (! $this->dbTableExists->hasTable($newTable->db, $newTable->table)) {
            return true;
        }

        // add only if this is new table
        if (! isset($this->tables[0]) || $this->tables[0] != $newTable) {
            array_unshift($this->tables, $newTable);
            $this->tables = array_values(array_unique($this->tables, SORT_REGULAR));
            $this->trim();
            if ($this->getPmaTable() !== null) {
                return $this->saveToDb();
            }
        }

        return true;
    }

    /**
     * Removes recent/favorite tables that don't exist.
     *
     * @return bool|Message True if invalid and removed, False if not invalid,
     * Message if error while removing
     */
    public function removeIfInvalid(RecentFavoriteTable $tableToRemove): bool|Message
    {
        foreach ($this->tables as $table) {
            if (
                $table->db->getName() !== $tableToRemove->db->getName()
                || $table->table->getName() !== $tableToRemove->table->getName()
            ) {
                continue;
            }

            if (! $this->dbTableExists->hasTable($table->db, $table->table)) {
                return $this->remove($tableToRemove);
            }
        }

        return false;
    }

    /**
     * Remove favorite tables.
     *
     * @return true|Message True if success, Message if not
     */
    public function remove(RecentFavoriteTable $tableToRemove): bool|Message
    {
        foreach ($this->tables as $key => $table) {
            if (
                $table->db->getName() !== $tableToRemove->db->getName()
                || $table->table->getName() !== $tableToRemove->table->getName()
            ) {
                continue;
            }

            unset($this->tables[$key]);
        }

        if ($this->getPmaTable() !== null) {
            return $this->saveToDb();
        }

        return true;
    }

    /**
     * Function to check if a table is already in favorite list.
     */
    public function contains(RecentFavoriteTable $currentTable): bool
    {
        // When looking for the value we are looking for a similar object with
        // the same public properties, not the same instance. The in_array must be loose comparison.
        return in_array($currentTable, $this->tables, false);
    }

    /**
     * Generate Html for sync Favorite tables anchor. (from localStorage to pmadb)
     */
    public function getHtmlSyncFavoriteTables(): string
    {
        $retval = '';
        if (Current::$server === 0) {
            return '';
        }

        $relationParameters = $this->relation->getRelationParameters();
        // Not to show this once list is synchronized.
        if (
            $relationParameters->favoriteTablesFeature !== null
            && ! isset($_SESSION['tmpval']['favorites_synced'][Current::$server])
        ) {
            $url = Url::getFromRoute('/database/structure/favorite-table', [
                'ajax_request' => true,
                'favorite_table' => true,
                'sync_favorite_tables' => true,
            ]);
            $retval = '<a class="hide" id="sync_favorite_tables"';
            $retval .= ' href="' . $url . '"></a>';
        }

        return $retval;
    }

    /**
     * Generate Html to update recent tables.
     */
    public static function getHtmlUpdateRecentTables(): string
    {
        return '<a class="hide" id="update_recent_tables" href="'
                    . Url::getFromRoute('/recent-table', ['ajax_request' => true, 'recent_table' => true])
                . '"></a>';
    }

    /**
     * Return the name of the configuration storage table
     *
     * @return string|null pma table name
     */
    private function getPmaTable(): string|null
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($this->tableType === TableType::Recent && $relationParameters->recentlyUsedTablesFeature !== null) {
            return Util::backquote($relationParameters->recentlyUsedTablesFeature->database)
                . '.' . Util::backquote($relationParameters->recentlyUsedTablesFeature->recent);
        }

        if ($this->tableType === TableType::Favorite && $relationParameters->favoriteTablesFeature !== null) {
            return Util::backquote($relationParameters->favoriteTablesFeature->database)
                . '.' . Util::backquote($relationParameters->favoriteTablesFeature->favorite);
        }

        return null;
    }
}
