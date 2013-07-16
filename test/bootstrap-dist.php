<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Bootstrap for phpMyAdmin tests
 *
 * @package PhpMyAdmin-test
 */

// Let PHP complain about all errors
error_reporting(E_ALL);

// Adding phpMyAdmin sources to include path
set_include_path(
    get_include_path() . PATH_SEPARATOR . dirname(realpath("../index.php"))
);

// Setting constants for testing
define('PHPMYADMIN', 1);
define('TESTSUITE', 1);
define('PMA_MYSQL_INT_VERSION', 55000);

require_once 'libraries/core.lib.php';
require_once 'libraries/Config.class.php';
$CFG = new PMA_Config();
define('PMA_VERSION', $CFG->get('PMA_VERSION'));
unset($CFG);

session_start();

// You can put some additional code that should run before tests here

// Standard environment for tests
$_SESSION[' PMA_token '] = 'token';
$GLOBALS['lang'] = 'en';
$GLOBALS['is_ajax_request'] = false;


define('PMA_HAS_RUNKIT', function_exists('runkit_constant_redefine'));
$GLOBALS['runkit_internal_override'] = ini_get('runkit.internal_override');


/**
 * Function to emulate headers() function by storing headers in GLOBAL array.
 */
function test_header($string, $replace = true, $http_response_code = 200)
{
    if (! isset($GLOBALS['header'])) {
        $GLOBALS['header'] = array();
    }

    $GLOBALS['header'][] = $string;
}

/**
 * Function to emulate headers_hest.
 */
function test_headers_sent()
{
    return false;
}

if (PMA_HAS_RUNKIT && $GLOBALS['runkit_internal_override']) {
    echo "Enabling headers testing...\n";
    runkit_function_rename('header', 'test_header_override');
    runkit_function_rename('headers_sent', 'test_headers_sent_override');
    runkit_function_rename('test_header', 'header');
    runkit_function_rename('test_headers_sent', 'headers_sent');
    define('PMA_TEST_HEADERS', true);
} else {
    echo "No headers testing.\n";
    echo "Please install runkit and enable runkit.internal_override!\n";
}

?>
