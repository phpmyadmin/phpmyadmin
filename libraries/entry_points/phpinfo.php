<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpinfo() wrapper to allow displaying only when configured to do so.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('PHPMYADMIN')) {
    exit;
}

$response = PhpMyAdmin\Response::getInstance();
$response->disable();
$response->getHeader()->sendHttpHeaders();

/**
 * Displays PHP information
 */
if ($GLOBALS['cfg']['ShowPhpInfo']) {
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
}
