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
  * Get HTML for the Change password dialog
  *
  * @param string $username username
  * @param string $hostname hostname
  *
  * @return string html snippet
  */
function PMA_getHtmlForChangePassword($username, $hostname)
{
    /**
     * autocomplete feature of IE kills the "onchange" event handler and it
     * must be replaced by the "onpropertychange" one in this case
     */
    $chg_evt_handler = (PMA_USR_BROWSER_AGENT == 'IE'
        && PMA_USR_BROWSER_VER >= 5
        && PMA_USR_BROWSER_VER < 7)
                 ? 'onpropertychange'
                 : 'onchange';

    $is_privileges = basename($_SERVER['SCRIPT_NAME']) === 'server_privileges.php';

    $html = '<form method="post" id="change_password_form" '
        . 'action="' . basename($GLOBALS['PMA_PHP_SELF']) . '" '
        . 'name="chgPassword" '
        . 'class="' . ($is_privileges ? 'submenu-item' : '') . '">';

    $html .= PMA_URL_getHiddenInputs();

    if (strpos($GLOBALS['PMA_PHP_SELF'], 'server_privileges') !== false) {
        $html .= '<input type="hidden" name="username" '
            . 'value="' . htmlspecialchars($username) . '" />'
            . '<input type="hidden" name="hostname" '
            . 'value="' . htmlspecialchars($hostname) . '" />';
    }
    $html .= '<fieldset id="fieldset_change_password">'
        . '<legend'
        . ($is_privileges
            ? ' data-submenu-label="' . __('Change password') . '"'
            : ''
        )
        . '>' . __('Change password') . '</legend>'
        . '<table class="data noclick">'
        . '<tr class="odd">'
        . '<td colspan="2">'
        . '<input type="radio" name="nopass" value="1" id="nopass_1" '
        . 'onclick="pma_pw.value = \'\'; pma_pw2.value = \'\'; '
        . 'this.checked = true" />'
        . '<label for="nopass_1">' . __('No Password') . '</label>'
        . '</td>'
        . '</tr>'
        . '<tr class="even vmiddle">'
        . '<td>'
        . '<input type="radio" name="nopass" value="0" id="nopass_0" '
        . 'onclick="document.getElementById(\'text_pma_pw\').focus();" '
        . 'checked="checked" />'
        . '<label for="nopass_0">' . __('Password:') . '&nbsp;</label>'
        . '</td>'
        . '<td>'
        . '<input type="password" name="pma_pw" id="text_pma_pw" size="10" '
        . 'class="textfield"'
        . $chg_evt_handler . '="nopass[1].checked = true" />'
        . '&nbsp;&nbsp;' . __('Re-type:') . '&nbsp;'
        . '<input type="password" name="pma_pw2" id="text_pma_pw2" size="10" '
        . 'class="textfield"'
        . $chg_evt_handler . '="nopass[1].checked = true" />'
        . '</td>'
        . '</tr>';

    $html .= '<tr class="vmiddle">'
        . '<td>' . __('Password Hashing:') . '</td>';

    $serverType = PMA_Util::getServerType();
    if (($serverType == 'MySQL'
        && PMA_MYSQL_INT_VERSION >= 50507)
        || ($serverType == 'MariaDB'
        && PMA_MYSQL_INT_VERSION >= 50200)
    ) {

        $active_auth_plugins = PMA_getActiveAuthPlugins();

        $default_auth_plugin = PMA_getCurrentAuthenticationPlugin(
            'change', $username, $hostname
        );

        $iter = 0;
        $total_plugins = count($active_auth_plugins);
        foreach ($active_auth_plugins as $plugin) {
            if ($plugin['PLUGIN_NAME'] == 'mysql_old_password') {
                continue;
            }

            if ($iter != 0) {
                $html .= '<td>&nbsp;</td>';
            }
            $html .= '<td>'
                . '<input type="radio" name="pw_hash" value="'
                . $plugin['PLUGIN_NAME'] . '"'
                . ($default_auth_plugin == $plugin['PLUGIN_NAME'] ? 'checked="checked" ' : '')
                . ' id="radio_pw_hash_' . $plugin['PLUGIN_NAME'] . '" />'
                . '<label for="radio_pw_hash_' . $plugin['PLUGIN_NAME'] . '" >'
                . __($plugin['PLUGIN_DESCRIPTION']) . ' </label></td></tr>';

            if ($iter == $total_plugins - 2) {
                $html .= '<tr id="tr_element_before_generate_password">';
            } else if ($iter != $total_plugins - 1) {
                $html .= '<tr>';
            }
            $iter++;
        }

        $html .= '</tr>';
        $html .=  '</table>';

        $html .= '<div '
            . ($default_auth_plugin != 'sha256_password' ? 'style="display:none"' : '')
            . ' id="ssl_reqd_warning_cp">'
            . PMA_Message::notice(
                __(
                    'This method requires using an \'<i>SSL connection</i>\' '
                    . 'or an \'<i>unencrypted connection that encrypts the password '
                    . 'using RSA</i>\'; while connecting to the server.'
                )
                . PMA_Util::showMySQLDocu('sha256-authentication-plugin')
            )
                ->getDisplay()
            . '</div>';
    } else {
        $html .= '<td>'
            . '<input type="radio" name="pw_hash" value="mysql_native_password"'
            . 'checked="checked" id="radio_pw_hash_native" />'
            . '<label for="radio_pw_hash_native" >'
            . __('MySQL Native Authentication') . ' </label></td></tr>'
            . '<tr id="tr_element_before_generate_password"></tr>'
            . '</table>';
    }

    $html .= '</fieldset>'
        . '<fieldset id="fieldset_change_password_footer" class="tblFooters">'
        . '<input type="hidden" name="change_pw" value="1" />'
        . '<input type="submit" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>';
    return $html;
}
