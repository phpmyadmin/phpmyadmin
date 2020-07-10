<?php
/**
 * Displays form for password change
 */

declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Displays form for password change
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

        $isNew = ($serverType === 'MySQL' && $serverVersion >= 50507)
            || ($serverType === 'MariaDB' && $serverVersion >= 50200);

        if ($isNew) {
            // Provide this option only for 5.7.6+
            // OR for privileged users in 5.5.7+
            if (($serverType === 'MySQL'
                && $serverVersion >= 50706)
                || ($GLOBALS['dbi']->isSuperuser() && $mode === 'edit_other')
            ) {
                $active_auth_plugins = $serverPrivileges->getActiveAuthPlugins();
                if (isset($active_auth_plugins['mysql_old_password'])) {
                    unset($active_auth_plugins['mysql_old_password']);
                }

                $html .= $template->render('display/change_password/file_b', [
                    'active_auth_plugins' => $active_auth_plugins,
                    'orig_auth_plugin' => $orig_auth_plugin,
                ]);
            } else {
                $html .= $template->render('display/change_password/file_c');
            }
        } else {
            $active_auth_plugins = ['mysql_native_password' => __('Native MySQL authentication')];

            $html .= $template->render('display/change_password/file_d', [
                'orig_auth_plugin' => $orig_auth_plugin,
                'active_auth_plugins' => $active_auth_plugins,
            ]);
        }

        $html .= $template->render('display/change_password/file_e');

        return $html;
    }
}
