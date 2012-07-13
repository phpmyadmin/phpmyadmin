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
    public function getPresence($type, $searchClause = '')
    {
        $retval = 0;
        $db = $this->real_name;
        switch ($type) {
        case 'tables':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
                $query .= "WHERE `TABLE_SCHEMA`='$db' ";
                $query .= "AND `TABLE_TYPE`='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND `TABLE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = (int)PMA_DBI_fetch_value($query);
            } else {
                $query  = "SHOW FULL TABLES FROM ";
                $query .= $this->_commonFunctions->backquote($db);
                $query .= " WHERE `Table_type`='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND " . $this->_commonFunctions->backquote(
                        "Tables_in_" . $db
                    );
                    $query .= " LIKE '%" . $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'views':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`TABLES` ";
                $query .= "WHERE `TABLE_SCHEMA`='$db' ";
                $query .= "AND `TABLE_TYPE`!='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND `TABLE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = (int)PMA_DBI_fetch_value($query);
            } else {
                $query  = "SHOW FULL TABLES FROM ";
                $query .= $this->_commonFunctions->backquote($db);
                $query .= " WHERE `Table_type`!='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND " . $this->_commonFunctions->backquote(
                        "Tables_in_" . $db
                    );
                    $query .= " LIKE '%" . $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'procedures':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
                $query .= "WHERE `ROUTINE_SCHEMA`='$db'";
                $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
                if (! empty($searchClause)) {
                    $query .= "AND `ROUTINE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = (int)PMA_DBI_fetch_value($query);
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW PROCEDURE STATUS WHERE `Db`='$db' ";
                if (! empty($searchClause)) {
                    $query .= "WHERE `Name` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'functions':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`ROUTINES` ";
                $query .= "WHERE `ROUTINE_SCHEMA`='$db' ";
                $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
                if (! empty($searchClause)) {
                    $query .= "AND `ROUTINE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = (int)PMA_DBI_fetch_value($query);
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW FUNCTION STATUS WHERE `Db`='$db' ";
                if (! empty($searchClause)) {
                    $query .= "WHERE `Name` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        case 'events':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT COUNT(*) ";
                $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
                $query .= "WHERE `EVENT_SCHEMA`='$db' ";
                if (! empty($searchClause)) {
                    $query .= "AND `EVENT_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = (int)PMA_DBI_fetch_value($query);
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW EVENTS FROM $db ";
                if (! empty($searchClause)) {
                    $query .= "WHERE `Name` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $retval = PMA_DBI_num_rows(PMA_DBI_try_query($query));
            }
            break;
        default:
            break;
        }
        return $retval;
    }

    public function getData($type, $pos, $searchClause = '')
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
                $query .= "AND `TABLE_TYPE`='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND `TABLE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $query .= "ORDER BY `TABLE_NAME` ASC ";
                $query .= "LIMIT $pos, {$GLOBALS['cfg']['MaxTableList']}";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $query  = " SHOW FULL TABLES FROM ";
                $query .= $this->_commonFunctions->backquote($db);
                $query .= "WHERE `Table_type`='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND " . $this->_commonFunctions->backquote(
                        "Tables_in_" . $db
                    );
                    $query .= " LIKE '%" . $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    $count  = 0;
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        if ($pos <= 0 && $count < $GLOBALS['cfg']['MaxTableList']) {
                            $retval[] = $arr[0];
                            $count++;
                        }
                        $pos--;
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
                $query .= "AND `TABLE_TYPE`!='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND `TABLE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $query .= "ORDER BY `TABLE_NAME` ASC ";
                $query .= "LIMIT $pos, {$GLOBALS['cfg']['MaxTableList']}";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $query  = "SHOW FULL TABLES FROM ";
                $query .= $this->_commonFunctions->backquote($db);
                $query .= " WHERE `Table_type`!='BASE TABLE' ";
                if (! empty($searchClause)) {
                    $query .= "AND " . $this->_commonFunctions->backquote(
                        "Tables_in_" . $db
                    );
                    $query .= " LIKE '%" . $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    $count  = 0;
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        if ($pos <= 0 && $count < $GLOBALS['cfg']['MaxTableList']) {
                            $retval[] = $arr[0];
                            $count++;
                        }
                        $pos--;
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
                $query .= "AND `ROUTINE_TYPE`='PROCEDURE' ";
                if (! empty($searchClause)) {
                    $query .= "AND `ROUTINE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $query .= "ORDER BY `ROUTINE_NAME` ASC ";
                $query .= "LIMIT $pos, {$GLOBALS['cfg']['MaxTableList']}";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW PROCEDURE STATUS WHERE `Db`='$db' ";
                if (! empty($searchClause)) {
                    $query .= "WHERE `Name` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    $count  = 0;
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        if ($pos <= 0 && $count < $GLOBALS['cfg']['MaxTableList']) {
                            $retval[] = $arr['Name'];
                            $count++;
                        }
                        $pos--;
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
                $query .= "AND `ROUTINE_TYPE`='FUNCTION' ";
                if (! empty($searchClause)) {
                    $query .= "AND `ROUTINE_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $query .= "ORDER BY `ROUTINE_NAME` ASC ";
                $query .= "LIMIT $pos, {$GLOBALS['cfg']['MaxTableList']}";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SHOW FUNCTION STATUS WHERE `Db`='$db' ";
                if (! empty($searchClause)) {
                    $query .= "WHERE `Name` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    $count  = 0;
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        if ($pos <= 0 && $count < $GLOBALS['cfg']['MaxTableList']) {
                            $retval[] = $arr['Name'];
                            $count++;
                        }
                        $pos--;
                    }
                }
            }
            break;
        case 'events':
            if (! $GLOBALS['cfg']['Servers'][$GLOBALS['server']]['DisableIS']) {
                $db     = $this->_commonFunctions->sqlAddSlashes($db);
                $query  = "SELECT `EVENT_NAME` AS `name` ";
                $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
                $query .= "WHERE `EVENT_SCHEMA`='$db' ";
                if (! empty($searchClause)) {
                    $query .= "AND `EVENT_NAME` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $query .= "ORDER BY `EVENT_NAME` ASC ";
                $query .= "LIMIT $pos, {$GLOBALS['cfg']['MaxTableList']}";
                $retval = PMA_DBI_fetch_result($query);
            } else {
                $db     = $this->_commonFunctions->backquote($db);
                $query  = "SHOW EVENTS FROM $db ";
                if (! empty($searchClause)) {
                    $query .= "WHERE `Name` LIKE '%";
                    $query .= $this->_commonFunctions->sqlAddSlashes(
                        $searchClause, true
                    );
                    $query .= "%'";
                }
                $handle = PMA_DBI_try_query($query);
                if ($handle !== false) {
                    $count  = 0;
                    while ($arr = PMA_DBI_fetch_array($handle)) {
                        if ($pos <= 0 && $count < $GLOBALS['cfg']['MaxTableList']) {
                            $retval[] = $arr['Name'];
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
