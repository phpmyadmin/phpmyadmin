<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Relation;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

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
     * @param string $name    An identifier for the new node
     * @param int    $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $isGroup Whether this object has been created
     *                        while grouping nodes
     */
    public function __construct($name, $type = Node::OBJECT, $isGroup = false)
    {
        parent::__construct($name, $type, $isGroup);
        $this->icon = Util::getImage(
            's_db',
            __('Database operations')
        );

        $scriptName = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabDatabase'],
            'database'
        );
        $this->links = [
            'text'  => $scriptName
                . '?server=' . $GLOBALS['server']
                . '&amp;db=%1$s',
            'icon'  => 'db_operations.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;',
            'title' => __('Structure'),
        ];
        $this->classes = 'database';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
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
                $retval = $this->getTableCount($searchClause, $singleItem);
                break;
            case 'views':
                $retval = $this->getViewCount($searchClause, $singleItem);
                break;
            case 'procedures':
                $retval = $this->getProcedureCount($searchClause, $singleItem);
                break;
            case 'functions':
                $retval = $this->getFunctionCount($searchClause, $singleItem);
                break;
            case 'events':
                $retval = $this->getEventCount($searchClause, $singleItem);
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
    private function getTableOrViewCount($which, $searchClause, $singleItem)
    {
        $db = $this->realName;
        if ($which == 'tables') {
            $condition = 'IN';
        } else {
            $condition = 'NOT IN';
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db     = $GLOBALS['dbi']->escapeString($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$db' ";
            $query .= "AND `TABLE_TYPE`" . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'TABLE_NAME'
                );
            }
            $retval = (int) $GLOBALS['dbi']->fetchValue($query);
        } else {
            $query = "SHOW FULL TABLES FROM ";
            $query .= Util::backquote($db);
            $query .= " WHERE `Table_type`" . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->getWhereClauseForSearch(
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
    private function getTableCount($searchClause, $singleItem)
    {
        return $this->getTableOrViewCount(
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
    private function getViewCount($searchClause, $singleItem)
    {
        return $this->getTableOrViewCount(
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
    private function getProcedureCount($searchClause, $singleItem)
    {
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . Util::getCollateForIS() . "='$db'";
            $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'ROUTINE_NAME'
                );
            }
            $retval = (int) $GLOBALS['dbi']->fetchValue($query);
        } else {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SHOW PROCEDURE STATUS WHERE `Db`='$db' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->getWhereClauseForSearch(
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
    private function getFunctionCount($searchClause, $singleItem)
    {
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . Util::getCollateForIS() . "='$db' ";
            $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'ROUTINE_NAME'
                );
            }
            $retval = (int) $GLOBALS['dbi']->fetchValue($query);
        } else {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SHOW FUNCTION STATUS WHERE `Db`='$db' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->getWhereClauseForSearch(
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
    private function getEventCount($searchClause, $singleItem)
    {
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $db = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA` "
                . Util::getCollateForIS() . "='$db' ";
            if (! empty($searchClause)) {
                $query .= "AND " . $this->getWhereClauseForSearch(
                    $searchClause,
                    $singleItem,
                    'EVENT_NAME'
                );
            }
            $retval = (int) $GLOBALS['dbi']->fetchValue($query);
        } else {
            $db = Util::backquote($db);
            $query = "SHOW EVENTS FROM $db ";
            if (! empty($searchClause)) {
                $query .= "WHERE " . $this->getWhereClauseForSearch(
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
    private function getWhereClauseForSearch(
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
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
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
        $pos = (int) $pos;
        $retval = [];
        switch ($type) {
            case 'tables':
                $retval = $this->getTables($pos, $searchClause);
                break;
            case 'views':
                $retval = $this->getViews($pos, $searchClause);
                break;
            case 'procedures':
                $retval = $this->getProcedures($pos, $searchClause);
                break;
            case 'functions':
                $retval = $this->getFunctions($pos, $searchClause);
                break;
            case 'events':
                $retval = $this->getEvents($pos, $searchClause);
                break;
            default:
                break;
        }

        // Remove hidden items so that they are not displayed in navigation tree
        $cfgRelation = $this->relation->getRelationsParam();
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
        $db = $this->realName;
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['navwork']) {
            return [];
        }
        $navTable = Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['navigationhiding']);
        $sqlQuery = "SELECT `item_name` FROM " . $navTable
            . " WHERE `username`='" . $cfgRelation['user'] . "'"
            . " AND `item_type`='" . $type
            . "' AND `db_name`='" . $GLOBALS['dbi']->escapeString($db)
            . "'";
        $result = $this->relation->queryAsControlUser($sqlQuery, false);
        $hiddenItems = [];
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
    private function getTablesOrViews($which, int $pos, $searchClause)
    {
        if ($which == 'tables') {
            $condition = 'IN';
        } else {
            $condition = 'NOT IN';
        }
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval   = [];
        $db       = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $GLOBALS['dbi']->escapeString($db);
            $query  = "SELECT `TABLE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$escdDb' ";
            $query .= "AND `TABLE_TYPE`" . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
                $query .= "AND `TABLE_NAME` LIKE '%";
                $query .= $GLOBALS['dbi']->escapeString($searchClause);
                $query .= "%'";
            }
            $query .= "ORDER BY `TABLE_NAME` ASC ";
            $query .= "LIMIT " . $pos . ", $maxItems";
            $retval = $GLOBALS['dbi']->fetchResult($query);
        } else {
            $query = " SHOW FULL TABLES FROM ";
            $query .= Util::backquote($db);
            $query .= " WHERE `Table_type`" . $condition . "('BASE TABLE', 'SYSTEM VERSIONED') ";
            if (! empty($searchClause)) {
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
    private function getTables(int $pos, $searchClause)
    {
        return $this->getTablesOrViews('tables', $pos, $searchClause);
    }

    /**
     * Returns the list of views inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getViews(int $pos, $searchClause)
    {
        return $this->getTablesOrViews('views', $pos, $searchClause);
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
    private function getRoutines($routineType, $pos, $searchClause)
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = [];
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT `ROUTINE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA` "
                . Util::getCollateForIS() . "='$escdDb'";
            $query .= "AND `ROUTINE_TYPE`='" . $routineType . "' ";
            if (! empty($searchClause)) {
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
            if (! empty($searchClause)) {
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
    private function getProcedures($pos, $searchClause)
    {
        return $this->getRoutines('PROCEDURE', $pos, $searchClause);
    }

    /**
     * Returns the list of functions inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getFunctions($pos, $searchClause)
    {
        return $this->getRoutines('FUNCTION', $pos, $searchClause);
    }

    /**
     * Returns the list of events inside this database
     *
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return array
     */
    private function getEvents($pos, $searchClause)
    {
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval = [];
        $db = $this->realName;
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $escdDb = $GLOBALS['dbi']->escapeString($db);
            $query = "SELECT `EVENT_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA` "
                . Util::getCollateForIS() . "='$escdDb' ";
            if (! empty($searchClause)) {
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
            if (! empty($searchClause)) {
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
        $cfgRelation = $this->relation->getRelationsParam();
        if ($cfgRelation['navwork']) {
            if ($this->hiddenCount > 0) {
                $params = [
                    'showUnhideDialog' => true,
                    'dbName' => $this->realName,
                ];
                $ret = '<span class="dbItemControls">'
                    . '<a href="navigation.php" data-post="'
                    . Url::getCommon($params, '') . '"'
                    . ' class="showUnhide ajax">'
                    . Util::getImage(
                        'show',
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
