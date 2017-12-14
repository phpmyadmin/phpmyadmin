<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
namespace PMA\libraries\navigation\nodes;

use PMA\libraries\Util;
use PMA\libraries\URL;

/**
 * Represents a database node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeDatabase extends Node
{
    /**
     * The number of hidden items in this database
     *
     * @var int
     */
    protected $hiddenCount = 0;

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
        $this->icon = Util::getImage(
            's_db.png',
            __('Database operations')
        );

        $script_name = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabDatabase'],
            'database'
        );
        $this->links = array(
            'text'  => $script_name
                . '?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;token=' . $_SESSION[' PMA_token '],
            'icon'  => 'db_operations.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;token=' . $_SESSION[' PMA_token '],
            'title' => __('Structure'),
        );
        $this->classes = 'database';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PMA\libraries\navigation\nodes\NodeDatabase
     * and PMA\libraries\navigation\nodes\NodeTable classes
     *
     * @param string  $type         The type of item we are looking for
     *                              ('tables', 'views', etc)
     * @param string  $searchClause A string used to filter the results of
     *                              the query
     * @param boolean $singleItem   Whether to get presence of a single known
     *                              item or false in none
     *
     * @return int
     */
    public function getPresence($type = '', $searchClause = '', $singleItem = false)
    {
        $retval = 0;
        switch ($type) {
        case 'tables':
            $retval = $this->_getTableCount($searchClause, $singleItem);
            break;
        case 'views':
            $retval = $this->_getViewCount($searchClause, $singleItem);
            break;
        case 'procedures':
            $retval = $this->_getProcedureCount($searchClause, $singleItem);
            break;
        case 'functions':
            $retval = $this->_getFunctionCount($searchClause, $singleItem);
            break;
        case 'events':
            $retval = $this->_getEventCount($searchClause, $singleItem);
            break;
        default:
            break;
        }

        return $retval;
    }

    /**
     * Returns the number of tables or views present inside this database
     *
     * @param string  $which        tables|views
     * @param string  $searchClause A string used to filter the results of
     *                              the query
     * @param boolean $singleItem   Whether to get presence of a single known
     *                              item or false in none
     *
     * @return int
     */
    private function _getTableOrViewCount($which, $searchClause, $singleItem)
    {
        $db = $this->real_name;
        if ($which == 'tables') {
            $condition = '=';
        } else {
            $condition = '!=';
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db     = $GLOBALS['dbi']->escapeString($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$db' ";
            $query .= "AND `TABLE_TYPE`" . $condition . "'BASE TABLE' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'TABLE_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $query = "SHOW FULL TABLES FROM ";
            $query .= Util::backquote($db);
            $query .= " WHERE `Table_type`" . $condition . "'BASE TABLE' ";
            if (!empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'Tables_in_' . $db
                );
            }
            $retval = $GLOBALS['dbi']->numRows(
                $GLOBALS['dbi']->tryQuery($query)
            );
        }

        return $retval;
    }

    /**
     * Returns the number of tables present inside this database
     *
     * @param string  $searchClause A string used to filter the results of
     *                              the query
     * @param boolean $singleItem   Whether to get presence of a single known
     *                              item or false in none
     *
     * @return int
     */
    private function _getTableCount($searchClause, $singleItem)
    {
        return $this->_getTableOrViewCount(
            'tables',
            $searchClause,
            $singleItem
        );
    }

    /**
     * Returns the number of views present inside this database
     *
     * @param string  $searchClause A string used to filter the results of
     *                              the query
     * @param boolean $singleItem   Whether to get presence of a single known
     *                              item or false in none
     *
     * @return int
     */
    private function _getViewCount($searchClause, $singleItem)
    {
        return $this->_getTableOrViewCount(
            'views',
            $searchClause,
            $singleItem
        );
    }

    /**
     * Returns the number of procedures present inside this database
     *
     * @param string  $searchClause A string used to filter the results of
     *                              the query
     * @param boolean $singleItem   Whether to get presence of a single known
     *                              item or false in none
     *
     * @return int
     */
    private function _getProcedureCount($searchClause, $singleItem)
    {
        $db = $this->real_name;
        if (!$GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . Util::getCollateForIS() . "='$db'";
            $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
            if (!empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'ROUTINE_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SHOW PROCEDURE STATUS WHERE `Db`='$db' ";
            if (!empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'Name'
                );
            }
            $retval = $GLOBALS['dbi']->numRows(
                $GLOBALS['dbi']->tryQuery($query)
            );
        }

        return $retval;
    }

    /**
     * Returns the number of functions present inside this database
     *
     * @param string  $searchClause A string used to filter the results of
     *                              the query
     * @param boolean $singleItem   Whether to get presence of a single known
     *                              item or false in none
     *
     * @return int
     */
    private function _getFunctionCount($searchClause, $singleItem)
    {
        $db = $this->real_name;
        if (!$GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . Util::getCollateForIS() . "='$db' ";
            $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
            if (!empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'ROUTINE_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SHOW FUNCTION STATUS WHERE `Db`='$db' ";
            if (!empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'Name'
                );
            }
            $retval = $GLOBALS['dbi']->numRows(
                $GLOBALS['dbi']->tryQuery($query)
            );
        }

        return $retval;
    }

    /**
     * Returns the number of events present inside this database
     *
     * @param string  $searchClause A string used to filter the results of
     *                              the query
     * @param boolean $singleItem   Whether to get presence of a single known
     *                              item or false in none
     *
     * @return int
     */
    private function _getEventCount($searchClause, $singleItem)
    {
        $db = $this->real_name;
        if (!$GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA` "
                . Util::getCollateForIS() . "='$db' ";
            if (!empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'EVENT_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $db = Util::backquote($db);
            $query = "SHOW EVENTS FROM $db ";
            if (!empty($searchClause)) {
                $query .= "WHERE " . $this->_getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'Name'
                );
            }
            $retval = $GLOBALS['dbi']->numRows(
                $GLOBALS['dbi']->tryQuery($query)
            );
        }

        return $retval;
    }

    /**
     * Returns the WHERE clause for searching inside a database
     *
     * @param string  $searchClause A string used to filter the results of the query
     * @param boolean $singleItem   Whether to get presence of a single known item
     * @param string  $columnName   Name of the column in the result set to match
     *
     * @return string WHERE clause for searching
     */
    private function _getWhereClauseForSearch(
        $searchClause,
        $singleItem,
        $columnName
    ) {
        $query = '';
        if ($singleItem) {
            $query .= Util::backquote($columnName) . " = ";
            $query .= "'" . $GLOBALS['dbi']->escapeString($searchClause) . "'";
        } else {
            $query .= Util::backquote($columnName) . " LIKE ";
            $query .= "'%" . $GLOBALS['dbi']->escapeString($searchClause)
                . "%'";
        }

        return $query;
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
        $retval = array();
        switch ($type) {
        case 'tables':
            $retval = $this->_getTables($pos, $searchClause);
            break;
        case 'views':
            $retval = $this->_getViews($pos, $searchClause);
            break;
        case 'procedures':
            $retval = $this->_getProcedures($pos, $searchClause);
            break;
        case 'functions':
            $retval = $this->_getFunctions($pos, $searchClause);
            break;
        case 'events':
            $retval = $this->_getEvents($pos, $searchClause);
            break;
        default:
            break;
        }

        // Remove hidden items so that they are not displayed in navigation tree
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['navwork']) {
            $hiddenItems = $this->getHiddenItems(substr($type, 0, -1));
            foreach ($retval as $key => $item) {
                if (in_array($item, $hiddenItems)) {
                    unset($retval[$key]);
                }
            }
        }

        return $retval;
    }

    /**
     * Return list of hidden items of given type
     *
     * @param string $type The type of items we are looking for
     *                     ('table', 'function', 'group', etc.)
     *
     * @return array Array containing hidden items of given type
     */
    public function getHiddenItems($type)
    {
        $db = $this->real_name;
        $cfgRelation = PMA_getRelationsParam();
        if (empty($cfgRelation['navigationhiding'])) {
            return array();
        }
        $navTable = Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['navigationhiding']);
        $sqlQuery = "SELECT `item_name` FROM " . $navTable
            . " WHERE `username`='" . $cfgRelation['user'] . "'"
            . " AND `item_type`='" . $type
            . "'" . " AND `db_name`='" . $GLOBALS['dbi']->escapeString($db)
            . "'";
        $result = PMA_queryAsControlUser($sqlQuery, false);
        $hiddenItems = array();
        if ($result) {
            while ($row = $GLOBALS['dbi']->fetchArray($result)) {
                $hiddenItems[] = $row[0];
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        return $hiddenItems;
    }

    /**
     * Returns the list of tables or views inside this database
     *
     * @param string $which        tables|views
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function _getTablesOrViews($which, $pos, $searchClause)
    {
        if ($which == 'tables') {
            $condition = '=';
        } else {
            $condition = '!=';
        }
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval   = array();
        $db       = $this->real_name;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $GLOBALS['dbi']->escapeString($db);
            $query  = "SELECT `TABLE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$escdDb' ";
            $query .= "AND `TABLE_TYPE`" . $condition . "'BASE TABLE' ";
            if (! empty($searchClause)) {
                $query .= "AND `TABLE_NAME` LIKE '%";
                $query .= $GLOBALS['dbi']->escapeString($searchClause);
                $query .= "%'";
            }
            $query .= "ORDER BY `TABLE_NAME` ASC ";
            $query .= "LIMIT " . intval($pos) . ", $maxItems";
            $retval = $GLOBALS['dbi']->fetchResult($query);
        } else {
            $query = " SHOW FULL TABLES FROM ";
            $query .= Util::backquote($db);
            $query .= " WHERE `Table_type`" . $condition . "'BASE TABLE' ";
            if (!empty($searchClause)) {
                $query .= "AND " . Util::backquote(
                    "Tables_in_" . $db
                );
                $query .= " LIKE '%" . $GLOBALS['dbi']->escapeString(
                    $searchClause
                );
                $query .= "%'";
            }
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle !== false) {
                $count = 0;
                if ($GLOBALS['dbi']->dataSeek($handle, $pos)) {
                    while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                        if ($count < $maxItems) {
                            $retval[] = $arr[0];
                            $count++;
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        return $retval;
    }

    /**
     * Returns the list of tables inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function _getTables($pos, $searchClause)
    {
        return $this->_getTablesOrViews('tables', $pos, $searchClause);
    }

    /**
     * Returns the list of views inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function _getViews($pos, $searchClause)
    {
        return $this->_getTablesOrViews('views', $pos, $searchClause);
    }

    /**
     * Returns the list of procedures or functions inside this database
     *
     * @param string $routineType  PROCEDURE|FUNCTION
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function _getRoutines($routineType, $pos, $searchClause)
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = array();
        $db = $this->real_name;
        if (!$GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT `ROUTINE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . Util::getCollateForIS() . "='$escdDb'";
            $query .= "AND `ROUTINE_TYPE`='" . $routineType . "' ";
            if (!empty($searchClause)) {
                $query .= "AND `ROUTINE_NAME` LIKE '%";
                $query .= $GLOBALS['dbi']->escapeString($searchClause);
                $query .= "%'";
            }
            $query .= "ORDER BY `ROUTINE_NAME` ASC ";
            $query .= "LIMIT " . intval($pos) . ", $maxItems";
            $retval = $GLOBALS['dbi']->fetchResult($query);
        } else {
            $escdDb = $GLOBALS['dbi']->escapeString($db);
            $query = "SHOW " . $routineType . " STATUS WHERE `Db`='$escdDb' ";
            if (!empty($searchClause)) {
                $query .= "AND `Name` LIKE '%";
                $query .= $GLOBALS['dbi']->escapeString($searchClause);
                $query .= "%'";
            }
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle !== false) {
                $count = 0;
                if ($GLOBALS['dbi']->dataSeek($handle, $pos)) {
                    while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                        if ($count < $maxItems) {
                            $retval[] = $arr['Name'];
                            $count++;
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        return $retval;
    }

    /**
     * Returns the list of procedures inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function _getProcedures($pos, $searchClause)
    {
        return $this->_getRoutines('PROCEDURE', $pos, $searchClause);
    }

    /**
     * Returns the list of functions inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function _getFunctions($pos, $searchClause)
    {
        return $this->_getRoutines('FUNCTION', $pos, $searchClause);
    }

    /**
     * Returns the list of events inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function _getEvents($pos, $searchClause)
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = array();
        $db = $this->real_name;
        if (!$GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT `EVENT_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA` "
                . Util::getCollateForIS() . "='$escdDb' ";
            if (!empty($searchClause)) {
                $query .= "AND `EVENT_NAME` LIKE '%";
                $query .= $GLOBALS['dbi']->escapeString($searchClause);
                $query .= "%'";
            }
            $query .= "ORDER BY `EVENT_NAME` ASC ";
            $query .= "LIMIT " . intval($pos) . ", $maxItems";
            $retval = $GLOBALS['dbi']->fetchResult($query);
        } else {
            $escdDb = Util::backquote($db);
            $query = "SHOW EVENTS FROM $escdDb ";
            if (!empty($searchClause)) {
                $query .= "WHERE `Name` LIKE '%";
                $query .= $GLOBALS['dbi']->escapeString($searchClause);
                $query .= "%'";
            }
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle !== false) {
                $count = 0;
                if ($GLOBALS['dbi']->dataSeek($handle, $pos)) {
                    while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                        if ($count < $maxItems) {
                            $retval[] = $arr['Name'];
                            $count++;
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        return $retval;
    }

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return String HTML for control buttons
     */
    public function getHtmlForControlButtons()
    {
        $ret = '';
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['navwork']) {
            if ($this->hiddenCount > 0) {
                $params = array(
                    'showUnhideDialog' => true,
                    'dbName' => $this->real_name,
                );
                $ret = '<span class="dbItemControls">'
                    . '<a href="navigation.php'
                    . URL::getCommon($params) . '"'
                    . ' class="showUnhide ajax">'
                    . Util::getImage(
                        'show.png',
                        __('Show hidden items')
                    )
                    . '</a></span>';
            }
        }

        return $ret;
    }

    /**
     * Sets the number of hidden items in this database
     *
     * @param int $count hidden item count
     *
     * @return void
     */
    public function setHiddenCount($count)
    {
        $this->hiddenCount = $count;
    }

    /**
     * Returns the number of hidden items in this database
     *
     * @return int hidden item count
     */
    public function getHiddenCount()
    {
        return $this->hiddenCount;
    }
}

