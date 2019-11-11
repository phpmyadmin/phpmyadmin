<?php
/**
 * Displays the 'User groups' sub page under 'Users' page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\UserGroups;
use PhpMyAdmin\Server\Users;
use PhpMyAdmin\Url;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
$cfgRelation = $relation->getRelationsParam();
if (! $cfgRelation['menuswork']) {
    exit;
}

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('server/user_groups.js');

/**
 * Only allowed to superuser
 */
if (! $dbi->isSuperuser()) {
    $response->addHTML(
        PhpMyAdmin\Message::error(__('No Privileges'))
            ->getDisplay()
    );
    exit;
}

$response->addHTML('<div class="container-fluid">');
$response->addHTML(Users::getHtmlForSubMenusOnUsersPage(Url::getFromRoute('/server/user_groups')));

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
