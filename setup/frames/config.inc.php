<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Config file view and save screen
 *
 * @package PhpMyAdmin-Setup
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './libraries/config/FormDisplay.class.php';
require_once './setup/lib/index.lib.php';
require_once './setup/lib/ConfigGenerator.class.php';

$config_readable = false;
$config_writable = false;
$config_exists = false;
PMA_checkConfigRw($config_readable, $config_writable, $config_exists);
echo '<h2>' . __('Configuration file') . '</h2>';

echo PMA_displayFormTop('config.php');
echo '<input type="hidden" name="eol" value="'
    . htmlspecialchars(PMA_ifSetOr($_GET['eol'], 'unix')) . '" />';
echo PMA_displayFieldsetTop('config.inc.php', '', null, array('class' => 'simple'));
echo '<tr>';
echo '<td>';
echo '<textarea cols="50" rows="20" name="textconfig" '
    . 'id="textconfig" spellcheck="false">';
echo htmlspecialchars(ConfigGenerator::getConfigFile($GLOBALS['ConfigFile']));
echo '</textarea>';
echo '</td>';
echo '</tr>';
echo '<tr>';
echo '<td class="lastrow" style="text-align: left">';
echo '<input type="submit" name="submit_download" value="'
    . __('Download') . '" class="green" />';
echo '<input type="submit" name="submit_save" value="' . __('Save') . '"';
if (!$config_writable) {
    echo ' disabled="disabled"';
}
echo '/>';
echo '</td>';
echo '</tr>';

echo PMA_displayFieldsetBottomSimple();
echo PMA_displayFormBottom();
