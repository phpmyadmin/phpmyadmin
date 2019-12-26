<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Loads libraries/common.inc.php and preforms some additional actions
 *
 * @package PhpMyAdmin-Setup
 */
declare(strict_types=1);

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\DatabaseInterface;

/**
 * Do not include full common.
 * @ignore
 */
define('PMA_MINIMUM_COMMON', true);
chdir('..');

if (! file_exists(ROOT_PATH . 'libraries/common.inc.php')) {
    die('Bad invocation!');
}

require_once ROOT_PATH . 'libraries/common.inc.php';

// use default error handler
restore_error_handler();

// Save current language in a cookie, required since we use PMA_MINIMUM_COMMON
$GLOBALS['PMA_Config']->setCookie('pma_lang', $GLOBALS['lang']);
$GLOBALS['PMA_Config']->set('is_setup', true);

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
