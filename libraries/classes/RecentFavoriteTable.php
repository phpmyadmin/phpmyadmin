<?php
/**
 * Recent and Favorite table list handling
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\Generator;
use const SORT_REGULAR;
use function array_key_exists;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_unshift;
use function count;
use function htmlspecialchars;
use function json_decode;
use function json_encode;
use function max;
use function md5;
use function ucfirst;

/**
 * Handles the recently used and favorite tables.
 *
 * @TODO Change the release version in table pma_recent
 * (#recent in documentation)
 */
class RecentFavoriteTable
{
    /**
     * Reference to session variable containing recently used or favorite tables.
     *
     * @access private
     * @var array
     */
    private $tables;

    /**
     * Defines type of action, Favorite or Recent table.
     *
     * @access private
     * @var string
     */
    private $tableType;

    /**
     * RecentFavoriteTable instances.
     *
     * @access private
     * @var array
     */
    private static $instances = [];

    /** @var Relation */
    private $relation;

    /**
     * Creates a new instance of RecentFavoriteTable
     *
     * @param string $type the table type
     *
     * @access private
     */
    private function __construct($type)
    {
        global $dbi;

        $this->relation = new Relation($dbi);
        $this->tableType = $type;
        $server_id = $GLOBALS['server'];
        if (! isset($_SESSION['tmpval'][$this->tableType . 'Tables'][$server_id])
        ) {
            $_SESSION['tmpval'][$this->tableType . 'Tables'][$server_id]
                = $this->getPmaTable() ? $this->getFromDb() : [];
        }
        $this->tables
            =& $_SESSION['tmpval'][$this->tableType . 'Tables'][$server_id];
    }

    /**
     * Returns class instance.
     *
     * @param string $type the table type
     *
     * @return RecentFavoriteTable
     */
    public static function getInstance($type)
    {
        if (! array_key_exists($type, self::$instances)) {
            self::$instances[$type] = new RecentFavoriteTable($type);
        }

        return self::$instances[$type];
    }

    /**
     * Returns the recent/favorite tables array
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Returns recently used tables or favorite from phpMyAdmin database.
     *
     * @return array
     */
    public function getFromDb()
    {
        global $dbi;

        // Read from phpMyAdmin database, if recent tables is not in session
        $sql_query
            = ' SELECT `tables` FROM ' . $this->getPmaTable() .
            " WHERE `username` = '" . $dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "'";

        $return = [];
        $result = $this->relation->queryAsControlUser($sql_query, false);
        if ($result) {
            $row = $dbi->fetchArray($result);
            if (isset($row[0])) {
                $return = json_decode($row[0], true);
            }
        }

        return $return;
    }

    /**
     * Save recent/favorite tables into phpMyAdmin database.
     *
     * @return true|Message
     */
    public function saveToDb()
    {
        global $dbi;

        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query
            = ' REPLACE INTO ' . $this->getPmaTable() . ' (`username`, `tables`)' .
                " VALUES ('" . $dbi->escapeString($username) . "', '"
                . $dbi->escapeString(
                    json_encode($this->tables)
                ) . "')";

        $success = $dbi->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);

        if (! $success) {
            $error_msg = '';
            switch ($this->tableType) {
                case 'recent':
                    $error_msg = __('Could not save recent table!');
                    break;

                case 'favorite':
                    $error_msg = __('Could not save favorite table!');
                    break;
            }
            $message = Message::error($error_msg);
            $message->addMessage(
                Message::rawError(
                    $dbi->getError(DatabaseInterface::CONNECT_CONTROL)
                ),
                '<br><br>'
            );

            return $message;
        }

        return true;
    }

    /**
     * Trim recent.favorite table according to the
     * NumRecentTables/NumFavoriteTables configuration.
     *
     * @return bool True if trimming occurred
     */
    public function trim()
    {
        $max = max(
            $GLOBALS['cfg']['Num' . ucfirst($this->tableType) . 'Tables'],
            0
        );
        $trimming_occurred = count($this->tables) > $max;
        while (count($this->tables) > $max) {
            array_pop($this->tables);
        }

        return $trimming_occurred;
    }

    /**
     * Return HTML ul.
     *
     * @return string
     */
    public function getHtmlList()
    {
        $html = '';
        if (count($this->tables)) {
            if ($this->tableType === 'recent') {
                foreach ($this->tables as $table) {
                    $html .= '<li class="warp_link">';
                    $recent_url = Url::getFromRoute('/table/recent-favorite', [
                        'db' => $table['db'],
                        'table' => $table['table'],
                    ]);
                    $html .= '<a href="' . $recent_url . '">`'
                          . htmlspecialchars($table['db']) . '`.`'
                          . htmlspecialchars($table['table']) . '`</a>';
                    $html .= '</li>';
                }
            } else {
                foreach ($this->tables as $table) {
                    $html .= '<li class="warp_link">';

                    $html .= '<a class="ajax favorite_table_anchor" ';
                    $fav_rm_url = Url::getFromRoute('/database/structure/favorite-table', [
                        'db' => $table['db'],
                        'ajax_request' => true,
                        'favorite_table' => $table['table'],
                        'remove_favorite' => true,
                    ]);
                    $html .= 'href="' . $fav_rm_url
                        . '" title="' . __('Remove from Favorites')
                        . '" data-favtargetn="'
                        . md5($table['db'] . '.' . $table['table'])
                        . '" >'
                        . Generator::getIcon('b_favorite')
                        . '</a>';

                    $table_url = Url::getFromRoute('/table/recent-favorite', [
                        'db' => $table['db'],
                        'table' => $table['table'],
                    ]);
                    $html .= '<a href="' . $table_url . '">`'
                        . htmlspecialchars($table['db']) . '`.`'
                        . htmlspecialchars($table['table']) . '`</a>';
                    $html .= '</li>';
                }
            }
        } else {
            $html .= '<li class="warp_link">'
                  . ($this->tableType === 'recent'
                    ? __('There are no recent tables.')
                    : __('There are no favorite tables.'))
                  . '</li>';
        }

        return $html;
    }

    /**
     * Return HTML.
     *
     * @return string
     */
    public function getHtml()
    {
        $html  = '<div class="drop_list">';
        if ($this->tableType === 'recent') {
            $html .= '<button title="' . __('Recent tables')
                . '" class="drop_button btn">'
                . __('Recent') . '</button><ul id="pma_recent_list">';
        } else {
            $html .= '<button title="' . __('Favorite tables')
                . '" class="drop_button btn">'
                . __('Favorites') . '</button><ul id="pma_favorite_list">';
        }
        $html .= $this->getHtmlList();
        $html .= '</ul></div>';

        return $html;
    }

    /**
     * Add recently used or favorite tables.
     *
     * @param string $db    database name where the table is located
     * @param string $table table name
     *
     * @return true|Message True if success, Message if not
     */
    public function add($db, $table)
    {
        global $dbi;

        // If table does not exist, do not add._getPmaTable()
        if (! $dbi->getColumns($db, $table)) {
            return true;
        }

        $table_arr = [];
        $table_arr['db'] = $db;
        $table_arr['table'] = $table;

        // add only if this is new table
        if (! isset($this->tables[0]) || $this->tables[0] != $table_arr) {
            array_unshift($this->tables, $table_arr);
            $this->tables = array_merge(array_unique($this->tables, SORT_REGULAR));
            $this->trim();
            if ($this->getPmaTable()) {
                return $this->saveToDb();
            }
        }

        return true;
    }

    /**
     * Removes recent/favorite tables that don't exist.
     *
     * @param string $db    database
     * @param string $table table
     *
     * @return bool|Message True if invalid and removed, False if not invalid,
     * Message if error while removing
     */
    public function removeIfInvalid($db, $table)
    {
        global $dbi;

        foreach ($this->tables as $tbl) {
            if ($tbl['db'] != $db || $tbl['table'] != $table) {
                continue;
            }

            // TODO Figure out a better way to find the existence of a table
            if (! $dbi->getColumns($tbl['db'], $tbl['table'])) {
                return $this->remove($tbl['db'], $tbl['table']);
            }
        }

        return false;
    }

    /**
     * Remove favorite tables.
     *
     * @param string $db    database name where the table is located
     * @param string $table table name
     *
     * @return true|Message True if success, Message if not
     */
    public function remove($db, $table)
    {
        foreach ($this->tables as $key => $value) {
            if ($value['db'] != $db || $value['table'] != $table) {
                continue;
            }

            unset($this->tables[$key]);
        }
        if ($this->getPmaTable()) {
            return $this->saveToDb();
        }

        return true;
    }

    /**
     * Generate Html for sync Favorite tables anchor. (from localStorage to pmadb)
     *
     * @return string
     */
    public function getHtmlSyncFavoriteTables()
    {
        $retval = '';
        $server_id = $GLOBALS['server'];
        if ($server_id == 0) {
            return '';
        }
        $cfgRelation = $this->relation->getRelationsParam();
        // Not to show this once list is synchronized.
        if ($cfgRelation['favoritework'] && ! isset($_SESSION['tmpval']['favorites_synced'][$server_id])) {
            $url = Url::getFromRoute('/database/structure/favorite-table', [
                'ajax_request' => true,
                'favorite_table' => true,
                'sync_favorite_tables' => true,
            ]);
            $retval  = '<a class="hide" id="sync_favorite_tables"';
            $retval .= ' href="' . $url . '"></a>';
        }

        return $retval;
    }

    /**
     * Generate Html to update recent tables.
     *
     * @return string html
     */
    public static function getHtmlUpdateRecentTables()
    {
        $retval = '<a class="hide" id="update_recent_tables" href="';
        $retval .= Url::getFromRoute('/recent-table', [
            'ajax_request' => true,
            'recent_table' => true,
        ]);
        $retval .= '"></a>';

        return $retval;
    }

    /**
     * Return the name of the configuration storage table
     *
     * @return string|null pma table name
     */
    private function getPmaTable(): ?string
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['recentwork']) {
            return null;
        }

        if (! empty($cfgRelation['db'])
            && ! empty($cfgRelation[$this->tableType])
        ) {
            return Util::backquote($cfgRelation['db']) . '.'
                . Util::backquote($cfgRelation[$this->tableType]);
        }

        return null;
    }
}
