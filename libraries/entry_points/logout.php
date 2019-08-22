<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Logout script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\Plugins\AuthenticationPlugin;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $auth_plugin, $token_mismatch;

if ($_SERVER['REQUEST_METHOD'] != 'POST' || $token_mismatch) {
    Core::sendHeaderLocation('./index.php');
} else {
    /** @var AuthenticationPlugin $auth_plugin */
    $auth_plugin->logOut();
}
