<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Front controller for config view / download and clear
 *
 * @package PhpMyAdmin-Setup
 */

/**
 * Core libraries.
 */
require './lib/common.inc.php';
require_once './libraries/config/Form.class.php';
require_once './libraries/config/FormDisplay.class.php';
require_once './setup/lib/ConfigGenerator.class.php';

require './libraries/config/setup.forms.php';

$form_display = new FormDisplay();
$form_display->registerForm('_config.php', $forms['_config.php']);
$form_display->save('_config.php');

if (isset($_POST['eol'])) {
    $_SESSION['eol'] = ($_POST['eol'] == 'unix') ? 'unix' : 'win';
}

if (PMA_ifSetOr($_POST['submit_clear'], '')) {
    //
    // Clear current config and return to main page
    //
    ConfigFile::getInstance()->resetConfigData();
    // drop post data
    header('HTTP/1.1 303 See Other');
    header('Location: index.php');
    exit;
} elseif (PMA_ifSetOr($_POST['submit_download'], '')) {
    //
    // Output generated config file
    //
    PMA_downloadHeader('config.inc.php', 'text/plain');
    echo ConfigGenerator::getConfigFile();
    exit;
} else {
    //
    // Show generated config file in a <textarea>
    //
    header('HTTP/1.1 303 See Other');
    header('Location: index.php?page=config');
    exit;
}
?>
