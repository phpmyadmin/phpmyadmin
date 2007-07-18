<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';

/**
 * No rows were selected => show again the query and tell that user.
 */
if ((!isset($rows_to_delete) || !is_array($rows_to_delete)) && !isset($mult_btn)) {
    $disp_message = $strNoRowsSelected;
    $disp_query = '';
    require './sql.php';
    require_once './libraries/footer.inc.php';
}

/**
 * Drop multiple rows if required
 */

// workaround for IE problem:
if (isset($submit_mult_delete_x)) {
    $submit_mult = 'row_delete';
} elseif (isset($submit_mult_change_x)) {
    $submit_mult = 'row_edit';
} elseif (isset($submit_mult_export_x)) {
    $submit_mult = 'row_export';
}

// garvin: If the 'Ask for confirmation' button was pressed, this can only come from 'delete' mode,
// so we set it straight away.
if (isset($mult_btn)) {
    $submit_mult = 'row_delete';
}

switch($submit_mult) {
    case 'row_delete':
    case 'row_edit':
    case 'row_export':
        // leave as is
        break;

    case $GLOBALS['strExport']:
        $submit_mult = 'row_export';
        break;

    case $GLOBALS['strDelete']:
    case $GLOBALS['strKill']:
        $submit_mult = 'row_delete';
        break;

    default:
    case $GLOBALS['strEdit']:
        $submit_mult = 'row_edit';
        break;
}

if ($submit_mult == 'row_edit') {
    $js_to_run = 'tbl_change.js';
}

if ($submit_mult == 'row_delete' || $submit_mult == 'row_export') {
    $js_to_run = 'functions.js';
}

require_once './libraries/header.inc.php';

if (!empty($submit_mult)) {
    switch($submit_mult) {
        case 'row_edit':
            $primary_key = array();
            // garvin: As we got the fields to be edited from the 'rows_to_delete' checkbox, we use the index of it as the
            // indicating primary key. Then we built the array which is used for the tbl_change.php script.
            foreach ($rows_to_delete AS $i_primary_key => $del_query) {
                $primary_key[] = urldecode($i_primary_key);
            }

            $active_page = 'tbl_change.php';
            include './tbl_change.php';
            break;

        case 'row_export':
            // Needed to allow SQL export
            $single_table = TRUE;

            $primary_key = array();
            //$sql_query = urldecode($sql_query);
            // garvin: As we got the fields to be edited from the 'rows_to_delete' checkbox, we use the index of it as the
            // indicating primary key. Then we built the array which is used for the tbl_change.php script.
            foreach ($rows_to_delete AS $i_primary_key => $del_query) {
                $primary_key[] = urldecode($i_primary_key);
            }

            $active_page = 'tbl_export.php';
            include './tbl_export.php';
            break;

        case 'row_delete':
        default:
            $action = 'tbl_row_action.php';
            $err_url = 'tbl_row_action.php?' . PMA_generate_common_url($db, $table);
            if (! isset($mult_btn)) {
                $original_sql_query = $sql_query;
                $original_url_query = $url_query;
            }
            require './libraries/mult_submits.inc.php';
            $url_query = PMA_generate_common_url($db, $table)
                       . '&amp;goto=tbl_sql.php';


            /**
             * Show result of multi submit operation
             */
            // sql_query is not set when user does not confirm multi-delete
            if ((!empty($submit_mult) || isset($mult_btn)) && ! empty($sql_query)) {
                $disp_message = $strSuccess;
                $disp_query = $sql_query;
            }

            if (isset($original_sql_query)) {
                $sql_query = $original_sql_query;
            }

            if (isset($original_url_query)) {
                $url_query = $original_url_query;
            }

            // this is because sql.php could call tbl_structure
            // which would think it needs to call mult_submits.inc.php:
            unset($submit_mult);
            unset($mult_btn);

            $active_page = 'sql.php';
            require './sql.php';

            /**
             * Displays the footer
             */
            require_once './libraries/footer.inc.php';
        break;
    }
}
?>
