<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Bootstrap file for phpstan
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\MoTranslator\Loader;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

define('PHPMYADMIN', true);
define('TESTSUITE', true);

require_once ROOT_PATH . 'libraries/config.default.php';
require_once ROOT_PATH . 'libraries/vendor_config.php';
require_once AUTOLOAD_FILE;
$GLOBALS['cfg'] = $cfg;
$GLOBALS['server'] = 0;
$GLOBALS['PMA_Config'] = new Config();
define('PMA_VERSION', $GLOBALS['PMA_Config']->get('PMA_VERSION'));
define('PMA_MAJOR_VERSION', $GLOBALS['PMA_Config']->get('PMA_MAJOR_VERSION'));
define('PROXY_URL', '');
define('PROXY_USER', '');
define('PROXY_PASS', '');
define('PMA_PATH_TO_BASEDIR', '');

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36';
$GLOBALS['PMA_Config']->checkClient();
$GLOBALS['PMA_Config']->checkWebServerOs();
$GLOBALS['PMA_Config']->enableBc();// Defines constants, phpstan:level=1

Loader::loadFunctions();

$GLOBALS['dbi'] = DatabaseInterface::load(new DbiDummy());

// for PhpMyAdmin\Plugins\Import\ImportLdi
$GLOBALS['plugin_param'] = 'table';
