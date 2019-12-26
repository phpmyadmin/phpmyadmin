<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Gets core libraries and defines some variables
 */
require_once ROOT_PATH . 'libraries/common.inc.php';
$response = PhpMyAdmin\Response::getInstance();
$response->disable();
$response->getHeader()->sendHttpHeaders();

/**
 * Displays PHP information
 */
if ($GLOBALS['cfg']['ShowPhpInfo']) {
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
}
