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
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_operations.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token']
        );
        $this->classes = 'database';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the Node_Database and Node_Table classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return int
     */
    public function getPresence($type = '', $searchClause = '')
    {
        $retval = 0;
        $db     = $this->real_name;
        switch ($type) {
        case 'tables':
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$db' ";
            if (PMA_DRIZZLE) {
                $query .= "AND `TABLE_TYPE`='BASE' ";
            } else {
                $query .= "AND `TABLE_TYPE`='BASE TABLE' ";
            }
            if (! empty($searchClause)) {
                $query .= "AND `TABLE_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            break;
        case 'views':
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$db' ";
            if (PMA_DRIZZLE) {
                $query .= "AND `TABLE_TYPE`!='BASE' ";
            } else {
                $query .= "AND `TABLE_TYPE`!='BASE TABLE' ";
            }
            if (! empty($searchClause)) {
                $query .= "AND `TABLE_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            break;
        case 'procedures':
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA`='$db'";
            $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
            if (! empty($searchClause)) {
                $query .= "AND `ROUTINE_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            break;
        case 'functions':
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA`='$db' ";
            $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
            if (! empty($searchClause)) {
                $query .= "AND `ROUTINE_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            break;
        case 'events':
            $db     = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT COUNT(*) ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA`='$db' ";
            if (! empty($searchClause)) {
                $query .= "AND `EVENT_NAME` LIKE '%";
                $query .= PMA_Util::sqlAddSlashes(
                    $searchClause, true
                );
                $query .= "%'";
            }
            $retval = (int)$GLOBALS['dbi']->fetchValue($query);
            break;
        default:
            break;
        }
        return $retval;
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
        $maxItems = $GLOBALS['cfg']['MaxNavigationItems'];
        $retval   = array();
        $db       = $this->real_name;
        switch ($type) {
        case 'tables':
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `TABLE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$escdDb' ";
            if (PMA_DRIZZLE) {
                $query .= "AND `TABLE_TYPE`='BASE' ";
            } else {
                $query .= "AND `TABLE_TYPE`='BASE TABLE' ";
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
            break;
        case 'views':
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `TABLE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
            $query .= "WHERE `TABLE_SCHEMA`='$escdDb' ";
            if (PMA_DRIZZLE) {
                $query .= "AND `TABLE_TYPE`!='BASE' ";
            } else {
                $query .= "AND `TABLE_TYPE`!='BASE TABLE' ";
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
            break;
        case 'procedures':
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `ROUTINE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA`='$escdDb'";
            $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
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
            break;
        case 'functions':
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `ROUTINE_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
            $query .= "WHERE `ROUTINE_SCHEMA`='$escdDb' ";
            $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
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
            break;
        case 'events':
            $escdDb = PMA_Util::sqlAddSlashes($db);
            $query  = "SELECT `EVENT_NAME` AS `name` ";
            $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
            $query .= "WHERE `EVENT_SCHEMA`='$escdDb' ";
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
            break;
        default:
            break;
        }

        // Remove hidden items so that they are not displayed in navigation tree
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['navwork']) {
            $navTable = PMA_Util::backquote($cfgRelation['db'])
                . "." . PMA_Util::backquote($cfgRelation['navigationhiding']);
            $sqlQuery = "SELECT `item_name` FROM " . $navTable
                . " WHERE `username`='" . $cfgRelation['user'] . "'"
                . " AND `item_type`='" . substr($type, 0, -1) . "'"
                . " AND `db_name`='" . PMA_Util::sqlAddSlashes($db) . "'";
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
     * Returns HTML for show hidden button displayed infront of database node
     *
     * @return String HTML for show hidden button
     */
    public function getHtmlForControlButtons()
    {
        $ret = '';
        $db = $this->real_name;

        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['navwork']) {
            $navTable = PMA_Util::backquote($cfgRelation['db'])
                . "." . PMA_Util::backquote($cfgRelation['navigationhiding']);
            $sqlQuery = "SELECT COUNT(*) FROM " . $navTable
                . " WHERE `username`='"
                . PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . "'"
                . " AND `db_name`='" . PMA_Util::sqlAddSlashes($db) . "'";
            $count = $GLOBALS['dbi']->fetchValue(
                $sqlQuery, 0, 0, $GLOBALS['controllink']
            );
            if ($count > 0) {
                $ret = '<span class="dbItemControls">'
                    . '<a href="navigation.php?'
                    . PMA_URL_getCommon()
                    . '&showUnhideDialog=true'
                    . '&dbName=' . urldecode($db) . '"'
                    . ' class="showUnhide ajax">'
                    . PMA_Util::getImage(
                        'lightbulb.png', __('Show hidden items')
                    )
                    . '</a></span>';
            }
        }
        return $ret;
    }
}

?>
