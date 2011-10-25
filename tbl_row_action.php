<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handle row specifc actions like edit, delete, export
 *
 * @package PhpMyAdmin
 */


/**
 * do not globalize/import request variables
 * can only be enabled if all included files are switched superglobals too
 * but leave this here to show that this file is 'superglobalized'
define('PMA_NO_VARIABLES_IMPORT', true);
 */

/**
 *
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';

/**
 * No rows were selected => show again the query and tell that user.
 */
if (! PMA_isValid($_REQUEST['rows_to_delete'], 'array')
 && ! isset($_REQUEST['mult_btn'])) {
    $disp_message = __('No rows selected');
    $disp_query = '';
    include './sql.php';
    include './libraries/footer.inc.php';
}

if (isset($_REQUEST['submit_mult'])) {
    $submit_mult = $_REQUEST['submit_mult'];
// workaround for IE problem:
} elseif (isset($_REQUEST['submit_mult_delete_x'])) {
    $submit_mult = 'row_delete';
} elseif (isset($_REQUEST['submit_mult_change_x'])) {
    $submit_mult = 'row_edit';
} elseif (isset($_REQUEST['submit_mult_export_x'])) {
    $submit_mult = 'row_export';
}

// If the 'Ask for confirmation' button was pressed, this can only come
// from 'delete' mode, so we set it straight away.
if (isset($_REQUEST['mult_btn'])) {
    $submit_mult = 'row_delete';
}

switch($submit_mult) {
    case 'row_delete':
    case 'row_edit':
    case 'row_export':
        // leave as is
        break;

    case 'export':
        $submit_mult = 'row_export';
        break;

    case 'delete':
        $submit_mult = 'row_delete';
        break;

    default:
    case 'edit':
        $submit_mult = 'row_edit';
        break;
}

if (!empty($submit_mult)) {
    switch($submit_mult) {
        case 'row_edit':
            // As we got the rows to be edited from the
            // 'rows_to_delete' checkbox, we use the index of it as the
            // indicating WHERE clause. Then we build the array which is used
            // for the tbl_change.php script.
            $where_clause = array();
            foreach ($_REQUEST['rows_to_delete'] as $i_where_clause => $del_query) {
                $where_clause[] = urldecode($i_where_clause);
            }

            $active_page = 'tbl_change.php';
            include './tbl_change.php';
            break;

        case 'row_export':
            // Needed to allow SQL export
            $single_table = true;

            // As we got the rows to be exported from the
            // 'rows_to_delete' checkbox, we use the index of it as the
            // indicating WHERE clause. Then we build the array which is used
            // for the tbl_change.php script.
            $where_clause = array();
            foreach ($_REQUEST['rows_to_delete'] as $i_where_clause => $del_query) {
                $where_clause[] = urldecode($i_where_clause);
            }

            $active_page = 'tbl_export.php';
            include './tbl_export.php';
            break;

        case 'row_delete':
        default:
            $action = 'tbl_row_action.php';
            $err_url = 'tbl_row_action.php' . PMA_generate_common_url($GLOBALS['url_params']);
            if (! isset($_REQUEST['mult_btn'])) {
                $original_sql_query = $sql_query;
                if (! empty($url_query)) {
                    $original_url_query = $url_query;
                }
            }
            include './libraries/mult_submits.inc.php';
            $_url_params = $GLOBALS['url_params'];
            $_url_params['goto'] = 'tbl_sql.php';
            $url_query = PMA_generate_common_url($_url_params);


            /**
             * Show result of multi submit operation
             */
            // sql_query is not set when user does not confirm multi-delete
            if ((!empty($submit_mult) || isset($_REQUEST['mult_btn'])) && ! empty($sql_query)) {
                $disp_message = __('Your SQL query has been executed successfully');
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
            unset($submit_mult, $_REQUEST['mult_btn']);

            $active_page = 'sql.php';
            include './sql.php';

            /**
             * Displays the footer
             */
            include './libraries/footer.inc.php';
            break;
    }
}
?>
