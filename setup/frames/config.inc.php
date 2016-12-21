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

?>
<h2><?php echo __('Configuration file') ?></h2>
<?php PMA_displayFormTop('config.php'); ?>
<input type="hidden" name="eol" value="<?php echo htmlspecialchars(PMA_ifSetOr($_GET['eol'], 'unix')) ?>" />
<?php PMA_displayFieldsetTop('', '', null, array('class' => 'simple')); ?>
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
    </td>
</tr>
<?php
PMA_displayFieldsetBottomSimple();
PMA_displayFormBottom();
?>
