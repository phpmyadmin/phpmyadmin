<?php
define("PHPMYADMIN", true);

require_once 'libraries/String.class.php';
require_once 'libraries/core.lib.php';

$GLOBALS['PMA_String'] = new PMA_String();
require_once 'libraries/Config.class.php';
$CFG = new PMA_Config();
// Initialize PMA_VERSION variable
define('PMA_VERSION', $CFG->get('PMA_VERSION'));
unset($CFG);

// Ensure we have session started
session_start();

// Standard environment for tests
$_SESSION[' PMA_token '] = 'token';
$_SESSION['tmpval']['pftext'] = 'F';
$GLOBALS['lang'] = 'en';
$GLOBALS['is_ajax_request'] = false;

// Check whether we have runkit extension
define('PMA_HAS_RUNKIT', function_exists('runkit_constant_redefine'));
$GLOBALS['runkit_internal_override'] = ini_get('runkit.internal_override');

require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/sqlparser.data.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Message.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/sanitizing.lib.php';

echo "With //";
$query = 'CREATE TABLE x //';
var_dump(PMA_SQP_parse($query));

echo "Without //";
$query = 'CREATE TABLE x;';
var_dump(PMA_SQP_parse($query));
?>