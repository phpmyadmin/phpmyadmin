<?php
/**
 * Bootstrap for phpMyAdmin tests
 */

declare(strict_types=1);

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

/**
 * Set precision to sane value, with higher values
 * things behave slightly unexpectedly, for example
 * round(1.2, 2) returns 1.199999999999999956.
 */
ini_set('precision', '14');

// Let PHP complain about all errors
error_reporting(E_ALL);

// Ensure PHP has set timezone
date_default_timezone_set('UTC');

// Adding phpMyAdmin sources to include path
set_include_path(
    get_include_path() . PATH_SEPARATOR . dirname((string) realpath('../index.php'))
);

// Setting constants for testing
// phpcs:disable PSR1.Files.SideEffects
if (! defined('PHPMYADMIN')) {
    define('PHPMYADMIN', 1);
    define('TESTSUITE', 1);
}
// phpcs:enable

require_once ROOT_PATH . 'libraries/vendor_config.php';
require_once AUTOLOAD_FILE;
Loader::loadFunctions();

$GLOBALS['PMA_Config'] = new Config();
$GLOBALS['PMA_Config']->set('environment', 'development');
$GLOBALS['cfg']['environment'] = 'development';

/* Load Database interface */
$GLOBALS['dbi'] = DatabaseInterface::load(new DbiDummy());
