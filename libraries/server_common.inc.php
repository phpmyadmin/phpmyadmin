<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Shared code for server pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
$url_query = PMA_URL_getCommon($db);

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url = 'index.php' . $url_query;

/**
 * @global boolean Checks for superuser privileges
 */
$is_superuser = $GLOBALS['dbi']->isSuperuser();

// now, select the mysql db
if ($is_superuser && ! PMA_DRIZZLE) {
    $GLOBALS['dbi']->selectDb('mysql', $userlink);
}

PMA_Util::checkParameters(
    array('is_superuser', 'url_query'), false
);

/**
 * shared functions for server page
 */
require_once './libraries/server_common.lib.php';

?>
