<?php

declare(strict_types=1);

use PhpMyAdmin\Routing;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

global $route, $containerBuilder;

require_once ROOT_PATH . 'libraries/common.inc.php';

$dispatcher = Routing::getDispatcher();
Routing::callControllerForRoute($route, $dispatcher, $containerBuilder);
