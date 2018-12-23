<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Events management.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

/**
 * Include required files
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

/**
 * Do the magic
 */
$_PMA_RTE = 'EVN';
require_once ROOT_PATH . 'libraries/rte/rte_main.inc.php';
