<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\Util;

use function __;
use function in_array;

/**
 * Represents a columns node in the navigation tree
 */
class NodeTable extends NodeDatabaseChild
{
    /**
     * For the second IMG tag, used when rendering the node.
     */
    public Icon|null $secondIcon = null;

    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $icon = $this->addIcon(
            $this->config->settings['NavigationTreeDefaultTabTable'],
            ['db' => null, 'table' => null],
        );
        if ($icon !== null) {
            $this->icon = $icon;
        }

        $this->secondIcon = $this->addIcon(
            $this->config->settings['NavigationTreeDefaultTabTable2'],
            ['db' => null, 'table' => null],
        );

        $this->link = new Link(
            Util::getTitleForTarget($this->config->settings['DefaultTabTable']),
            $this->config->settings['DefaultTabTable'],
            ['pos' => 0, 'db' => null, 'table' => null],
        );
        $this->classes = 'nav_node_table';
        $this->urlParamName = 'table';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('columns' or 'indexes')
     * @param string $searchClause A string used to filter the results of the query
     */
    public function getPresence(UserPrivileges $userPrivileges, string $type = '', string $searchClause = ''): int
    {
        $retval = 0;
        $db = $this->getRealParent()->realName;
        $table = $this->realName;
        $dbi = DatabaseInterface::getInstance();
        switch ($type) {
            case 'columns':
                if (! $this->config->selectedServer['DisableIS']) {
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
                    $query .= 'WHERE `TABLE_NAME`=' . $dbi->quoteString($table) . ' ';
                    $query .= 'AND `TABLE_SCHEMA`=' . $dbi->quoteString($db);
                    $retval = (int) $dbi->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $table = Util::backquote($table);
                    $query = 'SHOW COLUMNS FROM ' . $table . ' FROM ' . $db;
                    $retval = $this->queryAndGetNumRows($query);
                }

                break;
            case 'indexes':
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW INDEXES FROM ' . $table . ' FROM ' . $db;
                $retval = $this->queryAndGetNumRows($query);
                break;
            case 'triggers':
                if (! $this->config->selectedServer['DisableIS']) {
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
                    $query .= 'WHERE `EVENT_OBJECT_SCHEMA` '
                    . Util::getCollateForIS() . '=' . $dbi->quoteString($db) . ' ';
                    $query .= 'AND `EVENT_OBJECT_TABLE` '
                    . Util::getCollateForIS() . '=' . $dbi->quoteString($table);
                    $retval = (int) $dbi->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $query = 'SHOW TRIGGERS FROM ' . $db . ' WHERE `Table` = ' . $dbi->quoteString($table);
                    $retval = $this->queryAndGetNumRows($query);
                }

                break;
            default:
                break;
        }

        return $retval;
    }

    /**
     * Returns the names of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return mixed[]
     */
    public function getData(
        UserPrivileges $userPrivileges,
        RelationParameters $relationParameters,
        string $type,
        int $pos,
        string $searchClause = '',
    ): array {
        $maxItems = $this->config->settings['MaxNavigationItems'];
        $retval = [];
        $db = $this->getRealParent()->realName;
        $table = $this->realName;
        $dbi = DatabaseInterface::getInstance();
        switch ($type) {
            case 'columns':
                if (! $this->config->selectedServer['DisableIS']) {
                    $query = 'SELECT `COLUMN_NAME` AS `name` ';
                    $query .= ',`COLUMN_KEY` AS `key` ';
                    $query .= ',`DATA_TYPE` AS `type` ';
                    $query .= ',`COLUMN_DEFAULT` AS `default` ';
                    $query .= ",IF (`IS_NULLABLE` = 'NO', '', 'nullable') AS `nullable` ";
                    $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
                    $query .= 'WHERE `TABLE_NAME`=' . $dbi->quoteString($table) . ' ';
                    $query .= 'AND `TABLE_SCHEMA`=' . $dbi->quoteString($db) . ' ';
                    $query .= 'ORDER BY `COLUMN_NAME` ASC ';
                    $query .= 'LIMIT ' . $pos . ', ' . $maxItems;
                    $retval = $dbi->fetchResultSimple($query);
                    break;
                }

                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW COLUMNS FROM ' . $table . ' FROM ' . $db;
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    break;
                }

                $count = 0;
                if ($handle->seek($pos)) {
                    while ($arr = $handle->fetchAssoc()) {
                        if ($count >= $maxItems) {
                            break;
                        }

                        $retval[] = [
                            'name' => $arr['Field'],
                            'key' => $arr['Key'],
                            'type' => Util::extractColumnSpec($arr['Type'])['type'],
                            'default' => $arr['Default'],
                            'nullable' => $arr['Null'] === 'NO' ? '' : 'nullable',
                        ];
                        $count++;
                    }
                }

                break;
            case 'indexes':
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW INDEXES FROM ' . $table . ' FROM ' . $db;
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    break;
                }

                $count = 0;
                foreach ($handle as $arr) {
                    if (in_array($arr['Key_name'], $retval)) {
                        continue;
                    }

                    if ($pos <= 0 && $count < $maxItems) {
                        $retval[] = $arr['Key_name'];
                        $count++;
                    }

                    $pos--;
                }

                break;
            case 'triggers':
                if (! $this->config->selectedServer['DisableIS']) {
                    $query = 'SELECT `TRIGGER_NAME` AS `name` ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
                    $query .= 'WHERE `EVENT_OBJECT_SCHEMA` '
                    . Util::getCollateForIS() . '=' . $dbi->quoteString($db) . ' ';
                    $query .= 'AND `EVENT_OBJECT_TABLE` '
                    . Util::getCollateForIS() . '=' . $dbi->quoteString($table) . ' ';
                    $query .= 'ORDER BY `TRIGGER_NAME` ASC ';
                    $query .= 'LIMIT ' . $pos . ', ' . $maxItems;
                    $retval = $dbi->fetchSingleColumn($query);
                    break;
                }

                $db = Util::backquote($db);
                $query = 'SHOW TRIGGERS FROM ' . $db . ' WHERE `Table` = ' . $dbi->quoteString($table);
                $handle = $dbi->tryQuery($query);
                if ($handle === false) {
                    break;
                }

                $count = 0;
                if ($handle->seek($pos)) {
                    while ($arr = $handle->fetchAssoc()) {
                        if ($count >= $maxItems) {
                            break;
                        }

                        $retval[] = $arr['Trigger'];
                        $count++;
                    }
                }

                break;
            default:
                break;
        }

        return $retval;
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType(): string
    {
        return 'table';
    }

    /**
     * Add an icon to navigation tree
     *
     * @param '/table/sql'|'/table/search'|'/table/change'|'/sql'|'/table/structure'|'' $page   Page name to redirect
     * @param array<string, mixed>                                                      $params
     */
    private function addIcon(string $page, array $params): Icon|null
    {
        return match ($page) {
            '/table/structure' => new Icon('b_props', __('Structure'), $page, $params),
            '/table/search' => new Icon('b_search', __('Search'), $page, $params),
            '/table/change' => new Icon('b_insrow', __('Insert'), $page, $params),
            '/table/sql' => new Icon('b_sql', __('SQL'), $page, $params),
            '/sql' => new Icon('b_browse', __('Browse'), $page, $params),
            default => null,
        };
    }
}
