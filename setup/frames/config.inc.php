<?php
/**
 * Config file view and save screen
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './setup/lib/FormDisplay.class.php';
require_once './setup/lib/index.lib.php';

$config_readable = false;
$config_writable = false;
$config_exists = false;
check_config_rw($config_readable, $config_writable, $config_exists);
?>
<h2><?php echo $GLOBALS['strSetupConfigurationFile'] ?></h2>
<?php display_form_top('config.php'); ?>
<input type="hidden" name="eol" value="<?php echo htmlspecialchars(PMA_ifSetOr($_GET['eol'], 'unix')) ?>" />
<?php display_fieldset_top('', '', null, array('class' => 'simple')); ?>
<tr>
    <td>
        <textarea cols="50" rows="20" name="textconfig" id="textconfig" spellcheck="false"><?php
            echo htmlspecialchars(ConfigFile::getInstance()->getConfigFile())
        ?></textarea>
    </td>
</tr>
<tr>
    <td class="lastrow" style="text-align: left">
        <input type="submit" name="submit_download" value="<?php echo $GLOBALS['strSetupDownload'] ?>" class="green" />
        <input type="submit" name="submit_save" value="<?php echo $GLOBALS['strSave'] ?>"<?php if (!$config_writable) echo ' disabled="disabled"' ?> />
    </td>
</tr>
<?php
display_fieldset_bottom_simple();
display_form_bottom();
?>
