<?php
/**
 * Bootstrap file for psalm
 *
 * The checks for defined are because psalm --alter can use this file multiple times
 */

declare(strict_types=1);

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// phpcs:disable PSR1.Files.SideEffects
if (! defined('TESTSUITE')) {
    define('TESTSUITE', true);
}

// phpcs:enable

include_once ROOT_PATH . 'examples/signon-script.php';
require_once ROOT_PATH . 'app/constants.php';
require_once AUTOLOAD_FILE;
