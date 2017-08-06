<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Front controller for config view / download and clear
 *
 * @package PhpMyAdmin-Setup
 */
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Core;
use PhpMyAdmin\Url;
use PhpMyAdmin\Response;

/**
 * Core libraries.
 */
require './lib/common.inc.php';

require './libraries/config/setup.forms.php';

$form_display = new FormDisplay($GLOBALS['ConfigFile']);
$form_display->registerForm('_config.php', $forms['_config.php']);
$form_display->save('_config.php');

$response = Response::getInstance();

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
    $response->generateHeader303('index.php' . Url::getCommonRaw(array('page' => 'config')));
    exit;
}
