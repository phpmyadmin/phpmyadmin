<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

// Adding phpMyAdmin sources to include path
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(realpath("../index.php")));

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

?>
