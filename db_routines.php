<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Routines management.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\CheckUserPrivileges;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Include required files
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

$checkUserPrivileges = new CheckUserPrivileges($GLOBALS['dbi']);
$checkUserPrivileges->getPrivileges();

/**
 * Do the magic
 */
$_PMA_RTE = 'RTN';
require_once ROOT_PATH . 'libraries/rte/rte_main.inc.php';
