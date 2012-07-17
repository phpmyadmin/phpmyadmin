<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Alter one or more table columns
 *
 * linked from table_structure, uses libraries/tbl_properties.inc.php to display
 * form and handles this form data
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

$common_functions = PMA_CommonFunctions::getInstance();

if (isset($_REQUEST['field'])) {
    $GLOBALS['field'] = $_REQUEST['field'];
}

// Check parameters
$common_functions->checkParameters(array('db', 'table'));

/**
 * Gets tables informations
 */
require_once 'libraries/tbl_common.inc.php';
require_once 'libraries/tbl_info.inc.php';

$active_page = 'tbl_structure.php';

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);

/**
 * Moving columns
 */
if (isset($_REQUEST['move_columns'])
    && is_array($_REQUEST['move_columns'])
    && $GLOBALS['is_ajax_request']) {
    /*
     * first, load the definitions for all columns
     */
    $columns = PMA_DBI_get_columns_full($db, $table);
    $column_names = array_keys($columns);
    $changes = array();
    $we_dont_change_keys = array();

    // move columns from first to last
    for ($i = 0, $l = count($_REQUEST['move_columns']); $i < $l; $i++) {
        $column = $_REQUEST['move_columns'][$i];
        // is this column already correctly placed?
        if ($column_names[$i] == $column) {
            continue;
        }

        // it is not, let's move it to index $i
        $data = $columns[$column];
        $extracted_columnspec = $common_functions->extractColumnSpec($data['Type']);
        if (isset($data['Extra']) && $data['Extra'] == 'on update CURRENT_TIMESTAMP') {
            $extracted_columnspec['attribute'] = $data['Extra'];
            unset($data['Extra']);
        }
        $current_timestamp = false;
        if ($data['Type'] == 'timestamp' && $data['Default'] == 'CURRENT_TIMESTAMP') {
            $current_timestamp = true;
        }
        $default_type
            = $data['Null'] === 'YES' && $data['Default'] === null
                ? 'NULL'
                : ($current_timestamp
                    ? 'CURRENT_TIMESTAMP'
                    : ($data['Default'] == ''
                        ? 'NONE'
                        : 'USER_DEFINED'));

        $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
            $column,
            $column,
            strtoupper($extracted_columnspec['type']),
            $extracted_columnspec['spec_in_brackets'],
            $extracted_columnspec['attribute'],
            isset($data['Collation'])
                ? $data['Collation']
                : '',
            $data['Null'] === 'YES'
                ? 'NULL'
                : 'NOT NULL',
            $default_type,
            $current_timestamp
                ? ''
                : $data['Default'],
            isset($data['Extra']) && $data['Extra'] !== ''
                ? $data['Extra']
                : false,
            isset($data['Comments']) && $data['Comments'] !== ''
                ? $data['Comments']
                : false,
            $we_dont_change_keys,
            $i,
            $i === 0
                ? '-first'
                : $column_names[$i - 1]
                );
        // update current column_names array, first delete old position
        for ($j = 0, $ll = count($column_names); $j < $ll; $j++) {
            if ($column_names[$j] == $column) {
                unset($column_names[$j]);
            }
        }
        // insert moved column
        array_splice($column_names, $i, 0, $column);
    }
    $response = PMA_Response::getInstance();
    if (empty($changes)) { // should never happen
        $response->isSuccess(false);
        exit;
    }
    $move_query = 'ALTER TABLE ' . $common_functions->backquote($table) . ' ';
    $move_query .= implode(', ', $changes);
    // move columns
    $result = PMA_DBI_try_query($move_query);
    $tmp_error = PMA_DBI_getError();
    if ($tmp_error) {
        $response->isSuccess(false);
        $response->addJSON('message', PMA_Message::error($tmp_error));
    } else {
        $message = PMA_Message::success(
            __('The columns have been moved successfully.')
        );
        $response->addJSON('message', $message);
        $response->addJSON('columns', $column_names);
    }
    exit;
}

/**
 * Modifications have been submitted -> updates the table
 */
$abort = false;
if (isset($_REQUEST['do_save_data'])) {
    $field_cnt = count($_REQUEST['field_orig']);
    $key_fields = array();
    $changes = array();

    for ($i = 0; $i < $field_cnt; $i++) {
        $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
            $_REQUEST['field_orig'][$i],
            $_REQUEST['field_name'][$i],
            $_REQUEST['field_type'][$i],
            $_REQUEST['field_length'][$i],
            $_REQUEST['field_attribute'][$i],
            isset($_REQUEST['field_collation'][$i])
                ? $_REQUEST['field_collation'][$i]
                : '',
            isset($_REQUEST['field_null'][$i])
                ? $_REQUEST['field_null'][$i]
                : 'NOT NULL',
            $_REQUEST['field_default_type'][$i],
            $_REQUEST['field_default_value'][$i],
            isset($_REQUEST['field_extra'][$i])
                ? $_REQUEST['field_extra'][$i]
                : false,
            isset($_REQUEST['field_comments'][$i])
                ? $_REQUEST['field_comments'][$i]
                : '',
            $key_fields,
            $i,
            isset($_REQUEST['field_move_to'][$i])
                ? $_REQUEST['field_move_to'][$i]
                : ''
        );
    } // end for

    // Builds the primary keys statements and updates the table
    $key_query = '';
    /**
     * this is a little bit more complex
     *
     * @todo if someone selects A_I when altering a column we need to check:
     *  - no other column with A_I
     *  - the column has an index, if not create one
     *
    if (count($key_fields)) {
        $fields = array();
        foreach ($key_fields as $each_field) {
            if (isset($_REQUEST['field_name'][$each_field]) && strlen($_REQUEST['field_name'][$each_field])) {
                $fields[] = PMA_CommonFunctions::getInstance()->backquote($_REQUEST['field_name'][$each_field]);
            }
        } // end for
        $key_query = ', ADD KEY (' . implode(', ', $fields) . ') ';
    }
     */

    // To allow replication, we first select the db to use and then run queries
    // on this db.
    if (! PMA_DBI_select_db($db)) {
        $common_functions->mysqlDie(
            PMA_DBI_getError(),
            'USE ' . $common_functions->backquote($db) . ';',
            '',
            $err_url
        );
    }
    $sql_query = 'ALTER TABLE ' . $common_functions->backquote($table) . ' ';
    $sql_query .= implode(', ', $changes) . $key_query;
    $sql_query .= ';';
    $result    = PMA_DBI_try_query($sql_query);

    if ($result !== false) {
        $message = PMA_Message::success(
            __('Table %1$s has been altered successfully')
        );
        $message->addParam($table);
        $btnDrop = 'Fake';

        /**
         * If comments were sent, enable relation stuff
         */
        include_once 'libraries/transformations.lib.php';

        // update field names in relation
        if (isset($_REQUEST['field_orig']) && is_array($_REQUEST['field_orig'])) {
            foreach ($_REQUEST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_REQUEST['field_name'][$fieldindex] != $fieldcontent) {
                    PMA_REL_renameField(
                        $db, $table, $fieldcontent,
                        $_REQUEST['field_name'][$fieldindex]
                    );
                }
            }
        }

        // update mime types
        if (isset($_REQUEST['field_mimetype'])
            && is_array($_REQUEST['field_mimetype'])
            && $cfg['BrowseMIME']
        ) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                    && strlen($_REQUEST['field_name'][$fieldindex])
                ) {
                    PMA_setMIME(
                        $db, $table, $_REQUEST['field_name'][$fieldindex],
                        $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex]
                    );
                }
            }
        }

        if ($_REQUEST['ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->isSuccess($message->isSuccess());
            $response->addJSON('message', $message);
            $response->addJSON(
                'sql_query',
                $common_functions->getMessage(null, $sql_query)
            );
            exit;
        }

        $active_page = 'tbl_structure.php';
        include 'tbl_structure.php';
    } else {
        $common_functions->mysqlDie('', '', '', $err_url, false);
        // An error happened while inserting/updating a table definition.
        // to prevent total loss of that data, we embed the form once again.
        // The variable $regenerate will be used to restore data in libraries/tbl_properties.inc.php
        if (isset($_REQUEST['orig_field'])) {
            $_REQUEST['field'] = $_REQUEST['orig_field'];
        }

        $regenerate = true;
    }
}

/**
 * No modifications yet required -> displays the table fields
 *
 * $selected comes from multi_submits.inc.php
 */
if ($abort == false) {
    if (! isset($selected)) {
        $common_functions->checkParameters(array('field'));
        $selected[]   = $_REQUEST['field'];
        $selected_cnt = 1;
    } else { // from a multiple submit
        $selected_cnt = count($selected);
    }

    /**
     * @todo optimize in case of multiple fields to modify
     */
    for ($i = 0; $i < $selected_cnt; $i++) {
        $fields_meta[] = PMA_DBI_get_columns($db, $table, $selected[$i], true);
    }
    $num_fields  = count($fields_meta);
    $action      = 'tbl_alter.php';

    // Get more complete field information.
    // For now, this is done to obtain MySQL 4.1.2+ new TIMESTAMP options
    // and to know when there is an empty DEFAULT value.
    // Later, if the analyser returns more information, it
    // could be executed to replace the info given by SHOW FULL COLUMNS FROM.
    /**
     * @todo put this code into a require()
     * or maybe make it part of PMA_DBI_get_columns();
     */

    // We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
    // SHOW FULL COLUMNS says NULL and SHOW CREATE TABLE says NOT NULL (tested
    // in MySQL 4.0.25).

    $show_create_table = PMA_DBI_fetch_value(
        'SHOW CREATE TABLE ' . $common_functions->backquote($db) . '.' . $common_functions->backquote($table),
        0, 1
    );
    $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
    unset($show_create_table);
    /**
     * Form for changing properties.
     */
    include 'libraries/tbl_properties.inc.php';
}
?>
