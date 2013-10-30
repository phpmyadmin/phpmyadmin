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
 * Represents a columns node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Table extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     *
     * @return Node_Table
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        switch ($GLOBALS['cfg']['NavigationTreeDefaultTabTable']) {
        case 'tbl_structure.php':
            $title = __('Structure');
            break;
        case 'tbl_sql.php':
            $title = __('SQL');
            break;
        case 'tbl_select.php':
            $title = __('Search');
            break;
        case 'tbl_change.php':
            $title = __('Insert');
            break;
        case 'sql.php':
            $title = __('Browse');
            break;
        }

        $this->icon  = PMA_Util::getImage('b_browse.png', $title);
        $this->links = array(
            'text' => $GLOBALS['cfg']['DefaultTabTable']
                    . '?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;pos=0&amp;token=' . $GLOBALS['token'],
            'icon' => $GLOBALS['cfg']['NavigationTreeDefaultTabTable']
                    . '?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s&amp;token=' . $GLOBALS['token']
        );
        $this->classes = 'table';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the Node_Database and Node_Table classes
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
        $db     = $this->realParent()->real_name;
        $table  = $this->real_name;
        switch ($type) {
        case 'columns':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = PMA_Util::sqlAddSlashes($db);
                $table  = PMA_Util::sqlAddSlashes($table);
                $query  = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`COLUMNS` ";
                $query .= "WHERE `TABLE_NAME`='$table' ";
                $query .= "AND `TABLE_SCHEMA`='$db'";
                $retval = (int)PMA_DBI_fetch_value($query);
            } else {
                $db     = PMA_Util::backquote($db);
                $table  = PMA_Util::backquote($table);
                $query  = "SHOW COLUMNS FROM $table FROM $db";
                $retval = (int)PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'indexes':
            $db     = PMA_Util::backquote($db);
            $table  = PMA_Util::backquote($table);
            $query  = "SHOW INDEXES FROM $table FROM $db";
            $retval = (int)PMA_DBI_num_rows(PMA_DBI_try_query($query));
            break;
        case 'triggers':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = PMA_Util::sqlAddSlashes($db);
                $table  = PMA_Util::sqlAddSlashes($table);
                $query  = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TRIGGERS` ";
                $query .= "WHERE `EVENT_OBJECT_SCHEMA`='$db' ";
                $query .= "AND `EVENT_OBJECT_TABLE`='$table'";
                $retval = (int)PMA_DBI_fetch_value($query);
            } else {
                $db     = PMA_Util::backquote($db);
                $table  = PMA_Util::sqlAddSlashes($table);
                $query  = "SHOW TRIGGERS FROM $db WHERE `Table` = '$table'";
                $retval = (int)PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
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
        $db       = $this->realParent()->real_name;
        $table    = $this->real_name;
        switch ($type) {
        case 'columns':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = PMA_Util::sqlAddSlashes($db);
                $table  = PMA_Util::sqlAddSlashes($table);
                $query  = "SELECT `COLUMN_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`COLUMNS` ";
                $query .= "WHERE `TABLE_NAME`='$table' ";
                $query .= "AND `TABLE_SCHEMA`='$db' ";
                $query .= "ORDER BY `COLUMN_NAME` ASC ";
                $query .= "LIMIT " . intval($pos) . ", $maxItems";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = PMA_Util::backquote($db);
                $table  = PMA_Util::backquote($table);
                $query  = "SHOW COLUMNS FROM $table FROM $db";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    $count = 0;
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        if ($pos <= 0 && $count < $maxItems) {
                            $retval[] = $arr['Field'];
                            $count++;
                        }
                        $pos--;
                    }
                }
            }
            break;
        case 'indexes':
            $db     = PMA_Util::backquote($db);
            $table  = PMA_Util::backquote($table);
            $query  = "SHOW INDEXES FROM $table FROM $db";
            $handle = PMA_DBI_try_query($query);
            if ($handle !== false) {
                $count = 0;
                while ($arr = PMA_DBI_fetch_array($handle)) {
                    if (! in_array($arr['Key_name'], $retval)) {
                        if ($pos <= 0 && $count < $maxItems) {
                            $retval[] = $arr['Key_name'];
                            $count++;
                        }
                        $pos--;
                    }
                }
            }
            break;
        case 'triggers':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = PMA_Util::sqlAddSlashes($db);
                $table  = PMA_Util::sqlAddSlashes($table);
                $query  = "SELECT `TRIGGER_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TRIGGERS` ";
                $query .= "WHERE `EVENT_OBJECT_SCHEMA`='$db' ";
                $query .= "AND `EVENT_OBJECT_TABLE`='$table' ";
                $query .= "ORDER BY `TRIGGER_NAME` ASC ";
                $query .= "LIMIT " . intval($pos) . ", $maxItems";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = PMA_Util::backquote($db);
                $table  = PMA_Util::sqlAddSlashes($table);
                $query  = "SHOW TRIGGERS FROM $db WHERE `Table` = '$table'";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    $count = 0;
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        if ($pos <= 0 && $count < $maxItems) {
                            $retval[] = $arr['Trigger'];
                            $count++;
                        }
                        $pos--;
                    }
                }
            }
            break;
        default:
            break;
        }
        return $retval;
    }
}

?>
