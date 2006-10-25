<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require_once './libraries/common.lib.php';
require_once './libraries/Table.class.php';

// Check parameters

PMA_checkParameters(array('db', 'table'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_sql.php?' . PMA_generate_common_url($db, $table);


/**
 * Selects the database to work with
 */
PMA_DBI_select_db($db);

/**
 * A target table name has been sent to this script -> do the work
 */
if (isset($new_name) && trim($new_name) != '') {
    if ($db == $target_db && $table == $new_name) {
        $message   = (isset($submit_move) ? $strMoveTableSameNames : $strCopyTableSameNames);
    } else {
        PMA_Table::moveCopy($db, $table, $target_db, $new_name, $what, isset($submit_move), 'one_table');
        $js_to_run = 'functions.js';
        $message   = (isset($submit_move) ? $strMoveTableOK : $strCopyTableOK);
        $message   = sprintf($message, htmlspecialchars($table), htmlspecialchars($new_name));
        $reload    = 1;
        /* Check: Work on new table or on old table? */
        if (isset($submit_move)) {
            $db        = $target_db;
            $table     = $new_name;
        } else {
            $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
            if (isset($switch_to_new) && $switch_to_new == 'true') {
                setcookie('pma_switch_to_new', 'true', 0, $GLOBALS['cookie_path'], '', $GLOBALS['is_https']);
                $db        = $target_db;
                $table     = $new_name;
            } else {
                setcookie('pma_switch_to_new', '', 0, $GLOBALS['cookie_path'], '', $GLOBALS['is_https']);
            }
        }
    }
    require_once './libraries/header.inc.php';
} // end is target table name


/**
 * No new name for the table!
 */
else {
    require_once './libraries/header.inc.php';
    PMA_mysqlDie($strTableEmpty, '', '', $err_url);
}


/**
 * Back to the calling script
 */

require './tbl_sql.php';
?>
