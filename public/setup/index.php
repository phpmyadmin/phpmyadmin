<?php

use PhpMyAdmin\Common;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
}

define('PHPMYADMIN', true);

if (PHP_VERSION_ID < 80100) {
    die('PHP 8.1.0+ is required.' . PHP_EOL
        . 'Currently installed version is: ' . PHP_VERSION . PHP_EOL
        . 'Please upgrade your PHP installation to meet the requirements.');
}

require_once ROOT_PATH . 'libraries/constants.php';

if (!is_readable(AUTOLOAD_FILE)) {
    die('File "' . AUTOLOAD_FILE . '" is missing or not readable.' . PHP_EOL
        . 'Most likely you did not run Composer to install library files.' . PHP_EOL
        . 'Please refer to the documentation on how to install library files: '
        . 'https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git');
}


require AUTOLOAD_FILE;

Common::run(true);
