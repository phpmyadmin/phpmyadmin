<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Ensure the database and the table exist (else move to the "parent" script)
 * and display headers
 */
if (!isset($is_db) || !$is_db) {
    // Not a valid db name -> back to the welcome page
    if (!empty($db)) {
        $is_db = @PMA_mysql_select_db($db);
    }
    if (empty($db) || !$is_db) {
        if (!isset($is_transformation_wrapper)) {
            header('Location: ' . $cfg['PmaAbsoluteUri'] . 'main.php?' . PMA_generate_common_url('', '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        }
        exit;
    }
} // end if (ensures db exists)
if (!isset($is_table) || !$is_table) {
    // Not a valid table name -> back to the db_details.php
    if (!empty($table)) {
        $is_table = @PMA_mysql_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'');
    }
    if (empty($table)
        || !($is_table && @mysql_numrows($is_table))) {
        if (!isset($is_transformation_wrapper)) {
            header('Location: ' . $cfg['PmaAbsoluteUri'] . 'db_details.php?' . PMA_generate_common_url($db, '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        }
        exit;
    } else if (isset($is_table)) {
        mysql_free_result($is_table);
    }
} // end if (ensures table exists)
?>
