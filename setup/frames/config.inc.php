<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Config file view and save screen
 *
 * @package PhpMyAdmin-setup
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
check_config_rw($config_readable, $config_writable, $config_exists);
?>
<h2><?php echo __('Configuration file') ?></h2>
<?php display_form_top('config.php'); ?>
<input type="hidden" name="eol" value="<?php echo htmlspecialchars(PMA_ifSetOr($_GET['eol'], 'unix')) ?>" />
<?php display_fieldset_top('', '', null, array('class' => 'simple')); ?>
<tr>
    <td>
        <textarea cols="50" rows="20" name="textconfig" id="textconfig" spellcheck="false"><?php
            echo htmlspecialchars(ConfigGenerator::getConfigFile())
        ?></textarea>
    </td>
</tr>
<tr>
    <td class="lastrow" style="text-align: left">
        <input type="submit" name="submit_download" value="<?php echo __('Download') ?>" class="green" />
        <input type="submit" name="submit_save" value="<?php echo __('Save') ?>"<?php if (!$config_writable) echo ' disabled="disabled"' ?> />
    </td>
</tr>
<?php
display_fieldset_bottom_simple();
display_form_bottom();
?>
