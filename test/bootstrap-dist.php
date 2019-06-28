<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Bootstrap for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\Theme;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
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
    get_include_path() . PATH_SEPARATOR . dirname((string) realpath("../index.php"))
);

// Setting constants for testing
define('PHPMYADMIN', 1);
define('TESTSUITE', 1);

// Selenium tests setup
$test_defaults = [
    'TESTSUITE_SERVER' => 'localhost',
    'TESTSUITE_USER' => 'root',
    'TESTSUITE_PASSWORD' => '',
    'TESTSUITE_DATABASE' => 'test',
    'TESTSUITE_PORT' => 3306,
    'TESTSUITE_URL' => 'http://localhost/phpmyadmin/',
    'TESTSUITE_SELENIUM_HOST' => '',
    'TESTSUITE_SELENIUM_PORT' => '4444',
    'TESTSUITE_SELENIUM_BROWSER' => 'firefox',
    'TESTSUITE_SELENIUM_COVERAGE' => '',
    'TESTSUITE_BROWSERSTACK_USER' => '',
    'TESTSUITE_BROWSERSTACK_KEY' => '',
    'TESTSUITE_FULL' => '',
    'CI_MODE' => '',
];
if (PHP_SAPI == 'cli') {
    foreach ($test_defaults as $varname => $defvalue) {
        $envvar = getenv($varname);
        if ($envvar) {
            $GLOBALS[$varname] = $envvar;
        } else {
            $GLOBALS[$varname] = $defvalue;
        }
    }
}

require_once ROOT_PATH . 'libraries/vendor_config.php';
require_once AUTOLOAD_FILE;
Loader::loadFunctions();
$GLOBALS['PMA_Config'] = new Config();
// Initialize PMA_VERSION variable
define('PMA_VERSION', $GLOBALS['PMA_Config']->get('PMA_VERSION'));
define('PMA_MAJOR_VERSION', $GLOBALS['PMA_Config']->get('PMA_MAJOR_VERSION'));

/* Ensure default language is active */
LanguageManager::getInstance()->getLanguage('en')->activate();

/* Load Database interface */
$GLOBALS['dbi'] = DatabaseInterface::load(new DbiDummy());

// Set proxy information from env, if available
$http_proxy = getenv('http_proxy');
if (PHP_SAPI == 'cli' && $http_proxy && ($url_info = parse_url($http_proxy))) {
    define('PROXY_URL', $url_info['host'] . ':' . $url_info['port']);
    define('PROXY_USER', empty($url_info['user']) ? '' : $url_info['user']);
    define('PROXY_PASS', empty($url_info['pass']) ? '' : $url_info['pass']);
} else {
    define('PROXY_URL', '');
    define('PROXY_USER', '');
    define('PROXY_PASS', '');
}

// Ensure we have session started
session_start();

// Standard environment for tests
$_SESSION[' PMA_token '] = 'token';
$GLOBALS['PMA_Theme'] = Theme::load(ROOT_PATH . 'themes/pmahomme');
$_SESSION['tmpval']['pftext'] = 'F';
$GLOBALS['lang'] = 'en';
