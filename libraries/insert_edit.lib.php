<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */


/**
 * phpmyadmin edit row or insert
 * 
 * @param array $paramArray
 * @param boolean $found_unique_key 
 * @return array
 */
function PMA_loadAllSelectedRowInEditMode($paramArray, $found_unique_key)
{
    list($rows, $table, $db) = $paramArray;
    if (isset($_REQUEST['where_clause'])) {
        $where_clause_array = PMA_getWhereClauseArray();
        list($whereClauses, $resultArray, $rowsArray) = PMA_whereClausesAnalyses($where_clause_array, $paramArray, $found_unique_key);
        return array(false, $whereClauses, $resultArray, $rowsArray, $where_clause_array);
    } else {
        list($results, $row) = PMA_loadFirstRowInEditMode($paramArray);
        return array(true, null, $results, $row, null);
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
 * When in edit mode load all selected rows from table
 * 
 * @param array $where_clause_array
 * @param array $paramArray
 * @param boolean $found_unique_key 
 * @return array $where_clauses, $result, $rows
 */
function PMA_whereClausesAnalyses($where_clause_array, $paramArray, $found_unique_key)
{
    list($rows, $table, $db) = $paramArray;
    $result             = array();
    $where_clauses      = array();
    foreach ($where_clause_array as $key_id => $where_clause) {
        $local_query           = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table)
                                 . ' WHERE ' . $where_clause . ';';
        $result[$key_id]       = PMA_DBI_query($local_query, null, PMA_DBI_QUERY_STORE);
        $rows[$key_id]         = PMA_DBI_fetch_assoc($result[$key_id]);
        $where_clauses[$key_id] = str_replace('\\', '\\\\', $where_clause);
        PMA_noRowReturnInEditMode($rows, $key_id, $where_clause_array, $local_query, $result, $found_unique_key);
    }
    return array($where_clauses, $result, $rows);
}

/**
 *
 * @param array $rows
 * @param string $key_id
 * @param array $where_clause_array
 * @param string $local_query
 * @param array $result
 * @param boolean $found_unique_key 
 */
function PMA_noRowReturnInEditMode($rows, $key_id, $where_clause_array, $local_query, $result, $found_unique_key)
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
}

/**
 * No primary key given, just load first row
 * 
 * @param array $paramArray
 * @return array 
 */
function PMA_loadFirstRowInEditMode($paramArray )
{
    list($rows, $table, $db) = $paramArray;
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
 *
 * @param array $url_params
 * @return string 
 */
function PMA_showFunctionFieldsInEditMode($url_params)
{
    $params = array(
            'ShowFunctionFields' => 1,
            'ShowFieldTypesInDataEditView' => $GLOBALS['cfg']['ShowFieldTypesInDataEditView'],
            'goto' => 'sql.php');
    $this_url_params = array_merge($url_params, $params);
    return ' : <a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '">' . __('Function') . '</a>' . "\n";
}

/**
 *
 * @param array $url_params
 * @return stirng 
 */
function PMA_showFieldTypesInDataEditView($url_params)
{
    $params = array(
            'ShowFieldTypesInDataEditView' => 1,
            'ShowFunctionFields' => $GLOBALS['cfg']['ShowFunctionFields'],
            'goto' => 'sql.php');
    $this_other_url_params = array_merge($url_params, $params);
    return ' : <a href="tbl_change.php' . PMA_generate_common_url($this_other_url_params) . '">' . __('Type') . '</a>' . "\n";
}
 
 /**
  *
  * @param array $url_params
  * @return string 
  */
 function PMA_fieldTypesInDataEditView($url_params)
 {
     $params = array(
                'ShowFieldTypesInDataEditView' => 0,
                'ShowFunctionFields' => $GLOBALS['cfg']['ShowFunctionFields'],
                'goto' => 'sql.php'
                );
     $this_url_params = array_merge($url_params, $params);
     return '          <th><a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '" title="' . __('Hide') . '">' . __('Type') . '</a></th>' . "\n";
 }
 
 /**
  *
  * @param array $url_params
  * @return string 
  */
 function PMA_functionFfiledsInEditView($url_params)
 {
     $params = array(
                'ShowFieldTypesInDataEditView' => 0,
                'ShowFunctionFields' => $GLOBALS['cfg']['ShowFunctionFields'],
                'goto' => 'sql.php'
                );
     $this_url_params = array_merge($url_params, $params);
     return '          <th><a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '" title="' . __('Hide') . '">' . __('Type') . '</a></th>' . "\n";
 }
?>
