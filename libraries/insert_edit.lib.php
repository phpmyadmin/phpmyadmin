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
 * Retrieve form parameters for insert/edit form
 * 
 * @param array $where_clauses
 * @param array $where_clause_array
 * @return array $_form_params
 */
function PMA_getFormParametersForInsertForm($paramArray, $where_clauses, $where_clause_array, $err_url)
{
    list($table, $db) = $paramArray;
    $_form_params = array(
    'db'        => $db,
    'table'     => $table,
    'goto'      => $GLOBALS['goto'],
    'err_url'   => $err_url,
    'sql_query' => $_REQUEST['sql_query'],
    );
    if (isset($where_clauses)) {
        foreach ($where_clause_array as $key_id => $where_clause) {
            $_form_params['where_clause[' . $key_id . ']'] = trim($where_clause);
        }
    }
    if (isset($_REQUEST['clause_is_unique'])) {
        $_form_params['clause_is_unique'] = $_REQUEST['clause_is_unique'];
    }
    return $_form_params;
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
    $params = array();
    if(! $showFuncFields) {
        $params['ShowFunctionFields'] = 1;
    } else {
        $params['ShowFunctionFields'] = 0;
    }
    $params['ShowFieldTypesInDataEditView'] = $GLOBALS['cfg']['ShowFieldTypesInDataEditView'];
    $params['goto'] = 'sql.php';
    $this_url_params = array_merge($url_params, $params);
    if(! $showFuncFields) {
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
    $params = array();
    if(! $showColumnType) {
        $params['ShowFieldTypesInDataEditView'] = 1;
    } else {
        $params['ShowFieldTypesInDataEditView'] = 0;
    }
    $params['ShowFunctionFields'] = $GLOBALS['cfg']['ShowFunctionFields'];
    $params['goto'] = 'sql.php';
    $this_other_url_params = array_merge($url_params, $params);
    if(! $showColumnType) {
        return ' : <a href="tbl_change.php' . PMA_generate_common_url($this_other_url_params) . '">' . __('Type') . '</a>' . "\n";
    }
    return '          <th><a href="tbl_change.php' . PMA_generate_common_url($this_other_url_params) . '" title="' . __('Hide') . '">' . __('Type') . '</a></th>' . "\n";
    
}

/**
 * Retrieve the default for datetime data type
 * 
 * @param array $table_fields 
 */
function PMA_getDefaultForDatetime($column)
{
    // d a t e t i m e
    //
    // Current date should not be set as default if the field is NULL
    // for the current row, but do not put here the current datetime
    // if there is a default value (the real default value will be set
    // in the Default value logic below)

    // Note: (tested in MySQL 4.0.16): when lang is some UTF-8,
    // $column['Default'] is not set if it contains NULL:
    // Array ([Field] => d [Type] => datetime [Null] => YES [Key] => [Extra] => [True_Type] => datetime)
    // but, look what we get if we switch to iso: (Default is NULL)
    // Array ([Field] => d [Type] => datetime [Null] => YES [Key] => [Default] => [Extra] => [True_Type] => datetime)
    // so I force a NULL into it (I don't think it's possible
    // to have an empty default value for DATETIME)
    // then, the "if" after this one will work
    if ($column['Type'] == 'datetime'
        && ! isset($column['Default'])
        && isset($column['Null'])
        && $column['Null'] == 'YES'
    ) {
        $column['Default'] = null;
      }
}
 
 /**
  * Analyze the table column array
  * 
  * @param array $column
  * @param array $comments_map
  * @return type 
  */
function PMA_analyzeTableColumnsArray($column, $comments_map, $timestamp_seen)
{
    $column['Field_html']    = htmlspecialchars($column['Field']);
    $column['Field_md5']     = md5($column['Field']);
    // True_Type contains only the type (stops at first bracket)
    $column['True_Type']     = preg_replace('@\(.*@s', '', $column['Type']);
    PMA_getDefaultForDatetime($column);
    $column['len']           = preg_match('@float|double@', $column['Type']) ? 100 : -1;
    $column['Field_title']   = PMA_getColumnTitle($column, $comments_map);
    $column['is_binary']     = PMA_isColumnBinary($column);
    $column['is_blob']       = PMA_isColumnBlob($column);
    $column['is_char']       = PMA_isColumnChar($column);
    list($column['pma_type'], $column['wrap'], $column['first_timestamp']) = 
            PMA_getEnumSetAndTimestampColumns($column, $timestamp_seen);
    
    return $column;
}
 
 /**
  * Retrieve the column title
  * 
  * @param array $column
  * @param array $comments_map
  * @return string 
  */
function PMA_getColumnTitle($column, $comments_map)
{
    if (isset($comments_map[$column['Field']])) {
        return '<span style="border-bottom: 1px dashed black;" title="'
            . htmlspecialchars($comments_map[$column['Field']]) . '">'
            . $column['Field_html'] . '</span>';
    } else {
            return $column['Field_html'];
    }
}
 
 /**
  * check is table column bainary
  * 
  * @param array $column
  * @return boolean 
  */
function PMA_isColumnBinary($column)
{
    // The type column.
    // Fix for bug #3152931 'ENUM and SET cannot have "Binary" option'
    // If check to ensure types such as "enum('one','two','binary',..)" or
    // "enum('one','two','varbinary',..)" are not categorized as binary.
    if (stripos($column['Type'], 'binary') === 0
        || stripos($column['Type'], 'varbinary') === 0
    ) {
        return stristr($column['Type'], 'binary');
    } else {
        return false;
    }
     
}
 
 /**
  * check is table column blob
  * 
  * @param array $column
  * @return boolean 
  */
function PMA_isColumnBlob($column)
{
    // If check to ensure types such as "enum('one','two','blob',..)" or
    // "enum('one','two','tinyblob',..)" etc. are not categorized as blob.
    if (stripos($column['Type'], 'blob') === 0
        || stripos($column['Type'], 'tinyblob') === 0
        || stripos($column['Type'], 'mediumblob') === 0
        || stripos($column['Type'], 'longblob') === 0
    ) {
        return stristr($column['Type'], 'blob');
    } else {
        return false;
    }
}

/**
 * check is table column char
 * 
 * @param array $column
 * @return boolean 
 */
function PMA_isColumnChar($column)
{
    // If check to ensure types such as "enum('one','two','char',..)" or
    // "enum('one','two','varchar',..)" are not categorized as char.
    if (stripos($column['Type'], 'char') === 0
        || stripos($column['Type'], 'varchar') === 0
    ) {
        return stristr($column['Type'], 'char');
    } else {
        return false;
    }
}
/**
 * Retieve set, enum, timestamp table columns
 * 
 * @param array $column
 * @param int $timestamp_seen 
 */
function PMA_getEnumSetAndTimestampColumns($column, $timestamp_seen)
{
    $column['first_timestamp'] = false;
    switch ($column['True_Type']) {
    case 'set':
        $column['pma_type'] = 'set';
        $column['wrap']  = '';
        break;
    case 'enum':
        $column['pma_type'] = 'enum';
        $column['wrap']  = '';
        break;
    case 'timestamp':
        if (!$timestamp_seen) {   // can only occur once per table
            $timestamp_seen  = 1;
            $column['first_timestamp'] = true;
        }
        $column['pma_type'] = $column['Type'];
        $column['wrap']  = ' nowrap';
        break;

    default:
        $column['pma_type'] = $column['Type'];
        $column['wrap']  = ' nowrap';
        break;
    }
    return array($column['pma_type'], $column['wrap'], $column['first_timestamp']);
}

/**
 * The function column
 * We don't want binary data to be destroyed
 * Note: from the MySQL manual: "BINARY doesn't affect how the column is
 *       stored or retrieved" so it does not mean that the contents is binary
 * 
 * @param array $column
 * @param boolean $is_upload
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param array $no_support_types
 * @param integer $tabindex_for_function
 * @param integer $tabindex
 * @param integer $idindex
 * @param boolean $insert_mode
 * @return string 
 */
function PMA_getFunctionColumn($column, $is_upload, $column_name_appendix,
    $unnullify_trigger, $no_support_types, $tabindex_for_function,
    $tabindex, $idindex, $insert_mode
) {                
    $html_output = '';
    if (($GLOBALS['cfg']['ProtectBinary'] && $column['is_blob'] && !$is_upload)
        || ($GLOBALS['cfg']['ProtectBinary'] == 'all' && $column['is_binary'])
        || ($GLOBALS['cfg']['ProtectBinary'] == 'noblob' && !$column['is_blob'])
    ) {
        $html_output .= '        <td class="center">' . __('Binary') . '</td>' . "\n";
    } elseif (strstr($column['True_Type'], 'enum')
        || strstr($column['True_Type'], 'set')
        || in_array($column['pma_type'], $no_support_types)
    ) {
        $html_output .= '        <td class="center">--</td>' . "\n";
    } else {
        $html_output .= '<td>' . "\n";
        $html_output .= '<select name="funcs' . $column_name_appendix . '"' . $unnullify_trigger 
            . 'tabindex="' . ($tabindex + $tabindex_for_function) . '" id="field_' . $idindex . '_1">';
        $html_output .= PMA_getFunctionsForField($column, $insert_mode) . "\n";
        $html_output .= '</select>' .  "\n";
        $html_output .= '</td>' .  "\n";
    }
    return $html_output;
}

/**
 * The null column
 * 
 * @param array $column
 * @param string $column_name_appendix
 * @param array $real_null_value
 * @param integer $tabindex
 * @param integer $tabindex_for_null
 * @param integer $idindex
 * @param array $vkey
 * @param array $foreigners
 * @param array $foreignData
 * @return string 
 */
function PMA_getNullColumn($column, $column_name_appendix, $real_null_value,
    $tabindex, $tabindex_for_null, $idindex, $vkey, $foreigners, $foreignData
) {
    $html_output = '';
    $html_output .= '        <td>' . "\n";
    if ($column['Null'] == 'YES') {
        $html_output .= '            <input type="hidden" name="fields_null_prev' . $column_name_appendix . '"';
        if ($real_null_value && !$column['first_timestamp']) {
            $html_output .= ' value="on"';
        }
        $html_output .= ' />' . "\n";

        $html_output .= '            <input type="checkbox" class="checkbox_null" tabindex="' . ($tabindex + $tabindex_for_null) . '"'
             . ' name="fields_null' . $column_name_appendix . '"';
        if ($real_null_value && !$column['first_timestamp']) {
            $html_output .= ' checked="checked"';
        }
        $html_output .= ' id="field_' . ($idindex) . '_2" />';

        // nullify_code is needed by the js nullify() function
        $nullify_code = PMA_getNullifyCodeForNullColumn($column, $foreigners, $foreignData);
        // to be able to generate calls to nullify() in jQuery
        $html_output .= '<input type="hidden" class="nullify_code" name="nullify_code'
            . $column_name_appendix . '" value="' . $nullify_code . '" />';
        $html_output .= '<input type="hidden" class="hashed_field" name="hashed_field'
            . $column_name_appendix . '" value="' .  $column['Field_md5'] . '" />';
        $html_output .= '<input type="hidden" class="multi_edit" name="multi_edit'
            . $column_name_appendix . '" value="' . PMA_escapeJsString($vkey) . '" />';
    }
    $html_output .= '        </td>' . "\n";

    return $html_output;
}

/**
 * Retrieve the nullify code for the null column
 * 
 * @param array $column
 * @param array $foreigners
 * @param array $foreignData
 * @return integer 
 */
function PMA_getNullifyCodeForNullColumn($column, $foreigners, $foreignData)
{
    if (strstr($column['True_Type'], 'enum')) {
        if (strlen($column['Type']) > 20) {
            $nullify_code = '1';
        } else {
            $nullify_code = '2';
        }
    } elseif (strstr($column['True_Type'], 'set')) {
        $nullify_code = '3';
    } elseif ($foreigners && isset($foreigners[$column['Field']]) && $foreignData['foreign_link'] == false) {
        // foreign key in a drop-down
        $nullify_code = '4';
    } elseif ($foreigners && isset($foreigners[$column['Field']]) && $foreignData['foreign_link'] == true) {
        // foreign key with a browsing icon
        $nullify_code = '6';
    } else {
        $nullify_code = '5';
    }
    return $nullify_code;
}

/**
 * Get the HTML elements for value column in inert form
 * 
 * @param array $column
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param array $data
 * @param array $special_chars
 * @param array $foreignData
 * @param array $paramTableDbArray
 * @param array $rownumber_param
 * @param array $titles
 * @param array $text_dir
 * @param array $special_chars_encoded
 * @param integer $biggest_max_file_size
 * @param string $default_char_editing
 * @param array $no_support_types
 * @param array $gis_data_types
 * @return type 
 */
function PMA_getValueColumn($column, $backup_field, $column_name_appendix,
    $unnullify_trigger,$tabindex, $tabindex_for_value, $idindex, $data,
    $special_chars, $foreignData, $odd_row, $paramTableDbArray,$rownumber_param,
    $titles, $text_dir, $special_chars_encoded, $vkey,$is_upload,$biggest_max_file_size,
    $default_char_editing, $no_support_types, $gis_data_types, $extracted_columnspec
) {
    $html_output = '';
    
    if ($foreignData['foreign_link'] == true) {
        $html_output .= PMA_getForeignLink($column, $backup_field, $column_name_appendix,
            $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex, $data,
            $paramTableDbArray, $rownumber_param, $titles
            );
        
    } elseif (is_array($foreignData['disp_row'])) {
        $html_output .= PMA_dispRowForeignData($backup_field, $column_name_appendix,
            $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex, $data, $foreignData
            );
        
    } elseif ($GLOBALS['cfg']['LongtextDoubleTextarea'] && strstr($column['pma_type'], 'longtext')) {
        $html_output = '&nbsp;</td>';
        $html_output .= '</tr>';
        $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">'
            . '<td colspan="5" class="right">';
        $html_output .= PMA_getTextarea($column,$backup_field, $column_name_appendix, $unnullify_trigger,
            $tabindex, $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded
            );
        
    } elseif (strstr($column['pma_type'], 'text')) {
        $html_output .= PMA_getTextarea($column,$backup_field, $column_name_appendix, $unnullify_trigger,
            $tabindex, $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded
            );
        $html_output .= "\n";
        if (strlen($special_chars) > 32000) {
            $html_output .= "        </td>\n";
            $html_output .= '        <td>' . __('Because of its length,<br /> this column might not be editable');
        }
        
    } elseif ($column['pma_type'] == 'enum') {
        $html_output .= PMA_getPmaTypeEnum($paramsArrayForColumns, $column,$extracted_columnspec);
        
    } elseif ($column['pma_type'] == 'set') {
        $html_output .= PMA_getPmaTypeSet($column,$extracted_columnspec, $backup_field,
            $column_name_appendix, $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex);
        
    } elseif ($column['is_binary'] || $column['is_blob']) {
        $html_output .= PMA_getBinaryAndBlobColumn($column, $data, $special_chars,$biggest_max_file_size,
             $backup_field,$column_name_appendix, $unnullify_trigger, $tabindex, $tabindex_for_value,
            $idindex, $text_dir, $special_chars_encoded, $vkey, $is_upload);
        
    } elseif (! in_array($column['pma_type'], $no_support_types)) {
        $html_output .= PMA_getNoSupportTypes($column, $default_char_editing,$backup_field,
            $column_name_appendix, $unnullify_trigger,$tabindex,$special_chars,
            $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded, $data, $extracted_columnspec);  
    }
    
    if (in_array($column['pma_type'], $gis_data_types)) {
        $html_output .= PMA_getHTMLforGisDataTypes($vrow, $column);
    }
    
    return $html_output;
}

/**
 * Get HTML for foreign link in insert form
 * 
 * @param array $column
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param array $data
 * @param array $paramTableDbArray
 * @param array $rownumber_param
 * @param array $titles
 * @return string 
 */
function PMA_getForeignLink($column, $backup_field, $column_name_appendix,
    $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex, $data,
    $paramTableDbArray, $rownumber_param, $titles
) {
    list($db, $table) = $paramTableDbArray;
    $html_output = '';
    $html_output .= $backup_field . "\n";
    $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix . '" value="foreign" />';
    $html_output .= '<input type="text" name="fields' . $column_name_appendix . '"'
        . 'class="textfield" ' . $unnullify_trigger
        . 'tabindex="' . ($tabindex + $tabindex_for_value) . '"'
        . 'id="field_' . ($idindex) . '_3"'
        . 'value="' . htmlspecialchars($data) . '" />'
        . '<a class="hide foreign_values_anchor" target="_blank" onclick="window.open(this.href,'
        . '\'foreigners\', \'width=640,height=240,scrollbars=yes,resizable=yes\'); return false;" href="browse_foreigners.php?'
        . PMA_generate_common_url($db, $table) . '&amp;field='
        . PMA_escapeJsString(urlencode($column['Field']) . $rownumber_param) . '">'
        . str_replace("'", "\'", $titles['Browse']) . '</a>';
    return $html_output;
}

/**
 * Get HTML to display foreign data
 * 
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param array $data
 * @param array $foreignData
 * @return string 
 */
function PMA_dispRowForeignData($backup_field, $column_name_appendix,
    $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex, $data, $foreignData
) {
    $html_output = '';
    $html_output .= $backup_field . "\n";
    $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix . '" value="foreign" />'
        . '<select name="fields' . $column_name_appendix . '"'
        . $unnullify_trigger
        . 'class="textfield"' . ($tabindex + $tabindex_for_value). '"'
        . 'id="field_' . $idindex . '_3"'
        . PMA_foreignDropdown($foreignData['disp_row'], $foreignData['foreign_field'],
                $foreignData['foreign_display'], $data, $GLOBALS['cfg']['ForeignKeyMaxLimit'])
        . '</select>';
    
    return $html_output;
}

/**
 * Get HTML textarea for insert form
 * 
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param array $text_dir
 * @param array $special_chars_encoded
 * @return string 
 */
function PMA_getTextarea($column, $backup_field, $column_name_appendix, $unnullify_trigger,
    $tabindex, $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded
) {
    $the_class = '';
    $textAreaRows = $GLOBALS['cfg']['TextareaRows'];
    $textareaCols = $GLOBALS['cfg']['TextareaCols'];
    
    if($column['is_char']) {
        $the_class = 'char';
        $textAreaRows = $GLOBALS['cfg']['CharTextareaRows'];
        $textareaCols = $GLOBALS['cfg']['CharTextareaCols'];
    } elseif(($GLOBALS['cfg']['LongtextDoubleTextarea'] && strstr($column['pma_type'], 'longtext'))) {
        $textAreaRows = $GLOBALS['cfg']['TextareaRows']*2;
        $textareaCols = $GLOBALS['cfg']['TextareaCols']*2;
    }
    $html_output = $backup_field . "\n"
        . '<textarea name="fields' . $column_name_appendix . '"'
        . 'class="' . $the_class . '"'
        . 'rows="' . $textAreaRows . '"'
        . 'cols="' . $textareaCols . '"'
        . 'dir="' . $text_dir . '"'
        . 'id="field_' . ($idindex) . '_3"'
        . $unnullify_trigger
        . 'tabindex="' . ($tabindex + $tabindex_for_value) . '">'
        . $special_chars_encoded
        . '</textarea>';
    
    return $html_output;
}

/**
 * Get HTML for enum type
 * 
 * @param array $column
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param array $extracted_columnspec
 * @return string 
 */
function PMA_getPmaTypeEnum($column, $backup_field, $column_name_appendix, $extracted_columnspec)
{
    $html_output = ''; 
    if (! isset($column['values'])) {
         $column['values'] = PMA_getColumnEnumValues($column, $extracted_columnspec);         
     }
     $column_enum_values = $column['values'];
     $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix. '" value="enum" />';
     $html_output .= '<input type="hidden" name="fields' . $column_name_appendix . '" value="" />';
     $html_output .= "\n" . '            ' . $backup_field . "\n";
     if (strlen($column['Type']) > 20) {
         $html_output .= PMA_showDropDownDependOnLength($column, $column_name_appendix, $unnullify_trigger,
            $tabindex, $tabindex_for_value, $idindex, $data, $column_enum_values
            );
     } else {
         $html_output .= PMA_showRadioButtonDependOnLength($column_name_appendix, $unnullify_trigger,
            $tabindex, $column, $tabindex_for_value, $idindex, $data, $column_enum_values
            );
     }
     return $html_output;
}

/**
 * Get column values
 * 
 * @param array $column
 * @param array $extracted_columnspec
 * @return array 
 */
function PMA_getColumnEnumValues($column, $extracted_columnspec)
{
    $column['values'] = array();
    foreach ($extracted_columnspec['enum_set_values'] as $val) {
        // Removes automatic MySQL escape format
        $val = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $val));
        $column['values'][] = array(
                            'plain' => $val,
                            'html'  => htmlspecialchars($val),
                            );
    }
    return $column['values'];
}

/**
 * Get HTML drop down for more than 20 string length
 * 
 * @param array $column
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param array $data
 * @param array $column_enum_values
 * @return string 
 */
function PMA_showDropDownDependOnLength($column, $column_name_appendix, $unnullify_trigger,
    $tabindex, $tabindex_for_value, $idindex, $data, $column_enum_values
) {
    $html_output = '<select name="fields' . $column_name_appendix . '"'
        . $unnullify_trigger
        . 'class="textfield"'
        . 'tabindex="' . ($tabindex + $tabindex_for_value) . '"'
        . 'id="field_' . ($idindex) . '_3">'
        . '<option value="">&nbsp;</option>' . "\n";
    
    foreach ($column_enum_values as $enum_value) {
        $html_output .= '                ';
        $html_output .= '<option value="' . $enum_value['html'] . '"';
        if ($data == $enum_value['plain']
            || ($data == ''
                && (! isset($_REQUEST['where_clause']) || $column['Null'] != 'YES')
                && isset($column['Default'])
                && $enum_value['plain'] == $column['Default'])
        ) {
            $html_output .= ' selected="selected"';
        }
        $html_output .= '>' . $enum_value['html'] . '</option>' . "\n";
    }
    $html_output .= '</select>';
    return $html_output;
}

/**
 * Get HTML radio button for less than 20 string length
 * 
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param array $column
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param array $data
 * @param array $column_enum_values
 * @return string 
 */
function PMA_showRadioButtonDependOnLength($column_name_appendix, $unnullify_trigger,
    $tabindex, $column, $tabindex_for_value, $idindex, $data, $column_enum_values
) {
    $j = 0;
    $html_output = '';
    foreach ($column_enum_values as $enum_value) {
        $html_output .= '            '
            . '<input type="radio" name="fields' . $column_name_appendix . '"'
            . ' class="textfield"'
            . ' value="' . $enum_value['html'] . '"'
            . ' id="field_' . ($idindex) . '_3_'  . $j . '"'
            . $unnullify_trigger;
        if ($data == $enum_value['plain']
            || ($data == ''
                && (! isset($_REQUEST['where_clause']) || $column['Null'] != 'YES')
                && isset($column['Default'])
                && $enum_value['plain'] == $column['Default'])
        ) {
            $html_output .= ' checked="checked"';
        }
        $html_output .= ' tabindex="' . ($tabindex + $tabindex_for_value) . '" />';
        $html_output .= '<label for="field_' . $idindex . '_3_' . $j . '">'
            . $enum_value['html'] . '</label>' . "\n";
        $j++;
    }
    return $html_output;
}

/**
 * Get the HTML for 'set' pma type
 * 
 * @param array $column
 * @param array $extracted_columnspec
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @return string 
 */
function PMA_getPmaTypeSet($column,$extracted_columnspec, $backup_field,
    $column_name_appendix, $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex
) {
    list($column_set_values, $select_size) = PMA_getColumnSetValueAndSelectSize($column, $extracted_columnspec);
    $vset = array_flip(explode(',', $data));
    $html_output = $backup_field . "\n";
    $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix . '" value="set" />';
    $html_output .= '<select name="fields' . $column_name_appendix . '[]' . '"'
        . 'class="textfield"'
        . 'size="' . $select_size . '"'
        . 'multiple="multiple"' . $unnullify_trigger
        . 'tabindex="' . ($tabindex + $tabindex_for_value) . '"'
        . 'id="field_' . ($idindex) . '_3">';
    foreach ($column_set_values as $column_set_value) {
        $html_output .= '                ';
        $html_output .= '<option value="' . $column_set_value['html'] . '"';
        if (isset($vset[$column_set_value['plain']])) {
            $html_output .= ' selected="selected"';
        }
        $html_output .= '>' . $column_set_value['html'] . '</option>' . "\n";
    }
    $html_output .= '</select>';
    return $html_output;
}

/**
 * Retrieve column 'set' value and select size
 *  
 * @param array $column
 * @param array $extracted_columnspec
 * @return type 
 */
function PMA_getColumnSetValueAndSelectSize($column, $extracted_columnspec)
{
    if (! isset($column['values'])) {
        $column['values'] = array();
        foreach ($extracted_columnspec['enum_set_values'] as $val) {
            $column['values'][] = array(
                'plain' => $val,
                'html'  => htmlspecialchars($val),
            );
        }
        $column['select_size'] = min(4, count($column['values']));
    }
    return array($column['values'], $column['select_size']);
}

/**
 * Get HTML for binary and blob column
 * 
 * @param array $column
 * @param array $data
 * @param array $special_chars
 * @param integer $biggest_max_file_size
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param string $text_dir
 * @param string $special_chars_encoded
 * @param string $vkey
 * @param boolean $is_upload
 * @return string 
 */
function PMA_getBinaryAndBlobColumn($column, $data, $special_chars,$biggest_max_file_size,
    $backup_field, $column_name_appendix, $unnullify_trigger, $tabindex,
    $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded,$vkey, $is_upload
) {
    $html_output = '';
    if (($GLOBALS['cfg']['ProtectBinary'] && $column['is_blob'])
        || ($GLOBALS['cfg']['ProtectBinary'] == 'all' && $column['is_binary'])
        || ($GLOBALS['cfg']['ProtectBinary'] == 'noblob' && !$column['is_blob'])
    ) {
        $html_output .= __('Binary - do not edit');
        if (isset($data)) {
            $data_size = PMA_formatByteDown(strlen(stripslashes($data)), 3, 1);
            $html_output .= ' ('. $data_size [0] . ' ' . $data_size[1] . ')';
            unset($data_size);
        }

        $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix . '" value="protected" />'
            . '<input type="hidden" name="fields' . $column_name_appendix . '" value="" />';
    } elseif ($column['is_blob']) {
        $html_output .= "\n"
            . PMA_getTextarea($column,$backup_field, $column_name_appendix, $unnullify_trigger,
            $tabindex, $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded);
    } else {
        // field size should be at least 4 and max $GLOBALS['cfg']['LimitChars']
        $columnsize = min(max($column['len'], 4), $GLOBALS['cfg']['LimitChars']);
        $html_output .= "\n"
            . $backup_field . "\n"
            . PMA_getHTMLinput($column, $column_name_appendix, $special_chars, $columnsize,
            $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex);
    }
    
    if ($is_upload && $column['is_blob']) {
        $html_output .= '<br />'
            . '<input type="file" name="fields_upload' . $vkey . '[' . $column['Field_md5'] 
            . ']" class="textfield" id="field_' . $idindex . '_3" size="10" ' . $unnullify_trigger . '/>&nbsp;';
        list($html_out, $biggest_max_file_size) = PMA_getMaxUploadSize($column,$biggest_max_file_size);
        $html_output .= $html_out;
    }
    
    if (!empty($cfg['UploadDir'])) {
        $html_output .= PMA_getSelectOptionForUpload($vkey, $column);
    }
    
    return $html_output;
}

/**
 * Get HTML input type
 * 
 * @param array $column
 * @param string $column_name_appendix
 * @param array $special_chars
 * @param integer $columnsize
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @return string 
 */
function PMA_getHTMLinput($column, $column_name_appendix, $special_chars,
    $columnsize, $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex
) {
    $the_class = 'textfield';
    if ($column['pma_type'] == 'date') {
        $the_class .= ' datefield';
    } elseif ($column['pma_type'] == 'datetime'
        || substr($column['pma_type'], 0, 9) == 'timestamp'
    ) {
        $the_class .= ' datetimefield';
    }
    return '<input type="text" name="fields' . $column_name_appendix . '"'
        . 'value="' . $special_chars . '" size="' . $columnsize . '"'
        . 'class="' . $the_class . '"' . $unnullify_trigger
        . 'tabindex="' . ($tabindex + $tabindex_for_value). '"'
        . 'id="field_' . ($idindex) . '_3" />';
}

/**
 * Get HTML select option for upload
 * 
 * @param string $vkey
 * @param array $column
 * @return string 
 */
function PMA_getSelectOptionForUpload($vkey, $column)
{
    $files = PMA_getFileSelectOptions(PMA_userDir($GLOBALS['cfg']['UploadDir']));
    if ($files === false) {
        return '        <font color="red">' . __('Error') . '</font><br />' . "\n"
            . '        ' . __('The directory you set for upload work cannot be reached') . "\n";
    } elseif (!empty($files)) {
        return "<br />\n"
            . '    <i>' . __('Or') . '</i>' . ' ' . __('web server upload directory') . ':<br />' . "\n"
            . '        <select size="1" name="fields_uploadlocal' . $vkey . '[' . $column['Field_md5'] . ']">' . "\n"
            . '            <option value="" selected="selected"></option>' . "\n"
            . $files
            . '        </select>' . "\n";
    }
}

/**
 * Retrieve the maximum upload file size
 * 
 * @param array $column
 * @param integer $biggest_max_file_size
 * @return array 
 */
function PMA_getMaxUploadSize($column, $biggest_max_file_size)
{
    // find maximum upload size, based on field type
    /**
     * @todo with functions this is not so easy, as you can basically
     * process any data with function like MD5
     */
    $max_field_sizes = array(
        'tinyblob'   =>        '256',
        'blob'       =>      '65536',
        'mediumblob' =>   '16777216',
        'longblob'   => '4294967296'); // yeah, really

    $this_field_max_size = $max_upload_size; // from PHP max
    if ($this_field_max_size > $max_field_sizes[$column['pma_type']]) {
        $this_field_max_size = $max_field_sizes[$column['pma_type']];
    }
    $html_output = PMA_getFormattedMaximumUploadSize($this_field_max_size) . "\n";
    // do not generate here the MAX_FILE_SIZE, because we should
    // put only one in the form to accommodate the biggest field
    if ($this_field_max_size > $biggest_max_file_size) {
        $biggest_max_file_size = $this_field_max_size;
    }
    return array($html_output, $biggest_max_file_size);
}

/**
 * Get HTML for pma no support types 
 * 
 * @param array $column
 * @param string $default_char_editing
 * @param string $backup_field
 * @param string $column_name_appendix
 * @param string $unnullify_trigger
 * @param integer $tabindex
 * @param array $special_chars
 * @param integer $tabindex_for_value
 * @param integer $idindex
 * @param string $text_dir
 * @param array $special_chars_encoded
 * @return string 
 */
function PMA_getNoSupportTypes($column, $default_char_editing,$backup_field,
    $column_name_appendix, $unnullify_trigger,$tabindex,$special_chars,
    $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded,$data, $extracted_columnspec
) {
    $columnSize = PMA_getColumnSize($column, $extracted_columnspec);
    $html_output = $backup_field . "\n";
    if ($column['is_char']
        && ($GLOBALS['cfg']['CharEditing'] == 'textarea'
        || strpos($data, "\n") !== false)
    ) {
        $html_output .= "\n";
        $GLOBALS['cfg']['CharEditing'] = $default_char_editing;
        $html_output .= PMA_getTextarea($column, $backup_field, $column_name_appendix, $unnullify_trigger,
            $tabindex, $tabindex_for_value, $idindex, $text_dir, $special_chars_encoded);
    } else {
        $html_output .= PMA_getHTMLinput($column, $column_name_appendix, $special_chars,
            $columnSize, $unnullify_trigger, $tabindex, $tabindex_for_value, $idindex);
        
        if ($column['Extra'] == 'auto_increment') {
            $html_output .= '<input type="hidden" name="auto_increment' . $column_name_appendix . '" value="1" />';

        }
        if (substr($column['pma_type'], 0, 9) == 'timestamp') {
            $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix . '" value="timestamp" />';

        }
        if (substr($column['pma_type'], 0, 8) == 'datetime') {
            $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix . '" value="datetime" />';

        }
        if ($column['True_Type'] == 'bit') {
            $html_output .= '<input type="hidden" name="fields_type' . $column_name_appendix . '" value="bit" />';

        }
        if ($column['pma_type'] == 'date'
            || $column['pma_type'] == 'datetime'
            || substr($column['pma_type'], 0, 9) == 'timestamp'
        ) {
            // the _3 suffix points to the date field
            // the _2 suffix points to the corresponding NULL checkbox
            // in dateFormat, 'yy' means the year with 4 digits
        }
    }
    return $html_output;
}

/**
 * Get the column size
 * 
 * @param array $column
 * @return integer 
 */
function PMA_getColumnSize($column, $extracted_columnspec)
{
    if ($column['is_char']) {
        $columnSize = $extracted_columnspec['spec_in_brackets'];
        if ($columnSize > $GLOBALS['cfg']['MaxSizeForInputField']) {
            /**
             * This case happens for CHAR or VARCHAR columns which have
             * a size larger than the maximum size for input field.
             */
            $GLOBALS['cfg']['CharEditing'] = 'textarea';
        }
    } else {
        /**
         * This case happens for example for INT or DATE columns;
         * in these situations, the value returned in $column['len']
         * seems appropriate.
         */
        $columnSize = $column['len'];
    }
    return min(max($columnSize, $GLOBALS['cfg']['MinSizeForInputField']), $GLOBALS['cfg']['MaxSizeForInputField']);
}

/**
 * Get HTML for gis data types
 * 
 * @param string $vrow
 * @param array $column
 * @return string 
 */
function PMA_getHTMLforGisDataTypes($vrow, $column)
{
    $data_val = isset($vrow[$column['Field']]) ? $vrow[$column['Field']] : '';
    $_url_params = array(
        'field' => $column['Field_title'],
        'value' => $data_val,
     );
    if ($column['pma_type'] != 'geometry') {
        $_url_params = $_url_params + array('gis_data[gis_type]' => strtoupper($column['pma_type']));
    }
    $edit_str = PMA_getIcon('b_edit.png', __('Edit/Insert'));
    return '<span class="open_gis_editor">'
        . PMA_linkOrButton('#', $edit_str, array(), false, false, '_blank')
        . '</span>';
}

/**
 * get html for continue insertion form
 * 
 * @param array $paramArray
 * @param array $where_clause_array
 * @return string 
 */
function PMA_getContinueForm($paramArray, $where_clause_array, $err_url)
{
    list($table, $db) = $paramArray;
    $html_output = '<form id="continueForm" method="post" action="tbl_replace.php" name="continueForm" >'
        . PMA_generate_common_hidden_inputs($db, $table)
        . '<input type="hidden" name="goto" value="' . htmlspecialchars($GLOBALS['goto']) . '" />'
        . '<input type="hidden" name="err_url" value="' . htmlspecialchars($err_url) . '" />'
        . '<input type="hidden" name="sql_query" value="' . htmlspecialchars($_REQUEST['sql_query']) . '" />';
    
    if (isset($_REQUEST['where_clause'])) {
        foreach ($where_clause_array as $key_id => $where_clause) {
            $html_output .= '<input type="hidden" name="where_clause[' . $key_id . ']" value="' . htmlspecialchars(trim($where_clause)) . '" />'. "\n";
        }
    }
    $tmp = '<select name="insert_rows" id="insert_rows">' . "\n";
    $option_values = array(1,2,5,10,15,20,30,40);
    
    foreach ($option_values as $value) {
        $tmp .= '<option value="' . $value . '"';
        if ($value == $GLOBALS['cfg']['InsertRows']) {
            $tmp .= ' selected="selected"';
        }
        $tmp .= '>' . $value . '</option>' . "\n";
    }
    
    $tmp .= '</select>' . "\n";
    $html_output .= "\n" . sprintf(__('Continue insertion with %s rows'), $tmp);
    unset($tmp);
    $html_output .= '</form>' . "\n";
    return $html_output;
}

/**
 * Get action panel
 * 
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @param string $after_insert
 * @param boolean $found_unique_key
 * @return string 
 */
function PMA_getActionsPanel($tabindex, $tabindex_for_value, $after_insert, $found_unique_key)
{
    $html_output = '<fieldset id="actions_panel">'
        . '<table cellpadding="5" cellspacing="0">'
        . '<tr>'
        . '<td class="nowrap vmiddle">'
        . PMA_getSubmitTypeDropDown($tabindex, $tabindex_for_value)
        . "\n";
    if (!isset($after_insert)) {
        $after_insert = 'back';
    }
    $html_output .= '</td>'
        . '<td class="vmiddle">'
        . '&nbsp;&nbsp;&nbsp;<strong>' . __('and then') . '</strong>&nbsp;&nbsp;&nbsp;'
        . '</td>'
        . '<td class="nowrap vmiddle">'
        . PMA_getAfterInsertDropDown($after_insert, $found_unique_key)
        . '</td>'
        . '</tr>';
    $html_output .='<tr>'
        . PMA_getSumbitAndREsetButtonForActionsPanel($tabindex, $tabindex_for_value)
        . '</tr>'
        . '</table>'
        . '</fieldset>';
    return $html_output;
}

/**
 * Get a HTML drop down for submit types
 * 
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @return string 
 */
function PMA_getSubmitTypeDropDown($tabindex, $tabindex_for_value)
{
    $html_output = '<select name="submit_type" class="control_at_footer" tabindex="' . ($tabindex + $tabindex_for_value + 1) . '">';
    if (isset($_REQUEST['where_clause'])) {
        $html_output .= '<option value="save">' . __('Save') . '</option>';
    }
    $html_output .= '<option value="insert">' . __('Insert as new row') . '</option>'
        . '<option value="insertignore">' . __('Insert as new row and ignore errors') . '</option>'
        . '<option value="showinsert">' . __('Show insert query') . '</option>'
        . '</select>';
    return $html_output;
}

/**
 * Get HTML drop down for after insert
 * 
 * @param string $after_insert
 * @param boolean $found_unique_key
 * @return string 
 */
function PMA_getAfterInsertDropDown($after_insert, $found_unique_key)
{
    $html_output = '<select name="after_insert">'
        . '<option value="back" ' . ($after_insert == 'back' ? 'selected="selected"' : '') . '>'
        . __('Go back to previous page') . '</option>'
        . '<option value="new_insert" ' . ($after_insert == 'new_insert' ? 'selected="selected"' : '') . '>'
        . __('Insert another new row') . '</option>';
    
    if (isset($_REQUEST['where_clause'])) {
        $html_output .= '<option value="same_insert" ' . ($after_insert == 'same_insert' ? 'selected="selected"' : '') . '>'
            . __('Go back to this page') . '</option>';
        
        // If we have just numeric primary key, we can also edit next
        // in 2.8.2, we were looking for `field_name` = numeric_value
        //if (preg_match('@^[\s]*`[^`]*` = [0-9]+@', $where_clause)) {
        // in 2.9.0, we are looking for `table_name`.`field_name` = numeric_value
        if ($found_unique_key && preg_match('@^[\s]*`[^`]*`[\.]`[^`]*` = [0-9]+@', $_REQUEST['where_clause'])) {
            $html_output .= '<option value="edit_next" '. ($after_insert == 'edit_next' ? 'selected="selected"' : '') . '>'
                . __('Edit next row') . '</option>';
 
        }
    }
    $html_output .= '</select>';
    return $html_output;
    
}

/**
 * get Submit button and Reset button for action panel
 * 
 * @param integer $tabindex
 * @param integer $tabindex_for_value
 * @return string 
 */
function PMA_getSumbitAndResetButtonForActionsPanel($tabindex, $tabindex_for_value)
{
    return '<td>'
    . PMA_showHint(__('Use TAB key to move from value to value, or CTRL+arrows to move anywhere'))
    . '</td>'
    . '<td colspan="3" class="right vmiddle">'
    . '<input type="submit" class="control_at_footer" value="' . __('Go') . '"'
    . 'tabindex="' . ($tabindex + $tabindex_for_value + 6) . '" id="buttonYes" />'
    . '<input type="reset" class="control_at_footer" value="' . __('Reset') . '"'
    . 'tabindex="' . ($tabindex + $tabindex_for_value + 7) . '" />'
    . '</td>';
}

/**
 * Get table head and table foot for insert row table
 * 
 * @param array $url_params
 * @return string 
 */
function PMA_getHeadAndFootOfInsertRowTable($url_params)
{
    $html_output = '<table class="insertRowTable">'
        . '<thead>'
        . '<tr>'
        . '<th>' . __('Column') . '</th>';
    
    if ($GLOBALS['cfg']['ShowFieldTypesInDataEditView']) {
        $html_output .= PMA_showColumnTypesInDataEditView($url_params, true);
    }
    if ($GLOBALS['cfg']['ShowFunctionFields']) {
        $html_output .= PMA_showFunctionFieldsInEditMode($url_params, true);
    }
    
    $html_output .= '<th>'. __('Null') . '</th>'
        . '<th>' . __('Value') . '</th>'
        . '</tr>'
        . '</thead>'
        . ' <tfoot>'
        . '<tr>'
        . '<th colspan="5" class="tblFooters right">'
        . '<input type="submit" value="' . __('Go') . '" />'
        . '</th>'
        . '</tr>'
        . '</tfoot>';
    return $html_output;
}

?>
