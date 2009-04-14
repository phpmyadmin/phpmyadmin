<?php
/**
 * Front controller for config view / download and clear
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

/**
 * Core libraries.
 */
require './lib/common.inc.php';
require_once './setup/lib/Form.class.php';
require_once './setup/lib/FormDisplay.class.php';

$form_display = new FormDisplay();
$form_display->registerForm('_config.php');
$form_display->save('_config.php');
$config_file_path = ConfigFile::getInstance()->getFilePath();

if (isset($_POST['eol'])) {
    $_SESSION['eol'] = ($_POST['eol'] == 'unix') ? 'unix' : 'win';
}

if (PMA_ifSetOr($_POST['submit_clear'], '')) {
	//
	// Clear current config and return to main page
	//
	$_SESSION['ConfigFile'] = array();
    // drop post data
    header('HTTP/1.1 303 See Other');
    header('Location: index.php');
    exit;
} elseif (PMA_ifSetOr($_POST['submit_download'], '')) {
	//
	// Output generated config file
	//
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="config.inc.php"');
    echo ConfigFile::getInstance()->getConfigFile();
    exit;
} elseif (PMA_ifSetOr($_POST['submit_save'], '')) {
	//
	// Save generated config file on the server
	//
    file_put_contents($config_file_path, ConfigFile::getInstance()->getConfigFile());
    header('HTTP/1.1 303 See Other');
    header('Location: index.php');
    exit;
} elseif (PMA_ifSetOr($_POST['submit_load'], '')) {
	//
	// Load config file from the server
	//
    $cfg = array();
    require_once $config_file_path;
    $_SESSION['ConfigFile'] = $cfg;
    header('HTTP/1.1 303 See Other');
    header('Location: index.php');
    exit;
} elseif (PMA_ifSetOr($_POST['submit_delete'], '')) {
	//
	// Delete config file on the server
	//
    @unlink($config_file_path);
    header('HTTP/1.1 303 See Other');
    header('Location: index.php');
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
