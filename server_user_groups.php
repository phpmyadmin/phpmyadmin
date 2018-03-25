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
$relation->getRelationsParam();
if (! $GLOBALS['cfgRelation']['menuswork']) {
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
if (! empty($_REQUEST['deleteUserGroup'])) {
    UserGroups::delete($_REQUEST['userGroup']);
}

/**
 * Add a new user group
 */
if (! empty($_REQUEST['addUserGroupSubmit'])) {
    UserGroups::edit($_REQUEST['userGroup'], true);
}

/**
 * Update a user group
 */
if (! empty($_REQUEST['editUserGroupSubmit'])) {
    UserGroups::edit($_REQUEST['userGroup']);
}

if (isset($_REQUEST['viewUsers'])) {
    // Display users belonging to a user group
    $response->addHTML(UserGroups::getHtmlForListingUsersofAGroup($_REQUEST['userGroup']));
}

if (isset($_REQUEST['addUserGroup'])) {
    // Display add user group dialog
    $response->addHTML(UserGroups::getHtmlToEditUserGroup());
} elseif (isset($_REQUEST['editUserGroup'])) {
    // Display edit user group dialog
    $response->addHTML(UserGroups::getHtmlToEditUserGroup($_REQUEST['userGroup']));
} else {
    // Display user groups table
    $response->addHTML(UserGroups::getHtmlForUserGroupsTable());
}

$response->addHTML('</div>');
