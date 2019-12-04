<?php
/**
 * Displays form for password change
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Displays form for password change
 *
 * @package PhpMyAdmin
 */
class ChangePassword
{
    /**
     * Get HTML for the Change password dialog
     *
     * @param string $mode     where is the function being called?
     *                         values : 'change_pw' or 'edit_other'
     * @param string $username username
     * @param string $hostname hostname
     *
     * @return string html snippet
     */
    public static function getHtml($mode, $username, $hostname)
    {
        $relation = new Relation($GLOBALS['dbi']);
        $serverPrivileges = new Privileges(
            new Template(),
            $GLOBALS['dbi'],
            $relation,
            new RelationCleanup($GLOBALS['dbi'], $relation)
        );

        /**
         * autocomplete feature of IE kills the "onchange" event handler and it
         * must be replaced by the "onpropertychange" one in this case
         */
        $chg_evt_handler = 'onchange';

        $is_privileges = isset($_REQUEST['route']) && $_REQUEST['route'] === '/server/privileges';

        $action = Url::getFromRoute('/user_password');
        if ($is_privileges) {
            $action = Url::getFromRoute('/server/privileges');
        }

        $template = new Template();
        $html = $template->render('display/change_password/file_a', [
            'is_privileges' => $is_privileges,
            'username' => $username,
            'hostname' => $hostname,
            'chg_evt_handler' => $chg_evt_handler,
        ]);

        $serverType = Util::getServerType();
        $serverVersion = $GLOBALS['dbi']->getVersion();
        $orig_auth_plugin = $serverPrivileges->getCurrentAuthenticationPlugin(
            'change',
            $username,
            $hostname
        );

        if (($serverType == 'MySQL'
            && $serverVersion >= 50507)
            || ($serverType == 'MariaDB'
            && $serverVersion >= 50200)
        ) {
            // Provide this option only for 5.7.6+
            // OR for privileged users in 5.5.7+
            if (($serverType == 'MySQL'
                && $serverVersion >= 50706)
                || ($GLOBALS['dbi']->isSuperuser() && $mode == 'edit_other')
            ) {
                $auth_plugin_dropdown = $serverPrivileges->getHtmlForAuthPluginsDropdown(
                    $orig_auth_plugin,
                    'change_pw',
                    'new'
                );

                $html .= $template->render('display/change_password/file_b', [
                    'auth_plugin_dropdown' => $auth_plugin_dropdown,
                    'orig_auth_plugin' => $orig_auth_plugin,
                ]);
            } else {
                $html .= $template->render('display/change_password/file_c');
            }
        } else {
            $auth_plugin_dropdown = $serverPrivileges->getHtmlForAuthPluginsDropdown(
                $orig_auth_plugin,
                'change_pw',
                'old'
            );

            $html .= $template->render('display/change_password/file_d', [
                'auth_plugin_dropdown' => $auth_plugin_dropdown,
            ]);
        }

        $html .= $template->render('display/change_password/file_e');
        return $html;
    }
}
