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
        $is_db = @PMA_DBI_select_db($db);
    }
    if (empty($db) || !$is_db) {
        if (!isset($is_transformation_wrapper)) {
            PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'main.php?' . PMA_generate_common_url('', '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        }
        exit;
    }
} // end if (ensures db exists)
if (!isset($is_table) || !$is_table) {
    // Not a valid table name -> back to the db_details.php
    if (!empty($table)) {
        $is_table = PMA_DBI_try_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\';', NULL, PMA_DBI_QUERY_STORE);
    }
    if (empty($table)
        || !($is_table && @PMA_DBI_num_rows($is_table))) {
        $redirect = TRUE;
        if (!isset($is_transformation_wrapper)) {
            $redirect = TRUE;
            if (!empty($table)) {
                PMA_DBI_free_result($is_table);
                // SHOW TABLES doesn't show temporary tables, so try select (as it can happen just in case temporary table, it should be fast):
                $is_table2 = PMA_DBI_try_query('SELECT COUNT(*) FROM `' . PMA_sqlAddslashes($table, TRUE) . '`;', NULL, PMA_DBI_QUERY_STORE);
                $redirect = !($is_table2 && @PMA_DBI_num_rows($is_table2));
                PMA_DBI_free_result($is_table2);
            }

            if ($redirect) {
                PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'db_details.php?' . PMA_generate_common_url($db, '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
            }
        }
        if ($redirect) exit;
    } else if (isset($is_table)) {
        PMA_DBI_free_result($is_table);
    }
} // end if (ensures table exists)
?>
