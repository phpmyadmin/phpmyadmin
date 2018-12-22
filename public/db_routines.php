<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Routines management.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Include required files
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

/**
 * Include all other files
 */
require_once ROOT_PATH . 'libraries/check_user_privileges.inc.php';

/**
 * Do the magic
 */
$_PMA_RTE = 'RTN';
require_once ROOT_PATH . 'libraries/rte/rte_main.inc.php';
