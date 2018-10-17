<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Logout script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;

require_once 'libraries/common.inc.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST' || $token_mismatch) {
    Core::sendHeaderLocation('./index.php');
} else {
    $auth_plugin->logOut();
}
