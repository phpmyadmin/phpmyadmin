<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Manipulation of table data like inserting, replacing and updating
 *
 * Usually called as form action from tbl_change.php to insert or update table rows
 *
 * @todo 'edit_next' tends to not work as expected if used ...
 * at least there is no order by it needs the original query
 * and the row number and than replace the LIMIT clause
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\File;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

// Check parameters
Util::checkParameters(array('db', 'table', 'goto'));

$GLOBALS['dbi']->selectDb($GLOBALS['db']);

/**
 * Initializes some variables
 */
$goto_include = false;

$response = Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('makegrid.js');
// Needed for generation of Inline Edit anchors
$scripts->addFile('sql.js');
$scripts->addFile('indexes.js');
$scripts->addFile('gis_data_editor.js');

$relation = new Relation();

$insertEdit = new InsertEdit($GLOBALS['dbi']);

// check whether insert row mode, if so include tbl_change.php
$insertEdit->isInsertRow();

$after_insert_actions = array('new_insert', 'same_insert', 'edit_next');
if (isset($_POST['after_insert'])
    && in_array($_POST['after_insert'], $after_insert_actions)
) {
    $url_params['after_insert'] = $_POST['after_insert'];
    if (isset($_POST['where_clause'])) {
        foreach ($_POST['where_clause'] as $one_where_clause) {
            if ($_POST['after_insert'] == 'same_insert') {
                $url_params['where_clause'][] = $one_where_clause;
            } elseif ($_POST['after_insert'] == 'edit_next') {
                $insertEdit->setSessionForEditNext($one_where_clause);
            }
        }
    }
}
//get $goto_include for different cases
$goto_include = $insertEdit->getGotoInclude($goto_include);

// Defines the url to return in case of failure of the query
$err_url = $insertEdit->getErrorUrl($url_params);

/**
 * Prepares the update/insert of a row
 */
list($loop_array, $using_key, $is_insert, $is_insertignore)
    = $insertEdit->getParamsForUpdateOrInsert();

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
    'UUID_SHORT',
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

//if some posted fields need to be transformed.
$mime_map = Transformations::getMIME($GLOBALS['db'], $GLOBALS['table']);
if ($mime_map === false) {
    $mime_map = array();
}

$query_fields = array();
$insert_errors = array();
$row_skipped = false;
$unsaved_values = array();
foreach ($loop_array as $rownumber => $where_clause) {
    // skip fields to be ignored
    if (! $using_key && isset($_POST['insert_ignore_' . $where_clause])) {
        continue;
    }

    // Defines the SET part of the sql query
    $query_values = array();

    // Map multi-edit keys to single-level arrays, dependent on how we got the fields
    $multi_edit_columns
        = isset($_POST['fields']['multi_edit'][$rownumber])
        ? $_POST['fields']['multi_edit'][$rownumber]
        : array();
    $multi_edit_columns_name
        = isset($_POST['fields_name']['multi_edit'][$rownumber])
        ? $_POST['fields_name']['multi_edit'][$rownumber]
        : array();
    $multi_edit_columns_prev
        = isset($_POST['fields_prev']['multi_edit'][$rownumber])
        ? $_POST['fields_prev']['multi_edit'][$rownumber]
        : null;
    $multi_edit_funcs
        = isset($_POST['funcs']['multi_edit'][$rownumber])
        ? $_POST['funcs']['multi_edit'][$rownumber]
        : null;
    $multi_edit_salt
        = isset($_POST['salt']['multi_edit'][$rownumber])
        ? $_POST['salt']['multi_edit'][$rownumber]
        :null;
    $multi_edit_columns_type
        = isset($_POST['fields_type']['multi_edit'][$rownumber])
        ? $_POST['fields_type']['multi_edit'][$rownumber]
        : null;
    $multi_edit_columns_null
        = isset($_POST['fields_null']['multi_edit'][$rownumber])
        ? $_POST['fields_null']['multi_edit'][$rownumber]
        : null;
    $multi_edit_columns_null_prev
        = isset($_POST['fields_null_prev']['multi_edit'][$rownumber])
        ? $_POST['fields_null_prev']['multi_edit'][$rownumber]
        : null;
    $multi_edit_auto_increment
        = isset($_POST['auto_increment']['multi_edit'][$rownumber])
        ? $_POST['auto_increment']['multi_edit'][$rownumber]
        : null;
    $multi_edit_virtual
        = isset($_POST['virtual']['multi_edit'][$rownumber])
        ? $_POST['virtual']['multi_edit'][$rownumber]
        : null;

    // When a select field is nullified, it's not present in $_POST
    // so initialize it; this way, the foreach($multi_edit_columns) will process it
    foreach ($multi_edit_columns_name as $key => $val) {
        if (! isset($multi_edit_columns[$key])) {
            $multi_edit_columns[$key] = '';
        }
    }

    // Iterate in the order of $multi_edit_columns_name,
    // not $multi_edit_columns, to avoid problems
    // when inserting multiple entries
    $insert_fail = false;
    foreach ($multi_edit_columns_name as $key => $column_name) {
        $current_value = $multi_edit_columns[$key];
        // Note: $key is an md5 of the fieldname. The actual fieldname is
        // available in $multi_edit_columns_name[$key]

        $file_to_insert = new File();
        $file_to_insert->checkTblChangeForm($key, $rownumber);

        $possibly_uploaded_val = $file_to_insert->getContent();
        if ($possibly_uploaded_val !== false) {
            $current_value = $possibly_uploaded_val;
        }
        // Apply Input Transformation if defined
        if (!empty($mime_map[$column_name])
            && !empty($mime_map[$column_name]['input_transformation'])
        ) {
            $filename = 'libraries/classes/Plugins/Transformations/'
                . $mime_map[$column_name]['input_transformation'];
            if (is_file($filename)) {
                $classname = Transformations::getClassName($filename);
                if (class_exists($classname)) {
                    /** @var IOTransformationsPlugin $transformation_plugin */
                    $transformation_plugin = new $classname();
                    $transformation_options = Transformations::getOptions(
                        $mime_map[$column_name]['input_transformation_options']
                    );
                    $current_value = $transformation_plugin->applyTransformation(
                        $current_value, $transformation_options
                    );
                    // check if transformation was successful or not
                    // and accordingly set error messages & insert_fail
                    if (method_exists($transformation_plugin, 'isSuccess')
                        && !$transformation_plugin->isSuccess()
                    ) {
                        $insert_fail = true;
                        $row_skipped = true;
                        $insert_errors[] = sprintf(
                            __('Row: %1$s, Column: %2$s, Error: %3$s'),
                            $rownumber, $column_name,
                            $transformation_plugin->getError()
                        );
                    }
                }
            }
        }

        if ($file_to_insert->isError()) {
            $insert_errors[] = $file_to_insert->getError();
        }
        // delete $file_to_insert temporary variable
        $file_to_insert->cleanUp();

        $current_value = $insertEdit->getCurrentValueForDifferentTypes(
            $possibly_uploaded_val, $key, $multi_edit_columns_type,
            $current_value, $multi_edit_auto_increment,
            $rownumber, $multi_edit_columns_name, $multi_edit_columns_null,
            $multi_edit_columns_null_prev, $is_insert,
            $using_key, $where_clause, $table, $multi_edit_funcs
        );

        $current_value_as_an_array = $insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt, $gis_from_text_functions, $current_value,
            $gis_from_wkb_functions, $func_optional_param, $func_no_param, $key
        );

        if (! isset($multi_edit_virtual) || ! isset($multi_edit_virtual[$key])) {
            list($query_values, $query_fields)
                = $insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
                    $multi_edit_columns_name, $multi_edit_columns_null,
                    $current_value, $multi_edit_columns_prev, $multi_edit_funcs,
                    $is_insert, $query_values, $query_fields,
                    $current_value_as_an_array, $value_sets, $key,
                    $multi_edit_columns_null_prev
                );
        }
        if (isset($multi_edit_columns_null[$key])) {
            $multi_edit_columns[$key] = null;
        }
    } //end of foreach

    // temporarily store rows not inserted
    // so that they can be populated again.
    if ($insert_fail) {
        $unsaved_values[$rownumber] = $multi_edit_columns;
    }
    if (!$insert_fail && count($query_values) > 0) {
        if ($is_insert) {
            $value_sets[] = implode(', ', $query_values);
        } else {
            // build update query
            $query[] = 'UPDATE ' . Util::backquote($GLOBALS['table'])
                . ' SET ' . implode(', ', $query_values)
                . ' WHERE ' . $where_clause
                . ($_POST['clause_is_unique'] ? '' : ' LIMIT 1');
        }
    }
} // end foreach ($loop_array as $where_clause)
unset(
    $multi_edit_columns_name, $multi_edit_columns_prev, $multi_edit_funcs,
    $multi_edit_columns_type, $multi_edit_columns_null, $func_no_param,
    $multi_edit_auto_increment, $current_value_as_an_array, $key, $current_value,
    $loop_array, $where_clause, $using_key,  $multi_edit_columns_null_prev,
    $insert_fail
);

// Builds the sql query
if ($is_insert && count($value_sets) > 0) {
    $query = $insertEdit->buildSqlQuery($is_insertignore, $query_fields, $value_sets);
} elseif (empty($query) && ! isset($_POST['preview_sql']) && !$row_skipped) {
    // No change -> move back to the calling script
    //
    // Note: logic passes here for inline edit
    $message = Message::success(__('No change'));
    // Avoid infinite recursion
    if ($goto_include == 'tbl_replace.php') {
        $goto_include = 'tbl_change.php';
    }
    $active_page = $goto_include;
    include '' . Core::securePath($goto_include);
    exit;
}
unset($multi_edit_columns, $is_insertignore);

// If there is a request for SQL previewing.
if (isset($_POST['preview_sql'])) {
    Core::previewSQL($query);
}

/**
 * Executes the sql query and get the result, then move back to the calling
 * page
 */
list ($url_params, $total_affected_rows, $last_messages, $warning_messages,
    $error_messages, $return_to_sql_query)
        = $insertEdit->executeSqlQuery($url_params, $query);

if ($is_insert && (count($value_sets) > 0 || $row_skipped)) {
    $message = Message::getMessageForInsertedRows(
        $total_affected_rows
    );
    $unsaved_values = array_values($unsaved_values);
} else {
    $message = Message::getMessageForAffectedRows(
        $total_affected_rows
    );
}
if ($row_skipped) {
    $goto_include = 'tbl_change.php';
    $message->addMessagesString($insert_errors, '<br />');
    $message->isError(true);
}

$message->addMessages($last_messages, '<br />');

if (! empty($warning_messages)) {
    $message->addMessagesString($warning_messages, '<br />');
    $message->isError(true);
}
if (! empty($error_messages)) {
    $message->addMessagesString($error_messages);
    $message->isError(true);
}
unset(
    $error_messages, $warning_messages, $total_affected_rows,
    $last_messages, $last_message, $row_skipped, $insert_errors
);

/**
 * The following section only applies to grid editing.
 * However, verifying isAjax() is not enough to ensure we are coming from
 * grid editing. If we are coming from the Edit or Copy link in Browse mode,
 * ajax_page_request is present in the POST parameters.
 */
if ($response->isAjax() && ! isset($_POST['ajax_page_request'])) {
    /**
     * If we are in grid editing, we need to process the relational and
     * transformed fields, if they were edited. After that, output the correct
     * link/transformed value and exit
     */
    if (isset($_POST['rel_fields_list']) && $_POST['rel_fields_list'] != '') {

        $map = $relation->getForeigners($db, $table, '', 'both');

        $relation_fields = array();
        parse_str($_POST['rel_fields_list'], $relation_fields);

        // loop for each relation cell
        /** @var array $relation_fields */
        foreach ($relation_fields as $cell_index => $curr_rel_field) {
            foreach ($curr_rel_field as $relation_field => $relation_field_value) {
                $where_comparison = "='" . $relation_field_value . "'";
                $dispval = $insertEdit->getDisplayValueForForeignTableColumn(
                    $where_comparison, $map, $relation_field
                );

                $extra_data['relations'][$cell_index]
                    = $insertEdit->getLinkForRelationalDisplayField(
                        $map, $relation_field, $where_comparison,
                        $dispval, $relation_field_value
                    );
            }
        }   // end of loop for each relation cell
    }
    if (isset($_POST['do_transformations'])
        && $_POST['do_transformations'] == true
    ) {
        $edited_values = array();
        parse_str($_POST['transform_fields_list'], $edited_values);

        if (! isset($extra_data)) {
            $extra_data = array();
        }
        $transformation_types = array(
            "input_transformation",
            "transformation"
        );
        foreach ($mime_map as $transformation) {
            $column_name = $transformation['column_name'];
            foreach ($transformation_types as $type) {
                $file = Core::securePath($transformation[$type]);
                $extra_data = $insertEdit->transformEditedValues(
                    $db, $table, $transformation, $edited_values, $file,
                    $column_name, $extra_data, $type
                );
            }
        }   // end of loop for each $mime_map
    }

    // Need to check the inline edited value can be truncated by MySQL
    // without informing while saving
    $column_name = $_POST['fields_name']['multi_edit'][0][0];

    $insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
        $db, $table, $column_name, $extra_data
    );

    /**Get the total row count of the table*/
    $_table = new Table($_POST['table'], $_POST['db']);
    $extra_data['row_count'] = $_table->countRecords();

    $extra_data['sql_query'] = Util::getMessage(
        $message,
        $GLOBALS['display_query']
    );

    $response->setRequestStatus($message->isSuccess());
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

$scripts->addFile('vendor/jquery/additional-methods.js');
$scripts->addFile('tbl_change.js');

$active_page = $goto_include;

/**
 * If user asked for "and then Insert another new row" we have to remove
 * WHERE clause information so that tbl_change.php does not go back
 * to the current record
 */
if (isset($_POST['after_insert']) && 'new_insert' == $_POST['after_insert']) {
    unset($_POST['where_clause']);
}

/**
 * Load target page.
 */
require '' . Core::securePath($goto_include);
exit;
