<?php

// Adding phpMyAdmin sources to include path
set_include_path(get_include_path().PATH_SEPARATOR.dirname(realpath("../index.php")));

// Setting constants for testing
define('PHPMYADMIN', 1);
define('TESTSUITE', 1);

// You can put some additional code that runs before tests here

?>
