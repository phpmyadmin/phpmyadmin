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
 * @return array list of columns present in central columns list 
 * for the given database
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
    $has_list = (array) $GLOBALS['dbi']->fetchResult($query);
    return $has_list;
}

/**
 * return the existing columns in central list among the given list of columns
 * 
 * @param string $db   the selected databse
 * @param string $cols comma seperated list of given columns
 * 
 * @return array list of columns in central columns among given set of columns
 */
function PMA_findExistingColNames($db, $cols)
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return array();
    }
    $pmadb = $cfgCentralColumns['db'];
    $GLOBALS['dbi']->selectDb($pmadb);
    $central_list_table = $cfgCentralColumns['table'];
    $query = 'SELECT col_name FROM ' . PMA_Util::backquote($central_list_table) . ' '
            . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';
    $has_list = (array) $GLOBALS['dbi']->fetchResult($query);
    return $has_list;
}

/**
 * return error message to be displayed if central columns
 * configurartion storage is not completely configured
 * 
 * @return PMA_Message
 */
function PMA_configErrorMessage()
{
    return PMA_Message::error(
        __(
            'Central list of columns configuration Storage '
            . 'is not completely configured!'
        )
    );
}

/**
 * build the insert query for central columns list given PMA storage
 * db, central_columns table, column name and corresponding definition to be added
 * 
 * @param string $column             column to add into central list
 * @param array  $def                list of attributes of the column being added
 * @param string $db                 PMA configuration storage database name
 * @param string $central_list_table central columns configuration storage table name
 * 
 * @return string query string to insert the given column
 * with definition into central list
 */
function PMA_getInsertQuery($column, $def, $db, $central_list_table)
{
    $type = isset($def['Type'])?$def['Type']:"";
    $collation = isset($def['Collation'])?$def['Collation']:"";
    $isNull = ($def['Null'] == "NO")?0:1;
    $extra = isset($def['Extra'])?$def['Extra']:"";
    $default = isset($def['Default'])?$def['Default']:"";
    $insQuery = 'INSERT INTO ' 
    . PMA_Util::backquote($central_list_table) . ' '
    . 'VALUES ( \'' . $db . '\' ,\'' . $column . '\',\'' 
    . $type . '\',\''
    . $collation . '\',\'' . $isNull . '\',\'' . $extra . '\',\'' 
    . $default . '\');';
    return $insQuery;
}

/**
 * If $isTable is true then unique columns from given tables as $field_select
 * are added to central list otherwise the $field_select is considered as 
 * list of columns and these columns are added to central list if not already added
 * 
 * @param array $field_select if $isTable is true selected tables list 
 * otherwise selected columns list
 * @param bool  $isTable      if passed array is of tables or columns
 * 
 * @return true|PMA_Message
 */
function PMA_syncUniqueColumns($field_select, $isTable=true) 
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $db = $_POST['db'];
    $pmadb = $cfgCentralColumns['db'];
    $central_list_table = $cfgCentralColumns['table'];
    $GLOBALS['dbi']->selectDb($db);
    $existingCols = array();
    $cols = "";
    $insQuery = array();
    $fields = array();
    $message = true;
    if ($isTable) {
        foreach ($field_select as $table) {
            $fields[$table] = (array) $GLOBALS['dbi']->getColumns(
                $db, $table, null, true
            );
            foreach ($fields[$table] as $field => $def) {
                $cols .= "'" . $field . "',";
            }
        }
        
        $has_list = PMA_findExistingColNames($db, trim($cols, ','));
        foreach ($field_select as $table) {
            foreach ($fields[$table] as $field => $def) {
                if (!in_array($field, $has_list)) {
                    $has_list[] = $field;
                    $insQuery[] = PMA_getInsertQuery(
                        $field, $def, $db, $central_list_table
                    );
                } else {
                    $existingCols[] = "'" . $field . "'";
                }
            }
        }
    } else {
        $table = $_POST['table'];
        foreach ($field_select as $column) {
            $cols .= "'" . $column . "',";
        }
         $has_list = PMA_findExistingColNames($db, trim($cols, ','));
        foreach ($field_select as $column) {
            if (!in_array($column, $has_list)) {
                $has_list[] = $column;
                $field = (array) $GLOBALS['dbi']->getColumns(
                    $db, $table, $column,
                    true
                );
                $insQuery[] = PMA_getInsertQuery(
                    $column, $field, $db, $central_list_table
                );
            } else {
                $existingCols[] = "'" . $column . "'";
            }
        }
    }
    if ($existingCols) {
        $existingCols = implode(",", array_unique($existingCols));
        $message = PMA_Message::notice(
            sprintf(
                __(
                    'Could not add %1$s as they already exist in central list!'
                ), $existingCols
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
    $GLOBALS['dbi']->selectDb($pmadb);
    if ($insQuery) {
        foreach ($insQuery as $query) {
            if (!$GLOBALS['dbi']->tryQuery($query)) {
                $message = PMA_Message::error(__('Could not add columns!'));
                $message->addMessage('<br /><br />');
                $message->addMessage(
                    PMA_Message::rawError(
                        $GLOBALS['dbi']->getError($GLOBALS['controllink'])
                    )
                );
                break;
            }
        }
    } 
    return $message;
}

/**
 * if $isTable is true it removes all columns of given tables as $field_select from 
 * central columns list otherwise $field_select is columns list and it removes
 * given columns if present in central list
 * 
 * @param array $field_select if $isTable selectd list of tables otherwise
 * selected list of columns to remove from central list
 * @param bool  $isTable      if passed array is of tables or columns
 * 
 * @return true|PMA_Message
 */
function PMA_deleteColumnsFromList($field_select, $isTable=true) 
{
    $cfgCentralColumns = PMA_centralColumnsGetParams();
    if (empty($cfgCentralColumns)) {
        return PMA_configErrorMessage();
    }
    $db = $_POST['db'];
    $pmadb = $cfgCentralColumns['db'];
    $central_list_table = $cfgCentralColumns['table'];
    $GLOBALS['dbi']->selectDb($db);
    $message = true;
    $colNotExist = array();
    $fields = array();
    $cols ="";
    if ($isTable) {
        foreach ($field_select as $table) {
            $fields[$table] = (array) $GLOBALS['dbi']->getColumnNames(
                $db, $table, null, true
            );
            $col = implode("','", $fields[$table]);
            $col = "'" . $col . "'";
            $cols .= $col . ",";
        }
        $cols = trim($cols, ',');
        $has_list = PMA_findExistingColNames($db, $cols);
        foreach ($field_select as $table) {
            foreach ($fields[$table] as $column) {
                if (!in_array($column, $has_list)) {
                    $colNotExist[] = "'" . $column . "'";
                }
            }
        }
        
    } else {
        $cols = implode("','", $field_select);
        $cols = "'" . $cols . "'";
        $has_list = PMA_findExistingColNames($db, $cols);
        foreach ($field_select as $column) {
            if (!in_array($column, $has_list)) {
                $colNotExist[] = "'" . $column . "'";
            }
        }
    }
    if ($colNotExist) {
            $colNotExist = implode(",", array_unique($colNotExist));
            $message = PMA_Message::notice(
                sprintf(
                    __(
                        'Couldn\'t remove Column(s) %1$s '
                        . 'as they don\'t exist in central columns list!'
                    ), $colNotExist
                )
            );
    }
    $GLOBALS['dbi']->selectDb($pmadb, $GLOBALS['controllink']);
  
    $query = 'DELETE FROM ' . PMA_Util::backquote($central_list_table) . ' '
                . 'WHERE db_name = \'' . $db . '\' AND col_name IN (' . $cols . ');';
    
    if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['controllink'])) {
        $message = PMA_Message::error(__('Could not remove columns!'));
        $message->addMessage('<br />' . $cols . '<br />');
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
    $GLOBALS['dbi']->selectDb($db);
    $fields = (array) $GLOBALS['dbi']->getColumnNames($db, $table, null, true);
    $cols = implode("','", $fields);
    $cols = "'" . $cols . "'";
    $has_list = PMA_findExistingColNames($db, $cols);
    if (isset($has_list) && $has_list) {
        return (array)$has_list;
    } else {
        return array();
    } 
}
?>