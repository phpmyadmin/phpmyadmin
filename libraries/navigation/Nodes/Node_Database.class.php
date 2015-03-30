<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Represents a database node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Database extends Node
{
    /**
     * The number of hidden items in this database
     *
     * @var int
     */
    private $_hiddenCount = 0;

    /**
     * Initialises the class
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     *
     * @return Node_Database
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon  = PMA_Util::getImage(
            's_db.png',
            __('Database operations')
        );
        $this->links = array(
            'text' => $GLOBALS['cfg']['DefaultTabDatabase']
                    . '?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $_SESSION[' PMA_token '],
            'icon' => 'db_operations.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $_SESSION[' PMA_token '],
            'title' => __('Structure')
        );
        $this->classes = 'database';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the Node_Database and Node_Table classes
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
        $retval = 0;
        $db     = $this->real_name;
        if ($which == 'tables') {
            $condition = '=';
        } else {
            $condition = '!=';
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS'] || PMA_DRIZZLE) {
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$db' ";
            if (PMA_DRIZZLE) {
                $query .= "AND `TABLE_TYPE`" . $condition . "'BASE' ";
            } else {
                $query .= "AND `TABLE_TYPE`" . $condition . "'BASE TABLE' ";
            }
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'TABLE_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $query  = "SHOW FULL TABLES FROM ";
            $query .= PMA_Util::backquote($db);
            $query .= " WHERE `Table_type`" . $condition . "'BASE TABLE' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'Tables_in_' . $db
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
            'tables', $searchClause, $singleItem
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
            'views', $searchClause, $singleItem
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
        $retval = 0;
        $db     = $this->real_name;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . PMA_Util::getCollateForIS() . "='$db'";
            $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'ROUTINE_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $db    = PMA_Util::sqlAddSlashes($db);
            $query = "SHOW PROCEDURE STATUS WHERE `Db`='$db' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'Name'
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
        $retval = 0;
        $db     = $this->real_name;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . PMA_Util::getCollateForIS() . "='$db' ";
            $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'ROUTINE_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $db    = PMA_Util::sqlAddSlashes($db);
            $query = "SHOW FUNCTION STATUS WHERE `Db`='$db' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'Name'
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
        $retval = 0;
        $db     = $this->real_name;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA` "
                . PMA_Util::getCollateForIS() . "='$db' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'EVENT_NAME'
                );
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
        } else {
            $db    = PMA_Util::backquote($db);
            $query = "SHOW EVENTS FROM $db ";
            if (! empty($searchClause)) {
                $query .= "WHERE " . $this->_getWhereClauseForSearch(
                    $searchClause, $singleItem, 'Name'
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
        $searchClause, $singleItem, $columnName
    ) {
        $query = '';
        if ($singleItem) {
            $query .= PMA_Util::backquote($columnName) . " = ";
            $query .= "'" . PMA_Util::sqlAddSlashes($searchClause) . "'";
        } else {
            $query .= PMA_Util::backquote($columnName) . " LIKE ";
            $query .= "'%" . PMA_Util::sqlAddSlashes($searchClause, true) . "%'";
        }
        return $query;
    }

    /**
     * Returns the names of children of type $type present inside this container
     * This method is overridden by the Node_Database and Node_Table classes
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
        $retval   = array();
        $db       = $this->real_name;
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
        if (isset($cfgRelation['navwork']) && $cfgRelation['navwork']) {
            $navTable = PMA_Util::backquote($cfgRelation['db'])
                . "." . PMA_Util::backquote($cfgRelation['navigationhiding']);
            $sqlQuery = "SELECT `item_name` FROM " . $navTable
                . " WHERE `username`='" . $cfgRelation['user'] . "'"
                . " AND `item_type`='" . substr($type, 0, -1)
                . "'" . " AND `db_name`='" . PMA_Util::sqlAddSlashes($db) . "'";
            $result = PMA_queryAsControlUser($sqlQuery, false);
            if ($result) {
                $hiddenItems = array();
                while ($row = $GLOBALS['dbi']->fetchArray($result)) {
                    $hiddenItems[] = $row[0];
                }
                foreach ($retval as $key => $item) {
                    if (in_array($item, $hiddenItems)) {
                        unset($retval[$key]);
                    }
                }
            }
            $GLOBALS['dbi']->freeResult($result);
        }

        return $retval;
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
        if (! $GLOBALS['cfg']['Server']['DisableIS'] || PMA_DRIZZLE) {
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `TABLE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$escdDb' ";
            if (PMA_DRIZZLE) {
                $query .= "AND `TABLE_TYPE`" . $condition . "'BASE' ";
            } else {
                $query .= "AND `TABLE_TYPE`" . $condition . "'BASE TABLE' ";
            }
            if (! empty($searchClause)) {
                $query .= "AND `TABLE_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $query .= "ORDER BY `TABLE_NAME` ASC ";
            $query .= "LIMIT " . intval($pos) . ", $maxItems";
            $retval = $GLOBALS['dbi']->fetchResult($query);
        } else {
            $query  = " SHOW FULL TABLES FROM ";
            $query .= PMA_Util::backquote($db);
            $query .= " WHERE `Table_type`" . $condition . "'BASE TABLE' ";
            if (! empty($searchClause)) {
                $query .= "AND " . PMA_Util::backquote(
                    "Tables_in_" . $db
                );
                $query .= " LIKE '%" . PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle !== false) {
                $count = 0;
                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($pos <= 0 && $count < $maxItems) {
                        $retval[] = $arr[0];
                        $count++;
                    }
                    $pos--;
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
        $retval   = array();
        $db       = $this->real_name;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `ROUTINE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . PMA_Util::getCollateForIS() . "='$escdDb'";
            $query .= "AND `ROUTINE_TYPE`='" . $routineType . "' ";
            if (! empty($searchClause)) {
                $query .= "AND `ROUTINE_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $query .= "ORDER BY `ROUTINE_NAME` ASC ";
            $query .= "LIMIT " . intval($pos) . ", $maxItems";
            $retval = $GLOBALS['dbi']->fetchResult($query);
        } else {
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SHOW " . $routineType . " STATUS WHERE `Db`='$escdDb' ";
            if (! empty($searchClause)) {
                $query .= "AND `Name` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle !== false) {
                $count = 0;
                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($pos <= 0 && $count < $maxItems) {
                        $retval[] = $arr['Name'];
                        $count++;
                    }
                    $pos--;
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
        $retval   = array();
        $db       = $this->real_name;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `EVENT_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA` "
                . PMA_Util::getCollateForIS() . "='$escdDb' ";
            if (! empty($searchClause)) {
                $query .= "AND `EVENT_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $query .= "ORDER BY `EVENT_NAME` ASC ";
            $query .= "LIMIT " . intval($pos) . ", $maxItems";
            $retval = $GLOBALS['dbi']->fetchResult($query);
        } else {
            $escdDb = PMA_Util::backquote($db);
            $query  = "SHOW EVENTS FROM $escdDb ";
            if (! empty($searchClause)) {
                $query .= "WHERE `Name` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $handle = $GLOBALS['dbi']->tryQuery($query);
            if ($handle !== false) {
                $count = 0;
                while ($arr = $GLOBALS['dbi']->fetchArray($handle)) {
                    if ($pos <= 0 && $count < $maxItems) {
                        $retval[] = $arr['Name'];
                        $count++;
                    }
                    $pos--;
                }
            }
        }
        return $retval;
    }

    /**
     * Returns HTML for show hidden button displayed infront of database node
     *
     * @return String HTML for show hidden button
     */
    public function getHtmlForControlButtons()
    {
        $ret = '';
        $cfgRelation = PMA_getRelationsParam();
        if (isset($cfgRelation['navwork']) && $cfgRelation['navwork']) {
            if ( $this->_hiddenCount > 0) {
                $ret = '<span class="dbItemControls">'
                    . '<a href="navigation.php'
                    . PMA_URL_getCommon()
                    . '&showUnhideDialog=true'
                    . '&dbName=' . urldecode($this->real_name) . '"'
                    . ' class="showUnhide ajax">'
                    . PMA_Util::getImage(
                        'lightbulb.png', __('Show hidden items')
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
        $this->_hiddenCount = $count;
    }

    /**
     * Returns the number of hidden items in this database
     *
     * @return int hidden item count
     */
    public function getHiddenCount()
    {
        return $this->_hiddenCount;
    }
}

?>
