<?php

class Node_Database extends Node {
    
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = $this->_commonFunctions->getImage('s_db.png');
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_operations.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token']
        );
    }
    public function getPresence($type)
    {
        $retval = 0;
        $db = $this->real_name;
        switch ($type) {
        case 'tables':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `TABLE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
                $query .= "WHERE `TABLE_SCHEMA`='$db' ";
                $query .= "AND `TABLE_TYPE`='BASE TABLE' ";
                $query .= "LIMIT 1";
                $retval = PMA_DBI_fetch_value($query) === false ? 0 : 1;
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW FULL TABLES FROM $db ";
                $query .= "WHERE `Table_type`='BASE TABLE'";
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'views':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `TABLE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
                $query .= "WHERE `TABLE_SCHEMA`='$db' ";
                $query .= "AND `TABLE_TYPE`!='BASE TABLE' ";
                $query .= "LIMIT 1";
                $retval = PMA_DBI_fetch_value($query) === false ? 0 : 1;
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW FULL TABLES FROM $db ";
                $query .= "WHERE `Table_type`!='BASE TABLE'";
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'procedures':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `ROUTINE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
                $query .= "WHERE `ROUTINE_SCHEMA`='$db'";
                $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
                $query .= "LIMIT 1";
                $retval = PMA_DBI_fetch_value($query) === false ? 0 : 1;
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW PROCEDURE STATUS WHERE `Db`='$db'";
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'functions':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `ROUTINE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
                $query .= "WHERE `ROUTINE_SCHEMA`='$db' ";
                $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
                $query .= "LIMIT 1";
                $retval = PMA_DBI_fetch_value($query) === false ? 0 : 1;
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW FUNCTION STATUS WHERE `Db`='$db'";
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'events':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `EVENT_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
                $query .= "WHERE `EVENT_SCHEMA`='$db' ";
                $query .= "LIMIT 1";
                $retval = PMA_DBI_fetch_value($query) === false ? 0 : 1;
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW EVENTS FROM $db";
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
        $db = $this->real_name;
        switch ($type) {
        case 'tables':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `TABLE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
                $query .= "WHERE `TABLE_SCHEMA`='$db' ";
                $query .= "AND `TABLE_TYPE`='BASE TABLE'";
                $query .= "ORDER BY `TABLE_NAME` ASC";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW FULL TABLES FROM $db ";
                $query .= "WHERE `Table_type`='BASE TABLE'";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        $retval[] = $arr[0];
                    }
                }
            }
            break;
        case 'views':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `TABLE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
                $query .= "WHERE `TABLE_SCHEMA`='$db' ";
                $query .= "AND `TABLE_TYPE`!='BASE TABLE'";
                $query .= "ORDER BY `TABLE_NAME` ASC";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW FULL TABLES FROM $db ";
                $query .= "WHERE `Table_type`!='BASE TABLE'";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        $retval[] = $arr[0];
                    }
                }
            }
            break;
        case 'procedures':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `ROUTINE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
                $query .= "WHERE `ROUTINE_SCHEMA`='$db'";
                $query .= "AND `ROUTINE_TYPE`='PROCEDURE'";
                $query .= "ORDER BY `ROUTINE_NAME` ASC";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW PROCEDURE STATUS WHERE `Db`='$db'";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    while ($arr = PMA_DBI_fetch_assoc($handle)) {
                        $retval[] = $arr['Name'];
                    }
                }
            }
            break;
        case 'functions':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `ROUTINE_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
                $query .= "WHERE `ROUTINE_SCHEMA`='$db' ";
                $query .= "AND `ROUTINE_TYPE`='FUNCTION'";
                $query .= "ORDER BY `ROUTINE_NAME` ASC";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW FUNCTION STATUS WHERE `Db`='$db'";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    while ($arr = PMA_DBI_fetch_assoc($handle)) {
                        $retval[] = $arr['Name'];
                    }
                }
            }
            break;
        case 'events':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `EVENT_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
                $query .= "WHERE `EVENT_SCHEMA`='$db'";
                $query .= "ORDER BY `EVENT_NAME` ASC";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW EVENTS FROM $db";
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    while ($arr = PMA_DBI_fetch_assoc($handle)) {
                        $retval[] = $arr['Name'];
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
