<?php

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Controllers\Setup\MainController;
use PhpMyAdmin\Controllers\Setup\ShowConfigController;
use PhpMyAdmin\Controllers\Setup\ValidateController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

define('PHPMYADMIN', true);
// phpcs:enable

if (PHP_VERSION_ID < 70205) {
    die('<p>PHP 7.2.5+ is required.</p><p>Currently installed version is: ' . PHP_VERSION . '</p>');
}

require_once ROOT_PATH . 'libraries/constants.php';

if (! @is_readable(AUTOLOAD_FILE)) {
    die(
        '<p>File <samp>' . AUTOLOAD_FILE . '</samp> missing or not readable.</p>'
        . '<p>Most likely you did not run Composer to '
        . '<a href="https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git">'
        . 'install library files</a>.</p>'
    );
}

require AUTOLOAD_FILE;

Common::run(true);

// use default error handler
restore_error_handler();

// Save current language in a cookie, since it was not set in Common::run().
$GLOBALS['config']->setCookie('pma_lang', (string) $GLOBALS['lang']);
$GLOBALS['config']->set('is_setup', true);

$GLOBALS['ConfigFile'] = new ConfigFile();
$GLOBALS['ConfigFile']->setPersistKeys(
    [
        'DefaultLang',
        'ServerDefault',
        'UploadDir',
        'SaveDir',
        'Servers/1/verbose',
        'Servers/1/host',
        'Servers/1/port',
        'Servers/1/socket',
        'Servers/1/auth_type',
        'Servers/1/user',
        'Servers/1/password',
    ]
);

$GLOBALS['dbi'] = DatabaseInterface::load();

// allows for redirection even after sending some data
ob_start();

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
