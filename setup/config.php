<?php
/**
 * Front controller for config view / download and clear
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

require './lib/common.inc.php';
require_once './setup/lib/Form.class.php';
require_once './setup/lib/FormDisplay.class.php';

/**
 * Returns config file contents depending on GET type value:
 * o session - uses ConfigFile::getConfigFile()
 * o post - uses POST textconfig value
 *
 * @return string
 */
function get_config() {
    $type = PMA_ifSetOr($_GET['type'], 'session');

    if ($type == 'session') {
        $config = ConfigFile::getInstance()->getConfigFile();
    } else {
        $config = PMA_ifSetOr($_POST['textconfig'], '');
        // make sure our eol is \n
        $config = str_replace("\r\n", "\n", $config);
        if ($_SESSION['eol'] == 'win') {
            $config = str_replace("\n", "\r\n", $config);
        }
    }

    return $config;
}


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
    echo get_config();
    exit;
} elseif (PMA_ifSetOr($_POST['submit_save'], '')) {
	//
	// Save generated config file on the server
	//
    file_put_contents($config_file_path, get_config());
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