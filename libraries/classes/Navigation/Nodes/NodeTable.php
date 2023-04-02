<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Url;
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
     *
     * @var array<string, string>|null
     * @psalm-var array{image: string, title: string}|null
     */
    public array|null $secondIcon = null;

    /**
     * Initialises the class
     *
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     */
    public function __construct(string $name, int $type = Node::OBJECT, bool $isGroup = false)
    {
        parent::__construct($name, $type, $isGroup);

        $icon = $this->addIcon(
            Util::getScriptNameForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable'], 'table'),
        );
        if ($icon !== null) {
            $this->icon = $icon;
        }

        $this->secondIcon = $this->addIcon(
            Util::getScriptNameForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable2'], 'table'),
        );
        $title = (string) Util::getTitleForTarget($GLOBALS['cfg']['DefaultTabTable']);
        $this->title = $title;

        $this->links = [
            'text' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['DefaultTabTable'], 'table'),
                'params' => ['pos' => 0, 'db' => null, 'table' => null],
            ],
            'icon' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable'], 'table'),
                'params' => ['db' => null, 'table' => null],
            ],
            'second_icon' => [
                'route' => Util::getUrlForOption($GLOBALS['cfg']['NavigationTreeDefaultTabTable2'], 'table'),
                'params' => ['db' => null, 'table' => null],
            ],
            'title' => $this->title,
        ];
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
    public function getPresence(string $type = '', string $searchClause = ''): int
    {
        $retval = 0;
        $db = $this->realParent()->realName;
        $table = $this->realName;
        switch ($type) {
            case 'columns':
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $GLOBALS['dbi']->escapeString($db);
                    $table = $GLOBALS['dbi']->escapeString($table);
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
                    $query .= "WHERE `TABLE_NAME`='" . $table . "' ";
                    $query .= "AND `TABLE_SCHEMA`='" . $db . "'";
                    $retval = (int) $GLOBALS['dbi']->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $table = Util::backquote($table);
                    $query = 'SHOW COLUMNS FROM ' . $table . ' FROM ' . $db;
                    $retval = (int) $GLOBALS['dbi']->queryAndGetNumRows($query);
                }

                break;
            case 'indexes':
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW INDEXES FROM ' . $table . ' FROM ' . $db;
                $retval = (int) $GLOBALS['dbi']->queryAndGetNumRows($query);
                break;
            case 'triggers':
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $GLOBALS['dbi']->escapeString($db);
                    $table = $GLOBALS['dbi']->escapeString($table);
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
                    $query .= 'WHERE `EVENT_OBJECT_SCHEMA` '
                    . Util::getCollateForIS() . "='" . $db . "' ";
                    $query .= 'AND `EVENT_OBJECT_TABLE` '
                    . Util::getCollateForIS() . "='" . $table . "'";
                    $retval = (int) $GLOBALS['dbi']->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $table = $GLOBALS['dbi']->escapeString($table);
                    $query = 'SHOW TRIGGERS FROM ' . $db . " WHERE `Table` = '" . $table . "'";
                    $retval = (int) $GLOBALS['dbi']->queryAndGetNumRows($query);
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
    public function getData(string $type, int $pos, string $searchClause = ''): array
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = [];
        $db = $this->realParent()->realName;
        $table = $this->realName;
        switch ($type) {
            case 'columns':
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $GLOBALS['dbi']->escapeString($db);
                    $table = $GLOBALS['dbi']->escapeString($table);
                    $query = 'SELECT `COLUMN_NAME` AS `name` ';
                    $query .= ',`COLUMN_KEY` AS `key` ';
                    $query .= ',`DATA_TYPE` AS `type` ';
                    $query .= ',`COLUMN_DEFAULT` AS `default` ';
                    $query .= ",IF (`IS_NULLABLE` = 'NO', '', 'nullable') AS `nullable` ";
                    $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
                    $query .= "WHERE `TABLE_NAME`='" . $table . "' ";
                    $query .= "AND `TABLE_SCHEMA`='" . $db . "' ";
                    $query .= 'ORDER BY `COLUMN_NAME` ASC ';
                    $query .= 'LIMIT ' . $pos . ', ' . $maxItems;
                    $retval = $GLOBALS['dbi']->fetchResult($query);
                    break;
                }

                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW COLUMNS FROM ' . $table . ' FROM ' . $db;
                $handle = $GLOBALS['dbi']->tryQuery($query);
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
                            'nullable' => ($arr['Null'] === 'NO' ? '' : 'nullable'),
                        ];
                        $count++;
                    }
                }

                break;
            case 'indexes':
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW INDEXES FROM ' . $table . ' FROM ' . $db;
                $handle = $GLOBALS['dbi']->tryQuery($query);
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
                if (! $GLOBALS['cfg']['Server']['DisableIS']) {
                    $db = $GLOBALS['dbi']->escapeString($db);
                    $table = $GLOBALS['dbi']->escapeString($table);
                    $query = 'SELECT `TRIGGER_NAME` AS `name` ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
                    $query .= 'WHERE `EVENT_OBJECT_SCHEMA` '
                    . Util::getCollateForIS() . "='" . $db . "' ";
                    $query .= 'AND `EVENT_OBJECT_TABLE` '
                    . Util::getCollateForIS() . "='" . $table . "' ";
                    $query .= 'ORDER BY `TRIGGER_NAME` ASC ';
                    $query .= 'LIMIT ' . $pos . ', ' . $maxItems;
                    $retval = $GLOBALS['dbi']->fetchResult($query);
                    break;
                }

                $db = Util::backquote($db);
                $table = $GLOBALS['dbi']->escapeString($table);
                $query = 'SHOW TRIGGERS FROM ' . $db . " WHERE `Table` = '" . $table . "'";
                $handle = $GLOBALS['dbi']->tryQuery($query);
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
     * @param string $page Page name to redirect
     *
     * @return array<string, string>|null
     * @psalm-return array{image: string, title: string}|null
     */
    private function addIcon(string $page): array|null
    {
        return match ($page) {
            Url::getFromRoute('/table/structure') => ['image' => 'b_props', 'title' => __('Structure')],
            Url::getFromRoute('/table/search') => ['image' => 'b_search', 'title' => __('Search')],
            Url::getFromRoute('/table/change') => ['image' => 'b_insrow', 'title' => __('Insert')],
            Url::getFromRoute('/table/sql') => ['image' => 'b_sql', 'title' => __('SQL')],
            Url::getFromRoute('/sql') => ['image' => 'b_browse', 'title' => __('Browse')],
            default => null,
        };
    }
}
