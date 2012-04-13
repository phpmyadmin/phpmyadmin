<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

// Adding phpMyAdmin sources to include path
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(realpath("../index.php")));

// Setting constants for testing
define('PHPMYADMIN', 1);
define('TESTSUITE', 1);

session_start();

// You can put some additional code that should run before tests here

?>
