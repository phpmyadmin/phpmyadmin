<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects

define('PHPMYADMIN', true);
define('TESTSUITE', true);

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

if (! defined('TEST_PATH')) {
    // This is used at Debian because tests
    // can be in a different place than the source code
    define('TEST_PATH', ROOT_PATH);
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

require_once ROOT_PATH . 'libraries/constants.php';
require_once AUTOLOAD_FILE;// Some phpunit configurations will need it
