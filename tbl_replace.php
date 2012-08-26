<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Manipulation of table data like inserting, replacing and updating
 *
 * Usally called as form action from tbl_change.php to insert or update table rows
 *
 * @todo 'edit_next' tends to not work as expected if used ...
 * at least there is no order by it needs the original query
 * and the row number and than replace the LIMIT clause
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/insert_edit.lib.php';

$common_functions = PMA_CommonFunctions::getInstance();

// Check parameters
$common_functions->checkParameters(array('db', 'table', 'goto'));

PMA_DBI_select_db($GLOBALS['db']);

/**
 * Initializes some variables
 */
$goto_include = false;

$response = PMA_Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('makegrid.js');
// Needed for generation of Inline Edit anchors
$scripts->addFile('sql.js');

// check whether insert row moode, if so include tbl_change.php
PMA_isInsertRow();

if (isset($_REQUEST['after_insert'])
    && in_array($_REQUEST['after_insert'], array('new_insert', 'same_insert', 'edit_next'))
) {
    $url_params['after_insert'] = $_REQUEST['after_insert'];
    if (isset($_REQUEST['where_clause'])) {
        foreach ($_REQUEST['where_clause'] as $one_where_clause) {
            if ($_REQUEST['after_insert'] == 'same_insert') {
                $url_params['where_clause'][] = $one_where_clause;
            } elseif ($_REQUEST['after_insert'] == 'edit_next') {
                PMA_setSessionForEditNext($one_where_clause);
            }
        }
    }
}
//get $goto_include for different cases
$goto_include = PMA_getGotoInclude($goto_include);

// Defines the url to return in case of failure of the query
$err_url = PMA_getErrorUrl($url_params);

/**
 * Prepares the update/insert of a row
 */
list($loop_array, $using_key, $is_insert, $is_insertignore)
    = PMA_getParamsForUpdateOrInsert();

$query = array();
$value_sets = array();
$func_no_param = array(
    'CONNECTION_ID',
    'CURRENT_USER',
    'CURDATE',
    'CURTIME',
    'CURRENT_DATE',
    'CURRENT_TIME',
    'DATABASE',
    'LAST_INSERT_ID',
    'NOW',
    'PI',
    'RAND',
    'SYSDATE',
    'UNIX_TIMESTAMP',
    'USER',
    'UTC_DATE',
    'UTC_TIME',
    'UTC_TIMESTAMP',
    'UUID',
    'VERSION',
);
$func_optional_param = array(
    'RAND',
    'UNIX_TIMESTAMP',
);

$gis_from_text_functions = array(
    'GeomFromText',
    'GeomCollFromText',
    'LineFromText',
    'MLineFromText',
    'PointFromText',
    'MPointFromText',
    'PolyFromText',
    'MPolyFromText',
);

$gis_from_wkb_functions = array(
    'GeomFromWKB',
    'GeomCollFromWKB',
    'LineFromWKB',
    'MLineFromWKB',
    'PointFromWKB',
    'MPointFromWKB',
    'PolyFromWKB',
    'MPolyFromWKB',
);

// to create an object of PMA_File class
require_once './libraries/File.class.php';

$query_fields = array();
foreach ($loop_array as $rownumber => $where_clause) {
    // skip fields to be ignored
    if (! $using_key && isset($_REQUEST['insert_ignore_' . $where_clause])) {
        continue;
    }

    // Defines the SET part of the sql query
    $query_values = array();

    // Map multi-edit keys to single-level arrays, dependent on how we got the fields
    $multi_edit_colummns
        = isset($_REQUEST['fields']['multi_edit'][$rownumber])
        ? $_REQUEST['fields']['multi_edit'][$rownumber]
        : array();
    $multi_edit_columns_name
        = isset($_REQUEST['fields_name']['multi_edit'][$rownumber])
        ? $_REQUEST['fields_name']['multi_edit'][$rownumber]
        : null;
    $multi_edit_columns_prev
        = isset($_REQUEST['fields_prev']['multi_edit'][$rownumber])
        ? $_REQUEST['fields_prev']['multi_edit'][$rownumber]
        : null;
    $multi_edit_funcs
        = isset($_REQUEST['funcs']['multi_edit'][$rownumber])
        ? $_REQUEST['funcs']['multi_edit'][$rownumber]
        : null;
    $multi_edit_columns_type
        = isset($_REQUEST['fields_type']['multi_edit'][$rownumber])
        ? $_REQUEST['fields_type']['multi_edit'][$rownumber]
        : null;
    $multi_edit_columns_null
        = isset($_REQUEST['fields_null']['multi_edit'][$rownumber])
        ? $_REQUEST['fields_null']['multi_edit'][$rownumber]
        : null;
    $multi_edit_columns_null_prev
        = isset($_REQUEST['fields_null_prev']['multi_edit'][$rownumber])
        ? $_REQUEST['fields_null_prev']['multi_edit'][$rownumber]
        : null;
    $multi_edit_auto_increment
        = isset($_REQUEST['auto_increment']['multi_edit'][$rownumber])
        ? $_REQUEST['auto_increment']['multi_edit'][$rownumber]
        : null;

    // When a select field is nullified, it's not present in $_REQUEST
    // so initialize it; this way, the foreach($multi_edit_colummns) will process it
    foreach ($multi_edit_columns_name as $key => $val) {
        if (! isset($multi_edit_colummns[$key])) {
            $multi_edit_colummns[$key] = '';
        }
    }

    // Iterate in the order of $multi_edit_columns_name,
    // not $multi_edit_colummns, to avoid problems
    // when inserting multiple entries
    foreach ($multi_edit_columns_name as $key => $colummn_name) {
        $current_value = $multi_edit_colummns[$key];
        // Note: $key is an md5 of the fieldname. The actual fieldname is
        // available in $multi_edit_columns_name[$key]

        $file_to_insert = new PMA_File();
        $file_to_insert->checkTblChangeForm($key, $rownumber);

        $possibly_uploaded_val = $file_to_insert->getContent();

        if ($file_to_insert->isError()) {
            $message .= $file_to_insert->getError();
        }
        // delete $file_to_insert temporary variable
        $file_to_insert->cleanUp();

        $current_value = PMA_getCurrentValueForDifferentTypes(
            $possibly_uploaded_val, $key, $multi_edit_columns_type,
            $current_value, $multi_edit_auto_increment,
            $rownumber, $multi_edit_columns_name, $multi_edit_columns_null,
            $multi_edit_columns_null_prev, $is_insert,
            $using_key, $where_clause, $table
        );

        $current_value_as_an_array = PMA_getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_colummns, $multi_edit_columns_name, $multi_edit_funcs,
            $gis_from_text_functions, $current_value, $gis_from_wkb_functions,
            $func_optional_param, $func_no_param, $key
        );

        list($query_values, $query_fields)
            = PMA_getQueryValuesForInsertAndUpdateInMultipleEdit(
                $multi_edit_columns_name, $multi_edit_columns_null, $current_value,
                $multi_edit_columns_prev, $multi_edit_funcs, $is_insert,
                $query_values, $query_fields, $current_value_as_an_array,
                $value_sets, $key, $multi_edit_columns_null_prev
            );
    } //end of foreach

    if (count($query_values) > 0) {
        if ($is_insert) {
            $value_sets[] = implode(', ', $query_values);
        } else {
            // build update query
            $query[] = 'UPDATE ' . $common_functions->backquote($GLOBALS['db'])
                . '.' . $common_functions->backquote($GLOBALS['table'])
                . ' SET ' . implode(', ', $query_values)
                . ' WHERE ' . $where_clause
                . ($_REQUEST['clause_is_unique'] ? '' : ' LIMIT 1');
        }
    }
} // end foreach ($loop_array as $where_clause)
unset($multi_edit_columns_name, $multi_edit_columns_prev, $multi_edit_funcs,
    $multi_edit_columns_type, $multi_edit_columns_null, $func_no_param,
    $multi_edit_auto_increment, $current_value_as_an_array, $key, $current_value,
    $loop_array, $where_clause, $using_key,  $multi_edit_columns_null_prev);

// Builds the sql query
if ($is_insert && count($value_sets) > 0) {
    $query = PMA_buildSqlQuery($is_insertignore, $query_fields, $value_sets);
} elseif (empty($query)) {
    // No change -> move back to the calling script
    //
    // Note: logic passes here for inline edit
    $message = PMA_Message::success(__('No change'));
    $active_page = $goto_include;
    include '' . PMA_securePath($goto_include);
    exit;
}
unset($multi_edit_colummns, $is_insertignore);

/**
 * Executes the sql query and get the result, then move back to the calling
 * page
 */
list ($url_params, $total_affected_rows, $last_messages, $warning_messages,
    $error_messages, $return_to_sql_query)
        = PMA_executeSqlQuery($url_params, $query);

if ($is_insert && count($value_sets) > 0) {
    $message = PMA_Message::inserted_rows($total_affected_rows);
} else {
    $message = PMA_Message::affected_rows($total_affected_rows);
}

$message->addMessages($last_messages, '<br />');

if (! empty($warning_messages)) {
    $message->addMessages($warning_messages, '<br />');
    $message->isError(true);
}
if (! empty($error_messages)) {
    $message->addMessages($error_messages);
    $message->isError(true);
}
unset(
    $error_messages, $warning_messages, $total_affected_rows,
    $last_messages, $last_message
);

if ($response->isAjax()) {
    /**
     * If we are in grid editing, we need to process the relational and
     * transformed fields, if they were edited. After that, output the correct
     * link/transformed value and exit
     *
     * Logic taken from libraries/DisplayResults.class.php
     */

    if (isset($_REQUEST['rel_fields_list']) && $_REQUEST['rel_fields_list'] != '') {

        $map = PMA_getForeigners($db, $table, '', 'both');

        $relation_fields = array();
        parse_str($_REQUEST['rel_fields_list'], $relation_fields);

        // loop for each relation cell
        foreach ($relation_fields as $cell_index => $curr_cell_rel_field) {
            foreach ($curr_cell_rel_field as $relation_field => $relation_field_value) {
                $where_comparison = "='" . $relation_field_value . "'";
                $dispval = PMA_getDisplayValueForForeignTableColumn(
                    $where_comparison, $relation_field_value, $map, $relation_field
                );

                $extra_data['relations'][$cell_index] = PMA_getLinkForRelationalDisplayField(
                    $map, $relation_field, $where_comparison,
                    $dispval, $relation_field_value
                );
            }
        }   // end of loop for each relation cell
    }
    if (isset($_REQUEST['do_transformations'])
        && $_REQUEST['do_transformations'] == true
    ) {
        include_once 'libraries/transformations.lib.php';
        //if some posted fields need to be transformed, generate them here.
        $mime_map = PMA_getMIME($db, $table);

        if ($mime_map === false) {
            $mime_map = array();
        }
        $edited_values = array();
        parse_str($_REQUEST['transform_fields_list'], $edited_values);

        foreach ($mime_map as $transformation) {
            $include_file = PMA_securePath($transformation['transformation']);
            $column_name = $transformation['column_name'];

            foreach ($edited_values as $cell_index => $curr_cell_edited_values) {
                if (isset($curr_cell_edited_values[$column_name])) {
                    $column_data = $curr_cell_edited_values[$column_name];

                    $_url_params = array(
                        'db'            => $db,
                        'table'         => $table,
                        'where_clause'  => $_REQUEST['where_clause'],
                        'transform_key' => $column_name,
                    );

                    if (file_exists('libraries/transformations/' . $include_file)) {
                        $transformfunction_name = str_replace(
                            '.inc.php', '', $transformation['transformation']
                        );

                        include_once 'libraries/transformations/' . $include_file;

                        if (function_exists('PMA_transformation_' . $transformfunction_name)) {
                            $transform_function = 'PMA_transformation_' . $transformfunction_name;
                            $transform_options  = PMA_transformation_getOptions(
                                isset($transformation['transformation_options'])
                                ? $transformation['transformation_options'] : ''
                            );
                            $transform_options['wrapper_link'] = PMA_generate_common_url($_url_params);
                        }
                    }

                    $extra_data['transformations'][$cell_index] = $transform_function(
                        $column_data, $transform_options
                    );
                }
            }   // end of loop for each transformation cell
        }   // end of loop for each $mime_map
    }

    /**Get the total row count of the table*/
    $extra_data['row_count'] = PMA_Table::countRecords(
        $_REQUEST['db'], $_REQUEST['table']
    );
    
    $extra_data['sql_query']
        = $common_functions->getMessage($message, $GLOBALS['display_query']);

    $response = PMA_Response::getInstance();
    $response->isSuccess($message->isSuccess());
    $response->addJSON('message', $message);
    $response->addJSON($extra_data);
    exit;
}

if (! empty($return_to_sql_query)) {
    $disp_query = $GLOBALS['sql_query'];
    $disp_message = $message;
    unset($message);
    $GLOBALS['sql_query'] = $return_to_sql_query;
}

$scripts->addFile('tbl_change.js');

$active_page = $goto_include;

/**
 * If user asked for "and then Insert another new row" we have to remove
 * WHERE clause information so that tbl_change.php does not go back
 * to the current record
 */
if (isset($_REQUEST['after_insert']) && 'new_insert' == $_REQUEST['after_insert']) {
    unset($_REQUEST['where_clause']);
}

/**
 * Load target page.
 */
require '' . PMA_securePath($goto_include);
exit;
?>
