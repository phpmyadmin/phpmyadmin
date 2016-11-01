<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Logout script
 *
 * @package PhpMyAdmin
 */
require_once 'libraries/common.inc.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST' || $token_mismatch) {
    PMA_sendHeaderLocation('./index.php');
} else {
    $auth_plugin->logOut();
}

