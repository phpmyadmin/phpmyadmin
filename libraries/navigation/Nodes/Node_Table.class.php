<?php

class Node_Table extends Node {
    
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = $this->_commonFunctions->getImage('b_browse.png');
        $this->links = array(
            'text' => 'sql.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;pos=0&amp;token=' . $GLOBALS['token'],
            'icon' => $GLOBALS['cfg']['LeftDefaultTabTable']
                    . '?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s&amp;token=' . $GLOBALS['token']
        );
    }

    public function getPresence($type)
    {
        $retval = 0;
        $db = $this->realParent()->real_name;
        $table = $this->real_name;
        switch ($type) {
        case 'columns':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $table  = $this->_commonFunctions->sqlAddSlashes($table);
                $query  = "SELECT `COLUMN_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`COLUMNS` ";
                $query .= "WHERE `TABLE_NAME`='$table' ";
                $query .= "AND `TABLE_SCHEMA`='$db' ";
                $query .= "LIMIT 1";
                $retval = PMA_DBI_fetch_value($query) === false ? 0 : 1;
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $table  = $this->_commonFunctions->backquote($table);
                $query  = "SHOW COLUMNS FROM $table FROM $db";
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'indexes':
            $db     = $this->_commonFunctions->backquote($db);
            $table  = $this->_commonFunctions->backquote($table);
            $query  = "SHOW INDEXES FROM $table FROM $db";
            $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            break;
        case 'triggers':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $table  = $this->_commonFunctions->sqlAddSlashes($table);
                $query  = "SELECT `TRIGGER_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TRIGGERS` ";
                $query .= "WHERE `EVENT_OBJECT_SCHEMA`='$db' ";
                $query .= "AND `EVENT_OBJECT_TABLE`='$table' ";
                $query .= "LIMIT 1";
                $retval = PMA_DBI_fetch_value($query) === false ? 0 : 1;
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $table  = $this->_commonFunctions->sqlAddSlashes($table);
                $query  = "SHOW TRIGGERS FROM $db WHERE `Table` = '$table'";
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        default:
            break;
        }
        return $retval;
    }

    public function getData($type)
    {
        $retval = array();
        $db = $this->realParent()->real_name;
        $table = $this->real_name;
        switch ($type) {
        case 'columns':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $table  = $this->_commonFunctions->sqlAddSlashes($table);
                $query  = "SELECT `COLUMN_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`COLUMNS` ";
                $query .= "WHERE `TABLE_NAME`='$table' ";
                $query .= "AND `TABLE_SCHEMA`='$db'";
                $query .= "ORDER BY `COLUMN_NAME` ASC";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $table  = $this->_commonFunctions->backquote($table);
                $query  = "SHOW COLUMNS FROM $table FROM $db";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    while ($arr = PMA_DBI_fetch_assoc($handle)) {
                        $retval[] = $arr['Field'];
                    }
                }
            }
            break;
        case 'indexes':
            $db     = $this->_commonFunctions->backquote($db);
            $table  = $this->_commonFunctions->backquote($table);
            $query  = "SHOW INDEXES FROM $table FROM $db";
            $handle = PMA_DBI_try_query($query);
            if ($handle !== false) {
                while ($arr = PMA_DBI_fetch_assoc($handle)) {
                    if (! in_array($arr['Key_name'], $retval)) {
                        $retval[] = $arr['Key_name'];
                    }
                }
            }
            break;
        case 'triggers':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $table  = $this->_commonFunctions->sqlAddSlashes($table);
                $query  = "SELECT `TRIGGER_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TRIGGERS` ";
                $query .= "WHERE `EVENT_OBJECT_SCHEMA`='$db' ";
                $query .= "AND `EVENT_OBJECT_TABLE`='$table'";
                $query .= "ORDER BY `TRIGGER_NAME` ASC";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $table  = $this->_commonFunctions->sqlAddSlashes($table);
                $query  = "SHOW TRIGGERS FROM $db WHERE `Table` = '$table'";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    while ($arr = PMA_DBI_fetch_assoc($handle)) {
                        $retval[] = $arr['Trigger'];
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
