<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions with the insert/edit features in pma
 * 
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Retrieve the values for pma edit mode
 * 
 * @param array $paramArray
 * @param boolean $found_unique_key 
 * @return array
 */
function PMA_getValuesForEditMode($paramArray)
{
    $found_unique_key = false;
    list($table, $db) = $paramArray;
    if (isset($_REQUEST['where_clause'])) {
        $where_clause_array = PMA_getWhereClauseArray();
        list($whereClauses, $resultArray, $rowsArray, $found_unique_key) 
                = PMA_analyzeWhereClauses($where_clause_array, $paramArray, $found_unique_key);
        return array(false, $whereClauses, $resultArray, $rowsArray, $where_clause_array, $found_unique_key);
    } else {
        list($results, $row) = PMA_loadFirstRowInEditMode($paramArray);
        return array(true, null, $results, $row, null, $found_unique_key);
    }
}

/**
 *
 * @return whereClauseArray 
 */
function PMA_getWhereClauseArray()
{
    if(isset ($_REQUEST['where_clause'])) {
        if (is_array($_REQUEST['where_clause'])) {
            return $_REQUEST['where_clause'];
        } else {
            return array(0 => $_REQUEST['where_clause']);
        }
    }
}

/**
 * Analysing where cluases array
 * 
 * @param array $where_clause_array
 * @param array $paramArray
 * @param boolean $found_unique_key 
 * @return array $where_clauses, $result, $rows
 */
function PMA_analyzeWhereClauses($where_clause_array, $paramArray, $found_unique_key)
{
    list($table, $db) = $paramArray;
    $rows               = array();
    $result             = array();
    $where_clauses      = array();
    foreach ($where_clause_array as $key_id => $where_clause) {
        $local_query           = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table)
                                 . ' WHERE ' . $where_clause . ';';
        $result[$key_id]       = PMA_DBI_query($local_query, null, PMA_DBI_QUERY_STORE);
        $rows[$key_id]         = PMA_DBI_fetch_assoc($result[$key_id]);
        $where_clauses[$key_id] = str_replace('\\', '\\\\', $where_clause);
        $found_unique_key = PMA_showEmptyResultMessageOrSetUniqueCondition($rows, $key_id, $where_clause_array, $local_query, $result, $found_unique_key);
    }
    return array($where_clauses, $result, $rows, $found_unique_key);
}

/**
 * Show message for empty reult or set the unique_condition 
 * 
 * @param array $rows
 * @param string $key_id
 * @param array $where_clause_array
 * @param string $local_query
 * @param array $result
 * @param boolean $found_unique_key 
 */
function PMA_showEmptyResultMessageOrSetUniqueCondition($rows, $key_id, $where_clause_array, $local_query, $result, $found_unique_key)
{
    // No row returned
    if (! $rows[$key_id]) {
        unset($rows[$key_id], $where_clause_array[$key_id]);
        PMA_showMessage(__('MySQL returned an empty result set (i.e. zero rows).'), $local_query);
        echo "\n";
        include 'libraries/footer.inc.php';
    } else {// end if (no row returned) 
        $meta = PMA_DBI_get_fields_meta($result[$key_id]);
        list($unique_condition, $tmp_clause_is_unique)
            = PMA_getUniqueCondition($result[$key_id], count($meta), $meta, $rows[$key_id], true);
        if (! empty($unique_condition)) {
            $found_unique_key = true;
        }
        unset($unique_condition, $tmp_clause_is_unique);
    }
    return $found_unique_key;
}

/**
 * No primary key given, just load first row
 * 
 * @param array $paramArray
 * @return array 
 */
function PMA_loadFirstRowInEditMode($paramArray )
{
    list($table, $db) = $paramArray;
    $result = PMA_DBI_query(
        'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . ' LIMIT 1;',
        null,
        PMA_DBI_QUERY_STORE
    );
    $rows = array_fill(0, $GLOBALS['cfg']['InsertRows'], false);
    return array($result, $rows);
}

/**
 * Add some url parameters
 * 
 * @param array $url_params
 * @return array 
 */
function PMA_urlParamsInEditMode($url_params) 
{
    if (isset($_REQUEST['where_clause'])) {
        $url_params['where_clause'] = trim($_REQUEST['where_clause']);
    }
    if (! empty($_REQUEST['sql_query'])) {
        $url_params['sql_query'] = $_REQUEST['sql_query'];
    }
    return $url_params;
}

/**
 * Show function fields in data edit view in pma
 * 
 * @param array $url_params
 * @return string 
 */
function PMA_showFunctionFieldsInEditMode($url_params, $showFuncFields)
{
    if(!$showFuncFields) {
        $params = array('ShowFunctionFields' => 1);
    } else {
        $params = array('ShowFunctionFields' => 0);
    }
    $params = array(
            'ShowFieldTypesInDataEditView' => $GLOBALS['cfg']['ShowFieldTypesInDataEditView'],
            'goto' => 'sql.php');
    $this_url_params = array_merge($url_params, $params);
    if(!$showFuncFields) {
        return ' : <a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '">' . __('Function') . '</a>' . "\n";
    }
    return '          <th><a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '" title="' . __('Hide') . '">' . __('Function') . '</a></th>' . "\n";
}

/**
 * Show field types in data edit view in pma
 * 
 * @param array $url_params
 * @return stirng 
 */
function PMA_showColumnTypesInDataEditView($url_params, $showColumnType )
{
    if(!$showColumnType) {
        $params = array('ShowFieldTypesInDataEditView' => 1);
    } else {
        $params = array('ShowFieldTypesInDataEditView' => 0);
    }
    $params = array(
            'ShowFunctionFields' => $GLOBALS['cfg']['ShowFunctionFields'],
            'goto' => 'sql.php');
    $this_other_url_params = array_merge($url_params, $params);
    if(!$showColumnType) {
        return ' : <a href="tbl_change.php' . PMA_generate_common_url($this_other_url_params) . '">' . __('Type') . '</a>' . "\n";
    }
    return '          <th><a href="tbl_change.php' . PMA_generate_common_url($this_other_url_params) . '" title="' . __('Hide') . '">' . __('Type') . '</a></th>' . "\n";
    
}

/**
 * Retrieve the default for datetime data type
 * 
 * @param array $table_fields 
 */
 function PMA_getDefaultForDatetime($table_fields)
 {
    // d a t e t i m e
    //
    // Current date should not be set as default if the field is NULL
    // for the current row, but do not put here the current datetime
    // if there is a default value (the real default value will be set
    // in the Default value logic below)

    // Note: (tested in MySQL 4.0.16): when lang is some UTF-8,
    // $field['Default'] is not set if it contains NULL:
    // Array ([Field] => d [Type] => datetime [Null] => YES [Key] => [Extra] => [True_Type] => datetime)
    // but, look what we get if we switch to iso: (Default is NULL)
    // Array ([Field] => d [Type] => datetime [Null] => YES [Key] => [Default] => [Extra] => [True_Type] => datetime)
    // so I force a NULL into it (I don't think it's possible
    // to have an empty default value for DATETIME)
    // then, the "if" after this one will work
    if ($table_fields['Type'] == 'datetime'
        && ! isset($table_fields['Default'])
        && isset($table_fields['Null'])
        && $table_fields['Null'] == 'YES'
    ) {
        $table_fields['Default'] = null;
      }
 }
 
?>
