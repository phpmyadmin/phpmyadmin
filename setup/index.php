<?php

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\Controllers\Setup\MainController;
use PhpMyAdmin\Controllers\Setup\ShowConfigController;
use PhpMyAdmin\Controllers\Setup\ValidateController;
use PhpMyAdmin\Core;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require ROOT_PATH . 'setup/lib/common.inc.php';

$request = Common::getRequest();
$route = $request->getRoute();
if ($route === '/setup' || $route === '/') {
    (new MainController())($request);
    exit;
}

if ($route === '/setup/show-config') {
    (new ShowConfigController())($request);
    exit;
}

if ($route === '/setup/validate') {
    (new ValidateController())($request);
    exit;
}

Core::fatalError(sprintf(
    __('Error 404! The page %s was not found.'),
    '[code]' . htmlspecialchars($route) . '[/code]'
));
