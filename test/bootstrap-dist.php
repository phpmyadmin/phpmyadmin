<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Bootstrap for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */

/**
 * Set precision to sane value, with higher values
 * things behave slightly unexpectedly, for example
 * round(1.2, 2) returns 1.199999999999999956.
 */
ini_set('precision', 14);

// Let PHP complain about all errors
error_reporting(E_ALL);

// Ensure PHP has set timezone
date_default_timezone_set('UTC');

// Adding phpMyAdmin sources to include path
set_include_path(
    get_include_path() . PATH_SEPARATOR . dirname(realpath("../index.php"))
);

// Setting constants for testing
define('PHPMYADMIN', 1);
define('TESTSUITE', 1);
define('PMA_MYSQL_INT_VERSION', 55000);
define('PMA_MYSQL_STR_VERSION', '5.50.00');
define('PMA_MYSQL_VERSION_COMMENT', 'MySQL Community Server (GPL)');

// Selenium tests setup
$test_defaults = array(
    'TESTSUITE_SERVER' => 'localhost',
    'TESTSUITE_USER' => 'root',
    'TESTSUITE_PASSWORD' => '',
    'TESTSUITE_DATABASE' => 'test',
    'TESTSUITE_URL' => 'http://localhost/phpmyadmin/',
    'TESTSUITE_SELENIUM_HOST' => '',
    'TESTSUITE_SELENIUM_PORT' => '4444',
    'TESTSUITE_SELENIUM_BROWSER' => 'firefox',
    'TESTSUITE_SELENIUM_COVERAGE' => '',
    'TESTSUITE_BROWSERSTACK_USER' => '',
    'TESTSUITE_BROWSERSTACK_KEY' => '',
    'TESTSUITE_FULL' => '',
);
foreach ($test_defaults as $varname => $defvalue) {
    $envvar = getenv($varname);
    if ($envvar) {
        $GLOBALS[$varname] = $envvar;
    } else {
        $GLOBALS[$varname] = $defvalue;
    }
}

require_once 'libraries/vendor_config.php';
require_once 'libraries/autoloader.php';
require_once 'libraries/core.lib.php';
$CFG = new PMA\libraries\Config();
// Initialize PMA_VERSION variable
define('PMA_VERSION', $CFG->get('PMA_VERSION'));
unset($CFG);
require_once 'libraries/sql-parser/autoload.php';

/* Ensure default langauge is active */
require_once GETTEXT_INC;
PMA\libraries\LanguageManager::getInstance()->getLanguage('en')->activate();

// Set proxy information from env, if available
$http_proxy = getenv('http_proxy');
if ($http_proxy && ($url_info = parse_url($http_proxy))) {
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
$_SESSION['tmpval']['pftext'] = 'F';
$GLOBALS['lang'] = 'en';
$GLOBALS['is_ajax_request'] = false;
$GLOBALS['cell_align_left'] = 'left';

// Check whether we have runkit extension
define('PMA_HAS_RUNKIT', function_exists('runkit_constant_redefine'));
$GLOBALS['runkit_internal_override'] = ini_get('runkit.internal_override');


/**
 * Function to emulate headers() function by storing headers in GLOBAL array
 *
 * @param string  $string             header string
 * @param boolean $replace            .
 * @param integer $http_response_code .
 *
 * @return void
 */
function test_header($string, $replace = true, $http_response_code = 200)
{
    if (! isset($GLOBALS['header'])) {
        $GLOBALS['header'] = array();
    }

    $GLOBALS['header'][] = $string;
}

/**
 * Function to emulate headers_send.
 *
 * @return boolean false
 */
function test_headers_sent()
{
    return false;
}

/**
 * Function to emulate date() function
 *
 * @param string $date_format arg
 *
 * @return string dummy date
 */
function test_date($date_format)
{
    return '0000-00-00 00:00:00';
}

if (PMA_HAS_RUNKIT && $GLOBALS['runkit_internal_override']) {
    echo "Enabling headers testing using runkit...\n";
    runkit_function_rename('header', 'test_header_override');
    runkit_function_rename('headers_sent', 'test_headers_sent_override');
    runkit_function_rename('test_header', 'header');
    runkit_function_rename('test_headers_sent', 'headers_sent');
    define('PMA_TEST_HEADERS', true);
} else {
    echo "No headers testing.\n";
    echo "Please install runkit and enable runkit.internal_override!\n";
}

/**
 * Overrides date function
 *
 * @return boolean whether function was overridden or not
 */
function setupForTestsUsingDate()
{
    if (PMA_HAS_RUNKIT && $GLOBALS['runkit_internal_override']) {
        runkit_function_rename('date', 'test_date_override');
        runkit_function_rename('test_date', 'date');
        return true;
    } else {
        return false;
    }
}

/**
 * Restores date function
 *
 * @return void
 */
function tearDownForTestsUsingDate()
{
    if (PMA_HAS_RUNKIT && $GLOBALS['runkit_internal_override']) {
        runkit_function_rename('date', 'test_date');
        runkit_function_rename('test_date_override', 'date');
    }
}
