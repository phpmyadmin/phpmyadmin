<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Defines the central_columns parameters for the current user
 *
 * @return array    the central_columns parameters for the current user
 * @access  public
 */
function PMA_centralColumnsGetParams()
{
    static $cfgCentralColumns = null;

    if (null !== $cfgCentralColumns) {
        return $cfgCentralColumns;
    }

    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['central_columnswork']) {
        $cfgCentralColumns = array(
            'user'  => $GLOBALS['cfg']['Server']['user'],
            'db'    => $GLOBALS['cfg']['Server']['pmadb'],
            'table' => $GLOBALS['cfg']['Server']['central_columns'],
        );
    } else {
        $cfgCentralColumns = false;
    }

    return $cfgCentralColumns;
}

/**
 * get all columns of given database from central columns list 
 * 
 * @param string $db selected databse
 * 
 * @return array associative array of table name and 
 * corresponding columns from the columns list
 */
function PMA_getColumnsList($db)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return array();
    }
    $pmadb = $GLOBALS['cfg']['Server']['pmadb'];
    $GLOBALS['dbi']->selectDb($pmadb);
    $central_list_table = $GLOBALS['cfg']['Server']['central_columns'];
    //get current values of $db from central column list
    $query = 'SELECT * FROM ' . PMA_Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\';';
    $has_list = (array) json_decode($GLOBALS['dbi']->fetchValue($query, 0, 1));
    return $has_list;
}

/**
 * Sync unique columns of given tables with central columns list 
 * 
 * @param array $field_select selected tables list
 * @param bool  $isTable      if passed array is of tables or columns
 * 
 * @return true|PMA_Message
 * 
 * @todo eliminate duplicates
 */
function PMA_syncUniqueColumns($field_select, $isTable=true) 
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_Message::error(
            __(
                'Central list of columns configuration Storage '
                . 'is not completely configured!'
            )
        );
    }
    $db = $_POST['db'];
    $pmadb = $GLOBALS['cfg']['Server']['pmadb'];
    $central_list_table = $GLOBALS['cfg']['Server']['central_columns'];
    $has_list = PMA_getColumnsList($db);
    $GLOBALS['dbi']->selectDb($db);
    $sync = $has_list;
    $existingCols = "";
    $message = true;
    if ($isTable) {
        foreach ($field_select as $table) {
            $sync_tmp = isset($sync[$table])?(array)$sync[$table]:array();
            $fields = (array) $GLOBALS['dbi']->getColumns($db, $table, null, true);
            foreach ($fields as $field => $def) {
                if (!isset($sync_tmp[$field])) {
                    $sync_tmp[$field] = (isset($def['Type']))?$def['Type']:"";
                    $sync_tmp[$field] .= " ";
                    $sync_tmp[$field] .= (isset($def['key']))?$def['key']:"";
                    $sync_tmp[$field] .= " ";
                    $sync_tmp[$field] .= (isset($def['collation']))?$def['collation']:"";
                    $sync_tmp[$field] .= " ";
                    $sync_tmp[$field] .= (isset($def['Null']))?$def['Null']:"";
                    $sync_tmp[$field] .= " ";
                    $sync_tmp[$field] .= (isset($def['Extra']))?$def['Extra']:"";
                } else {
                    $existingCols .= ", '".$table.'.'.$field."'";
                }
            }
            $sync[$table] = $sync_tmp;
            //echo '<br><br>';
        }
    } else {
        $table = $_POST['table'];
        $sync_tmp = PMA_getCentralColumnsFromTable($db, $table);
        foreach ($field_select as $column) {
                $field = (array) $GLOBALS['dbi']->getColumns(
                    $db, $table, $column,
                    true
                );
            if (!isset($sync_tmp[$column])) {
                $sync_tmp[$column] = (isset($field['Type']))?$field['Type']:"";
                $sync_tmp[$column] .= " ";
                $sync_tmp[$column] .= (isset($field['key']))?$field['key']:"";
                $sync_tmp[$column] .= " ";
                $sync_tmp[$column] .= (isset($field['collation']))?$field['collation']:""; 
                $sync_tmp[$column] .= " ";
                $sync_tmp[$column] .= (isset($field['Null']))?$field['Null']:"";
                $sync_tmp[$column] .= " ";
                $sync_tmp[$column] .= (isset($field['Extra']))?$field['Extra']:"";
            } else {
                $existingCols .= ", '".$table.'.'.$column."'";
            }
        }
        $sync[$table] = $sync_tmp;
    }
    if ($existingCols != "") {
        $existingCols = trim($existingCols, ',');
        $message = PMA_Message::notice(
            __(
                "Could not add $existingCols as they already exist in central list!"
            )
        );
        $message->addMessage('<br /><br />');
        $message->addMessage(
            PMA_Message::notice(
                "Please remove them first "
                . "from central list if you want to update above columns"
            )
        );
    }
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    if (!$has_list) {
        $query = 'INSERT INTO ' . PMA_Util::backquote($central_list_table) . ' '
                . 'VALUES ( \'' . $db . '\' '
                . ', \'' . PMA_Util::sqlAddSlashes(json_encode($sync)) . '\' );';
        
    } else {
        $query = 'UPDATE ' . PMA_Util::backquote($central_list_table) . ' '
                . 'SET column_list = '
                . '\'' . PMA_Util::sqlAddSlashes(json_encode($sync)) . '\' '
                . 'WHERE db_name = \'' . $db . '\';';
    }
    if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
        $message = PMA_Message::error(__('Could not sync columns!'));
        $message->addMessage('<br /><br />');
        $message->addMessage(
            PMA_Message::rawError(
                $GLOBALS['dbi']->getError($GLOBALS['controllink'])
            )
        );
    }
    return $message;
}

/**
 * remove all columns of given tables from central columns list
 * 
 * @param array $field_select selectd list of tables
 * @param bool  $isTable      if passed array is of tables or columns
 * 
 * @return true|PMA_Message
 */
function PMA_deleteColumnsFromList($field_select, $isTable=true) 
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_Message::error(
            __(
                'Central list of columns configuration Storage '
                . 'is not completely configured!'
            )
        );
    }
    $db = $_POST['db'];
    $pmadb = $GLOBALS['cfg']['Server']['pmadb'];
    $central_list_table = $GLOBALS['cfg']['Server']['central_columns'];
    $has_list = PMA_getColumnsList($db);
    $GLOBALS['dbi']->selectDb($db);
    $sync = $has_list;
    $message = true;
    $tableNotExit = "";
    $columnsNotExist = "";
    if ($isTable) {
        foreach ($field_select as $table) {
            if (isset($sync[$table])) {
                unset($sync[$table]);
            } else {
                $tableNotExit .= ", '".$table."'";
            }
        }
        if ($tableNotExit != "") {
            $tableNotExit = trim($tableNotExit, ",");
            $message = PMA_Message::notice(
                __(
                    "Couldn't remove Table(s) $tableNotExit "
                    . "as they don't exist in central columns list!"
                )
            );
        }
    } else {
        $table = $_POST['table'];
        $sync[$table] = (array)$sync[$table];
        foreach ($field_select as $column) {
            if (isset($sync[$table][$column])) {
                unset($sync[$table][$column]);
            } else {
                $columnsNotExist .= ", '".$column."'";
            }
        }
        if (!$sync[$table]) {
            unset($sync[$table]);
        }
        if ($columnsNotExist != "") {
            $columnsNotExist = trim($columnsNotExist, ",");
            $message = PMA_Message::notice(
                __(
                    "Couldn't remove Column(s) $columnsNotExist "
                    . "as they don't exist in central columns list!"
                )
            );
        }
    }
    
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    if (!$has_list && $sync) {
        $query = 'INSERT INTO ' . PMA_Util::backquote($central_list_table) . ' '
                . 'VALUES ( \'' . $db . '\' '
                . ', \'' . PMA_Util::sqlAddSlashes(json_encode($sync)) . '\' );';
        
    } else if (!$sync) {
        $query = 'DELETE FROM ' . PMA_Util::backquote($central_list_table) . ' '
                . 'WHERE db_name = \'' . $db . '\';';
    } else {
        $query = 'UPDATE ' . PMA_Util::backquote($central_list_table) . ' '
                . 'SET column_list = '
                . '\'' . PMA_Util::sqlAddSlashes(json_encode($sync)) . '\' '
                . 'WHERE db_name = \'' . $db . '\';';
    }
    if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
        $message = PMA_Message::error(__('Could not remove columns!'));
        $message->addMessage('<br /><br />');
        $message->addMessage(
            PMA_Message::rawError(
                $GLOBALS['dbi']->getError($GLOBALS['controllink'])
            )
        );
    }
    return $message;
}

/**
 * return the columns present in central list of columns for a given
 * table of a given database
 * 
 * @param string $db    given database
 * @param string $table given tabale
 * 
 * @return array columns present in central list from given table of given db.
 */
function PMA_getCentralColumnsFromTable($db, $table)
{
    $has_list = PMA_getColumnsList($db);
    if (isset($has_list[$table]) && $has_list[$table]) {
        return (array)$has_list[$table];
    } else {
        return array();
    } 
}
?>