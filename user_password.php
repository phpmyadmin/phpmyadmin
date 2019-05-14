<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays and handles the form where the user can change his password
 * linked from index.php
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Display\ChangePassword;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPassword;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $cfg;

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

/** @var Response $response */
$response = $container->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('server_privileges.js');
$scripts->addFile('vendor/zxcvbn.js');

$template = new Template();
$relation = new Relation($dbi);
$relationCleanup = new RelationCleanup($dbi, $relation);
$serverPrivileges = new Privileges($template, $dbi, $relation, $relationCleanup);
$userPassword = new UserPassword($serverPrivileges);

/**
 * Displays an error message and exits if the user isn't allowed to use this
 * script
 */
if (! $cfg['ShowChgPassword']) {
    $cfg['ShowChgPassword'] = $dbi->selectDb('mysql');
}
if ($cfg['Server']['auth_type'] == 'config' || ! $cfg['ShowChgPassword']) {
    Message::error(
        __('You don\'t have sufficient privileges to be here right now!')
    )->display();
    exit;
} // end if

/**
 * If the "change password" form has been submitted, checks for valid values
 * and submit the query or logout
 */
if (isset($_POST['nopass'])) {
    if ($_POST['nopass'] == '1') {
        $password = '';
    } else {
        $password = $_POST['pma_pw'];
    }
    $change_password_message = $userPassword->setChangePasswordMsg();
    $msg = $change_password_message['msg'];
    if (! $change_password_message['error']) {
        $userPassword->changePassword($password, $msg, $change_password_message);
    } else {
        $userPassword->getChangePassMessage($change_password_message);
    }
}

/**
 * If the "change password" form hasn't been submitted or the values submitted
 * aren't valid -> displays the form
 */

// Displays an error message if required
if (isset($msg)) {
    $msg->display();
    unset($msg);
}

echo ChangePassword::getHtml('change_pw', $username, $hostname);
exit;
