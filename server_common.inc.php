<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');

/**
 * Handles some variables that may have been sent by the calling script
 */
unset($db, $table);

/**
 * Set parameters for links
 */
$url_query = PMA_generate_common_url();

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url = 'main.php' . $url_query;

/**
 * Displays the headers
 */
require_once('./header.inc.php');

/**
 * Checks for superuser privileges
 */
// We were checking privileges with 'USE mysql' but users with the global
// priv CREATE TEMPORARY TABLES or LOCK TABLES can do a 'USE mysql'
// (even if they cannot see the tables)

$is_superuser = PMA_DBI_try_query('SELECT COUNT(*) FROM mysql.user');

// now, select the mysql db
if ($is_superuser) {
    PMA_DBI_free_result($is_superuser);
    PMA_DBI_select_db('mysql', $userlink);
    $is_superuser = TRUE;
} else {
    $is_superuser = FALSE;
}

$has_binlogs = FALSE;
$binlogs = PMA_DBI_try_query('SHOW MASTER LOGS', NULL, PMA_DBI_QUERY_STORE);
if ($binlogs) {
    if (PMA_DBI_num_rows($binlogs) > 0) {
        $binary_logs = array();
        while ($row = PMA_DBI_fetch_array($binlogs)) {
            $binary_logs[] = $row[0];
        }
        $has_binlogs = TRUE;
    }
    PMA_DBI_free_result($binlogs);
}
unset($binlogs);
?>
