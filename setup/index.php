<?php

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\Setup\MainController;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require ROOT_PATH . 'setup/lib/common.inc.php';

(new MainController())(Common::getRequest());
