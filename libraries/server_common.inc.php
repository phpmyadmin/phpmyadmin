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
$GLOBALS['url_query'] = PMA_URL_getCommon(array('db' => $db));

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url = 'index.php' . $GLOBALS['url_query'];

/**
 * @global boolean Checks for superuser privileges
 */
$GLOBALS['is_superuser'] = $GLOBALS['dbi']->isSuperuser();
$GLOBALS['is_grantuser'] = $GLOBALS['dbi']->isUserType('grant');
$GLOBALS['is_createuser'] = $GLOBALS['dbi']->isUserType('create');

// now, select the mysql db
if ($GLOBALS['is_superuser'] && ! PMA_DRIZZLE) {
    $GLOBALS['dbi']->selectDb('mysql', $GLOBALS['userlink']);
}

PMA_Util::checkParameters(
    array('is_superuser', 'url_query'), false
);

/**
 * shared functions for server page
 */
require_once './libraries/server_common.lib.php';

?>
