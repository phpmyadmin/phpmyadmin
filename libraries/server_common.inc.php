<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
if ($is_superuser && !PMA_DRIZZLE) {
    PMA_DBI_select_db('mysql', $userlink);
}

/**
 * @global array binary log files
 */
$binary_logs = PMA_DRIZZLE
    ? null
    : PMA_DBI_fetch_result('SHOW MASTER LOGS', 'Log_name', null, null, PMA_DBI_QUERY_STORE);
?>
