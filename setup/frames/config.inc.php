<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Config file view and save screen
 *
 * @package PhpMyAdmin-Setup
 */

use PhpMyAdmin\Config\FormDisplayTemplate;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\ConfigGenerator;

if (!defined('PHPMYADMIN')) {
    exit;
}

echo '<h2>' , __('Configuration file') , '</h2>' . 
	 FormDisplayTemplate::displayFormTop('config.php') . 
	 '<input type="hidden" name="eol" value="' . htmlspecialchars(Core::ifSetOr($_GET['eol'], 'unix')) , '" />' . 
	 FormDisplayTemplate::displayFieldsetTop('config.inc.php', '', null, array('class' => 'simple')) . 
	 '<tr><td>' .
	 '<textarea cols="50" rows="20" name="textconfig" id="textconfig" spellcheck="false">' . 
	 htmlspecialchars(ConfigGenerator::getConfigFile($GLOBALS['ConfigFile'])) . 
	 '</textarea>' . 
	 '</td></tr>' . 
	 '<tr><td class="lastrow" style="text-align: left">' . 
	 '<input type="submit" name="submit_download" value="' . __('Download') . '" class="green" />' . 
	 '</td></tr>' . 
	 FormDisplayTemplate::displayFieldsetBottom(false) . 
	 FormDisplayTemplate::displayFormBottom();
