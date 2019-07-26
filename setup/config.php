<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Front controller for config view / download and clear
 *
 * @package PhpMyAdmin-Setup
 */
declare(strict_types=1);

use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Url;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

/**
 * Core libraries.
 */
require ROOT_PATH . 'setup/lib/common.inc.php';

$form_display = new ConfigForm($GLOBALS['ConfigFile']);
$form_display->save('Config');

$response = Response::getInstance();
$response->disable();

if (isset($_POST['eol'])) {
    $_SESSION['eol'] = ($_POST['eol'] == 'unix') ? 'unix' : 'win';
}

if (Core::ifSetOr($_POST['submit_clear'], '')) {
    //
    // Clear current config and return to main page
    //
    $GLOBALS['ConfigFile']->resetConfigData();
    // drop post data
    $response->generateHeader303('index.php' . Url::getCommonRaw());
    exit;
} elseif (Core::ifSetOr($_POST['submit_download'], '')) {
    //
    // Output generated config file
    //
    Core::downloadHeader('config.inc.php', 'text/plain');
    $response->disable();
    echo ConfigGenerator::getConfigFile($GLOBALS['ConfigFile']);
    exit;
} else {
    //
    // Show generated config file in a <textarea>
    //
    $response->generateHeader303('index.php' . Url::getCommonRaw(['page' => 'config']));
    exit;
}
