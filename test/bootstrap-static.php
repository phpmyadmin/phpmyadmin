<?php
/**
 * Bootstrap file for psalm
 */

declare(strict_types=1);

use PhpMyAdmin\MoTranslator\Loader;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
define('TESTSUITE', true);
// phpcs:enable

$cfg = [];

include_once ROOT_PATH . 'examples/signon-script.php';
require_once ROOT_PATH . 'libraries/config.default.php';
require_once ROOT_PATH . 'libraries/vendor_config.php';
require_once AUTOLOAD_FILE;

$GLOBALS['cfg'] = $cfg;
$GLOBALS['server'] = 0;

// phpcs:disable PSR1.Files.SideEffects
define('PMA_PATH_TO_BASEDIR', '');
define('PMA_USR_BROWSER_AGENT', 'CHROME');
define('PMA_USR_BROWSER_VER', 1);
define('PMA_USR_OS', 'os');
define('PMA_IS_WINDOWS', false);
// phpcs:enable

Loader::loadFunctions();

// for PhpMyAdmin\Plugins\Import\ImportLdi
$GLOBALS['plugin_param'] = 'table';
