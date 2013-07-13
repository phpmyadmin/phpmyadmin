<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_privileges.lib.php';

PMA_getRelationsParam();
if (! $GLOBALS['cfgRelation']['menuswork']) {
    exit;
}

$response = PMA_Response::getInstance();
$response->addHTML('<div>');
$response->addHTML(PMA_getHtmlForSubMenusOnUsersPage('server_user_groups.php'));

/**
 * Delete user group
 */
if (! empty($_REQUEST['deleteUserGroup'])) {
    PMA_deleteUserGroup($_REQUEST['userGroup']);
}

/**
 * Add a new user group
 */
if (! empty($_REQUEST['addUserGroupSubmit'])) {
    PMA_editUserGroup($_REQUEST['userGroup'], true);
}

/**
 * Update a user group
 */
if (! empty($_REQUEST['editUserGroupSubmit'])) {
    PMA_editUserGroup($_REQUEST['userGroup']);
}

if (isset($_REQUEST['viewUsers'])) {
    // Display users belonging to a user group
    $response->addHTML(PMA_getHtmlForListingUsersofAGroup($_REQUEST['userGroup']));
}

if (isset($_REQUEST['addUserGroup'])) {
    // Display add user group dialog
    $response->addHTML(PMA_getHtmlToEditUserGroup());
} elseif (isset($_REQUEST['editUserGroup'])) {
    // Display edit user group dialog
    $response->addHTML(PMA_getHtmlToEditUserGroup($_REQUEST['userGroup']));
} else {
    // Display user groups table
    $response->addHTML(PMA_getHtmlForUserGroupsTable());
}

$response->addHTML('</div>');
?>