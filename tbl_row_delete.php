<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');

/**
 * Drop multiple rows if required
 */

// workaround for IE problem:
if (isset($submit_mult_x)) {
    $submit_mult = 'row_delete';
} elseif (isset($submit_mult_edit_x)) {
    $submit_mult = 'row_edit';
}

// garvin: If the 'Ask for confirmation' button was pressed, this can only come from 'delete' mode,
// so we set it straight away.
if (isset($mult_btn)) {
    $submit_mult = 'row_delete';
}

if ($submit_mult == 'row_edit') {
    $js_to_run = 'tbl_change.js';
}

require_once('./header.inc.php');

if (!empty($submit_mult)) {
    switch($submit_mult) {
        case 'row_edit':
            if (isset($rows_to_delete) && is_array($rows_to_delete)) {
                $primary_key = array();
                // garvin: As we got the fields to be edited from the 'rows_to_delete' checkbox, we use the index of it as the
                // indicating primary key. Then we built the array which is used for the tbl_change.php script.
                foreach($rows_to_delete AS $i_primary_key => $del_query) {
                    $primary_key[] = urldecode($i_primary_key);
                }
                
                include './tbl_change.php';
            }
            break;
        
        case 'row_delete':
        default:
            if ((isset($rows_to_delete) && is_array($rows_to_delete))
                || isset($mult_btn)) {
                $action = 'tbl_row_delete.php';
                $err_url = 'tbl_row_delete.php?' . PMA_generate_common_url($db, $table);
                if (!isset($mult_btn)) {
                    $original_sql_query = $sql_query;
                    $original_url_query = $url_query;
                    $original_pos       = $pos;
                }
                require('./mult_submits.inc.php');
            }
            $url_query = PMA_generate_common_url($db, $table)
                       . '&amp;goto=tbl_properties.php';
            
            
            /**
             * Show result of multi submit operation
             */
            if ((!empty($submit_mult) && isset($rows_to_delete))
                || isset($mult_btn)) {
                PMA_showMessage($strSuccess);
            }
            
            if (isset($original_sql_query)) {
                $sql_query = $original_sql_query;
            }
            
            if (isset($original_url_query)) {
                $url_query = $original_url_query;
            }
            
            if (isset($original_pos)) {
                $pos       = $original_pos;
            }
            
            require('./sql.php');
            
            /**
             * Displays the footer
             */
            require_once('./footer.inc.php');
        break;
    }
}
?>
