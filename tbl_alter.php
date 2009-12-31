<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Alter one or more table columns/fields
 *
 * linked from table_structure, uses libraries/tbl_properties.inc.php to display
 * form and handles this form data
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

$GLOBALS['js_include'][] = 'functions.js';
require_once './libraries/header.inc.php';

// Check parameters
PMA_checkParameters(array('db', 'table'));

/**
 * Gets tables informations
 */
require_once './libraries/tbl_common.php';
require_once './libraries/tbl_info.inc.php';

$active_page = 'tbl_structure.php';

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);


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
            $_REQUEST['field_default_orig'][$i]
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
                $fields[] = PMA_backquote($_REQUEST['field_name'][$each_field]);
            }
        } // end for
        $key_query = ', ADD KEY (' . implode(', ', $fields) . ') ';
    }
     */

    // To allow replication, we first select the db to use and then run queries
    // on this db.
    PMA_DBI_select_db($db) or PMA_mysqlDie(PMA_DBI_getError(), 'USE ' . PMA_backquote($db) . ';', '', $err_url);
    // Optimization fix - 2 May 2001 - Robbat2
    $sql_query = 'ALTER TABLE ' . PMA_backquote($table) . ' ' . implode(', ', $changes) . $key_query;
    $result    = PMA_DBI_try_query($sql_query);

    if ($result !== false) {
        $message = PMA_Message::success('strTableAlteredSuccessfully');
        $message->addParam($table);
        $btnDrop = 'Fake';

        /**
         * If comments were sent, enable relation stuff
         */
        require_once './libraries/relation.lib.php';
        require_once './libraries/transformations.lib.php';

        // updaet field names in relation
        if (isset($_REQUEST['field_orig']) && is_array($_REQUEST['field_orig'])) {
            foreach ($_REQUEST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_REQUEST['field_name'][$fieldindex] != $fieldcontent) {
                    PMA_REL_renameField($db, $table, $fieldcontent,
                        $_REQUEST['field_name'][$fieldindex]);
                }
            }
        }

        // update mime types
        if (isset($_REQUEST['field_mimetype'])
         && is_array($_REQUEST['field_mimetype'])
         && $cfg['BrowseMIME']) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                 && strlen($_REQUEST['field_name'][$fieldindex])) {
                    PMA_setMIME($db, $table, $_REQUEST['field_name'][$fieldindex],
                        $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex]);
                }
            }
        }

        $active_page = 'tbl_structure.php';
        require './tbl_structure.php';
    } else {
        PMA_mysqlDie('', '', '', $err_url, false);
        // garvin: An error happened while inserting/updating a table definition.
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
        PMA_checkParameters(array('field'));
        $selected[]   = $_REQUEST['field'];
        $selected_cnt = 1;
    } else { // from a multiple submit
        $selected_cnt = count($selected);
    }

    /**
     * @todo optimize in case of multiple fields to modify
     */
    for ($i = 0; $i < $selected_cnt; $i++) {
        $_REQUEST['field'] = PMA_sqlAddslashes($selected[$i], true);
        $result        = PMA_DBI_query('SHOW FULL FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db) . ' LIKE \'' . $_REQUEST['field'] . '\';');
        $fields_meta[] = PMA_DBI_fetch_assoc($result);
        PMA_DBI_free_result($result);
    }
    $num_fields  = count($fields_meta);
    $action      = 'tbl_alter.php';

    // Get more complete field information.
    // For now, this is done to obtain MySQL 4.1.2+ new TIMESTAMP options
    // and to know when there is an empty DEFAULT value.
    // Later, if the analyser returns more information, it
    // could be executed to replace the info given by SHOW FULL FIELDS FROM.
    /**
     * @todo put this code into a require()
     * or maybe make it part of PMA_DBI_get_fields();
     */

    // We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
    // SHOW FULL FIELDS says NULL and SHOW CREATE TABLE says NOT NULL (tested
    // in MySQL 4.0.25).

    $show_create_table = PMA_DBI_fetch_value('SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table), 0, 1);
    $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
    unset($show_create_table);
    /**
     * Form for changing properties.
     */
    require './libraries/tbl_properties.inc.php';
}


/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
