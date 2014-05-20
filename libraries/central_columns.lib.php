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
    $sync_tmp = array();
    if ($isTable) {
        foreach ($field_select as $table) {
            $fields = (array) $GLOBALS['dbi']->getColumns($db, $table, null, true);
            foreach ($fields as $field => $def) {
                $sync_tmp[$field] = $def['Type'] . " " . $def['key'] . " " .
                    $def['collation'] . " " . $def["Null"] . " " . $def['Extra'];
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
                //print_r($field);
                $sync_tmp[$column] = $field['Type'] . " " . $field['key'] . " " .
                    $field['collation'] . " " . $field["Null"] 
                        . " " . $field['Extra'];
        }
            $sync[$table] = $sync_tmp;
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
        return $message;
    }
    return true;
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
    if ($isTable) {
        foreach ($field_select as $table) {
            unset($sync[$table]);
        }
    } else {
        $table = $_POST['table'];
        $sync[$table] = (array)$sync[$table];
        foreach ($field_select as $column) {
            unset($sync[$table][$column]);
        }
        if (!$sync[$table]) {
            unset($sync[$table]);
        }
    }
    
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
    if (!$has_list) {
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
        return $message;
    }
    return true;
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
    if ($has_list[$table]) {
        return (array)$has_list[$table];
    } else {
        return array();
    } 
}
?>