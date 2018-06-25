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
use PhpMyAdmin\MoTranslator\Loader;

define('PHPMYADMIN', true);
define('TESTSUITE', true);

require_once 'libraries/config.default.php';
$GLOBALS['cfg'] = $cfg;
$GLOBALS['server'] = 0;
$GLOBALS['PMA_Config'] = new Config();

Loader::loadFunctions();

DatabaseInterface::load();
