<?php

declare(strict_types=1);

use PhpMyAdmin\Application;

// phpcs:disable PSR1.Files.SideEffects
if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// phpcs:enable

if (PHP_VERSION_ID < 80102) {
    die('<p>PHP 8.1.2+ is required.</p><p>Currently installed version is: ' . PHP_VERSION . '</p>');
}

require ROOT_PATH . 'app/autoload.php';

if (! class_exists(Application::class)) {
    die(
        '<p>Unable to load phpMyAdmin.</p><p>Most likely you did not run Composer to '
        . '<a href="https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git">'
        . 'install library files</a>.</p>'
    );
}

require_once ROOT_PATH . 'app/constants.php';

Application::init()->run(isset($GLOBALS['isSetupPage']) && $GLOBALS['isSetupPage'] === true);
