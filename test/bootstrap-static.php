<?php
/**
 * Bootstrap file for psalm
 *
 * The checks for defined are because psalm --alter can use this file multiple times
 */

declare(strict_types=1);

use PhpMyAdmin\Config\Settings;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

if (! defined('TEST_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('TEST_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// phpcs:disable PSR1.Files.SideEffects
if (! defined('PHPMYADMIN')) {
    define('PHPMYADMIN', true);
}

if (! defined('TESTSUITE')) {
    define('TESTSUITE', true);
}

// phpcs:enable

include_once ROOT_PATH . 'examples/signon-script.php';
require_once ROOT_PATH . 'libraries/constants.php';
require_once AUTOLOAD_FILE;

$settings = new Settings([]);
$GLOBALS['cfg'] = $settings->asArray();
$GLOBALS['server'] = 0;

// phpcs:disable PSR1.Files.SideEffects
if (! defined('PMA_PATH_TO_BASEDIR')) {
    define('PMA_PATH_TO_BASEDIR', '');
}

// phpcs:enable

// for PhpMyAdmin\Plugins\Import\ImportLdi
$GLOBALS['plugin_param'] = 'table';
