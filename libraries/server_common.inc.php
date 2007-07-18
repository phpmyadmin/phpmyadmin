<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @uses    PMA_generate_common_url()
 * @uses    PMA_isSuperuser()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_DBI_fetch_result()
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    $userlink
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';

/**
 * Handles some variables that may have been sent by the calling script
 * Note: this can be called also from the db panel to get the privileges of
 *       a db, in which case we want to keep displaying the tabs of
 *       the Database panel
 */
if (empty($viewing_mode)) {
    $db = $table = '';
}

/**
 * Set parameters for links
 */
$url_query = PMA_generate_common_url($db);

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url = 'main.php' . $url_query;

/**
 * Displays the headers
 */
require_once './libraries/header.inc.php';

/**
 * @global boolean Checks for superuser privileges
 */
$is_superuser = PMA_isSuperuser();

// now, select the mysql db
if ($is_superuser) {
    PMA_DBI_select_db('mysql', $userlink);
}

/**
 * @global array binary log files
 */
$binary_logs = PMA_DBI_fetch_result('SHOW MASTER LOGS', 'Log_name', null, null,
    PMA_DBI_QUERY_STORE);
?>
