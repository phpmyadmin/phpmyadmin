<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for password change
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * autocomplete feature of IE kills the "onchange" event handler and it
 *        must be replaced by the "onpropertychange" one in this case
 */
$chg_evt_handler = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 5)
                 ? 'onpropertychange'
                 : 'onchange';

// Displays the form
?>
    <form method="post" id="change_password_form" action="<?php echo $GLOBALS['PMA_PHP_SELF']; ?>" name="chgPassword" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? 'class="ajax" ' : ''); ?> onsubmit="return checkPassword(this)">
    <?php   echo PMA_generate_common_hidden_inputs();
            if (strpos($GLOBALS['PMA_PHP_SELF'], 'server_privileges') !== false) {
                echo '<input type="hidden" name="username" value="' . htmlspecialchars($username) . '" />' . "\n"
                   . '<input type="hidden" name="hostname" value="' . htmlspecialchars($hostname) . '" />' . "\n";
            }?>
    <fieldset id="fieldset_change_password">
        <legend><?php echo __('Change password'); ?></legend>
        <table class="data noclick">
        <tr class="odd">
            <td colspan="2">
                <input type="radio" name="nopass" value="1" id="nopass_1" onclick="pma_pw.value = ''; pma_pw2.value = ''; this.checked = true" />
        <label for="nopass_1"><?php echo __('No Password') . "\n"; ?></label>
            </td>
        </tr>
        <tr class="even">
            <td>
                <input type="radio" name="nopass" value="0" id="nopass_0" onclick="document.getElementById('text_pma_pw').focus();" checked="checked " />
        <label for="nopass_0"><?php echo __('Password'); ?>:&nbsp;</label>
            </td>
            <td>
                <input type="password" name="pma_pw" id="text_pma_pw" size="10" class="textfield" <?php echo $chg_evt_handler; ?>="nopass[1].checked = true" />
        &nbsp;&nbsp;
        <?php echo __('Re-type'); ?>:&nbsp;
                <input type="password" name="pma_pw2" id="text_pma_pw2" size="10" class="textfield" <?php echo $chg_evt_handler; ?>="nopass[1].checked = true" />
            </td>
        </tr>
        <tr>
            <td>
            <?php echo __('Password Hashing'); ?>:
        </td>
        <td>
            <input type="radio" name="pw_hash" id="radio_pw_hash_new" value="new" checked="checked" />
            <label for="radio_pw_hash_new">
                MySQL&nbsp;4.1+
            </label>
        </td>
        </tr>
        <tr id="tr_element_before_generate_password">
            <td>&nbsp;</td>
        <td>
            <input type="radio" name="pw_hash" id="radio_pw_hash_old" value="old" />
            <label for="radio_pw_hash_old">
                <?php echo __('MySQL 4.0 compatible'); ?>
            </label>
        </td>
        </tr>
        </table>
    </fieldset>
    <fieldset id="fieldset_change_password_footer" class="tblFooters">
            <input type="submit" name="change_pw" value="<?php echo(__('Go')); ?>" />
    </fieldset>
</form>
