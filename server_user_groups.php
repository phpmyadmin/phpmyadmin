<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays the 'User groups' sub page under 'Users' page.
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\UserGroups;
use PhpMyAdmin\Server\Users;

require_once 'libraries/common.inc.php';

$relation = new Relation();
$cfgRelation = $relation->getRelationsParam();
if (! $cfgRelation['menuswork']) {
    exit;
}

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_user_groups.js');

/**
 * Only allowed to superuser
 */
if (! $GLOBALS['dbi']->isSuperuser()) {
    $response->addHTML(
        PhpMyAdmin\Message::error(__('No Privileges'))
            ->getDisplay()
    );
    exit;
}

$response->addHTML('<div>');
$response->addHTML(Users::getHtmlForSubMenusOnUsersPage('server_user_groups.php'));

/**
 * Delete user group
 */
if (! empty($_POST['deleteUserGroup'])) {
    UserGroups::delete($_POST['userGroup']);
}

/**
 * Add a new user group
 */
if (! empty($_POST['addUserGroupSubmit'])) {
    UserGroups::edit($_POST['userGroup'], true);
}

/**
 * Update a user group
 */
if (! empty($_POST['editUserGroupSubmit'])) {
    UserGroups::edit($_POST['userGroup']);
}

if (isset($_POST['viewUsers'])) {
    // Display users belonging to a user group
    $response->addHTML(UserGroups::getHtmlForListingUsersofAGroup($_POST['userGroup']));
}

if (isset($_GET['addUserGroup'])) {
    // Display add user group dialog
    $response->addHTML(UserGroups::getHtmlToEditUserGroup());
} elseif (isset($_POST['editUserGroup'])) {
    // Display edit user group dialog
    $response->addHTML(UserGroups::getHtmlToEditUserGroup($_POST['userGroup']));
} else {
    // Display user groups table
    $response->addHTML(UserGroups::getHtmlForUserGroupsTable());
}

$response->addHTML('</div>');
