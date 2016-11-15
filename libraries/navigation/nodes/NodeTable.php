<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
namespace PMA\libraries\navigation\nodes;

use PMA\libraries\Util;

/**
 * Represents a columns node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeTable extends NodeDatabaseChild
{
    /**
     * Initialises the class
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = array();
        $this->_addIcon(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['NavigationTreeDefaultTabTable'],
                'table'
            )
        );
        $this->_addIcon(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'],
                'table'
            )
        );
        $title = Util::getTitleForTarget(
            $GLOBALS['cfg']['DefaultTabTable']
        );
        $this->title = $title;

        $script_name = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabTable'],
            'table'
        );
        $this->links = array(
            'text'  => $script_name
                . '?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s'
                . '&amp;pos=0',
            'icon'  => array(
                Util::getScriptNameForOption(
                    $GLOBALS['cfg']['NavigationTreeDefaultTabTable'],
                    'table'
                )
                . '?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
                Util::getScriptNameForOption(
                    $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'],
                    'table'
                )
                . '?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
            ),
            'title' => $this->title,
        );
        $this->classes = 'table';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PMA\libraries\navigation\nodes\NodeDatabase
     * and PMA\libraries\navigation\nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('columns' or 'indexes')
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return int
     */
    public function getPresence($type = '', $searchClause = '')
    {
        $retval = 0;
        $db = $this->realParent()->real_name;
        $table = $this->real_name;
        switch ($type) {
        case 'columns':
            if (!$GLOBALS['cfg']['Server']['DisableIS']) {
                $db = $GLOBALS['dbi']->escapeString($db);
                $table = $GLOBALS['dbi']->escapeString($table);
                $query = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`COLUMNS` ";
                $query .= "WHERE `TABLE_NAME`='$table' ";
                $query .= "AND `TABLE_SCHEMA`='$db'";
                $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            } else {
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = "SHOW COLUMNS FROM $table FROM $db";
                $retval = (int)$GLOBALS['dbi']->numRows(
                    $GLOBALS['dbi']->tryQuery($query)
                );
            }
            break;
        case 'indexes':
            $db = Util::backquote($db);
            $table = Util::backquote($table);
            $query = "SHOW INDEXES FROM $table FROM $db";
            $retval = (int)$GLOBALS['dbi']->numRows(
                $GLOBALS['dbi']->tryQuery($query)
            );
            break;
        case 'triggers':
            if (!$GLOBALS['cfg']['Server']['DisableIS']) {
                $db = $GLOBALS['dbi']->escapeString($db);
                $table = $GLOBALS['dbi']->escapeString($table);
                $query = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TRIGGERS` ";
                $query .= "WHERE `EVENT_OBJECT_SCHEMA` "
                    . Util::getCollateForIS() . "='$db' ";
                $query .= "AND `EVENT_OBJECT_TABLE` "
                    . Util::getCollateForIS() . "='$table'";
                $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            } else {
                $db = Util::backquote($db);
                $table = $GLOBALS['dbi']->escapeString($table);
                $query = "SHOW TRIGGERS FROM $db WHERE `Table` = '$table'";
                $retval = (int)$GLOBALS['dbi']->numRows(
                    $GLOBALS['dbi']->tryQuery($query)
                );
            }
            break;
        default:
            break;
        }

        return $retval;
    }

    /**
     * Returns the names of children of type $type present inside this container
     * This method is overridden by the PMA\libraries\navigation\nodes\NodeDatabase
     * and PMA\libraries\navigation\nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    public function getData($type, $pos, $searchClause = '')
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = array();
        $db = $this->realParent()->real_name;
        $table = $this->real_name;
        switch ($type) {
        case 'columns':
            if (!$GLOBALS['cfg']['Server']['DisableIS']) {
                $db = $GLOBALS['dbi']->escapeString($db);
                $table = $GLOBALS['dbi']->escapeString($table);
                $query = "SELECT `COLUMN_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`COLUMNS` ";
                $query .= "WHERE `TABLE_NAME`='$table' ";
                $query .= "AND `TABLE_SCHEMA`='$db' ";
                $query .= "ORDER BY `COLUMN_NAME` ASC ";
                $query .= "LIMIT " . intval($pos) . ", $maxItems";
                $retval = $GLOBALS['dbi']->fetchResult($query);
                break;
            }

            $db = Util::backquote($db);
            $table = Util::backquote($table);
            $query = "SHOW COLUMNS FROM $table FROM $db";
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle === false) {
                break;
            }

            $count = 0;
            if ($GLOBALS['dbi']->dataSeek($handle, $pos)) {
                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($count < $maxItems) {
                        $retval[] = $arr['Field'];
                        $count++;
                    } else {
                        break;
                    }
                }
            }
            break;
        case 'indexes':
            $db = Util::backquote($db);
            $table = Util::backquote($table);
            $query = "SHOW INDEXES FROM $table FROM $db";
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle === false) {
                break;
            }

            $count = 0;
            while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
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
            if (!$GLOBALS['cfg']['Server']['DisableIS']) {
                $db = $GLOBALS['dbi']->escapeString($db);
                $table = $GLOBALS['dbi']->escapeString($table);
                $query = "SELECT `TRIGGER_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TRIGGERS` ";
                $query .= "WHERE `EVENT_OBJECT_SCHEMA` "
                    . Util::getCollateForIS() . "='$db' ";
                $query .= "AND `EVENT_OBJECT_TABLE` "
                    . Util::getCollateForIS() . "='$table' ";
                $query .= "ORDER BY `TRIGGER_NAME` ASC ";
                $query .= "LIMIT " . intval($pos) . ", $maxItems";
                $retval = $GLOBALS['dbi']->fetchResult($query);
                break;
            }

            $db = Util::backquote($db);
            $table = $GLOBALS['dbi']->escapeString($table);
            $query = "SHOW TRIGGERS FROM $db WHERE `Table` = '$table'";
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle === false) {
                break;
            }

            $count = 0;
            if ($GLOBALS['dbi']->dataSeek($handle, $pos)) {
                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($count < $maxItems) {
                        $retval[] = $arr['Trigger'];
                        $count++;
                    } else {
                        break;
                    }
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
    protected function getItemType()
    {
        return 'table';
    }

    /**
     * Add an icon to navigation tree
     *
     * @param string $page Page name to redirect
     *
     * @return void
     */
    private function _addIcon($page)
    {
        if (empty($page)) {
            return;
        }

        switch ($page) {
        case 'tbl_structure.php':
            $this->icon[] = Util::getImage(
                'b_props.png',
                __('Structure')
            );
            break;
        case 'tbl_select.php':
            $this->icon[] = Util::getImage(
                'b_search.png',
                __('Search')
            );
            break;
        case 'tbl_change.php':
            $this->icon[] = Util::getImage(
                'b_insrow.png',
                __('Insert')
            );
            break;
        case 'tbl_sql.php':
            $this->icon[] = Util::getImage('b_sql.png', __('SQL'));
            break;
        case 'sql.php':
            $this->icon[] = Util::getImage(
                'b_browse.png',
                __('Browse')
            );
            break;
        }
    }
}

