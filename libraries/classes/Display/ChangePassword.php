<?php

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
        global $dbi, $route;

        $relation = new Relation($dbi);
        $serverPrivileges = new Privileges(
            new Template(),
            $dbi,
            $relation,
            new RelationCleanup($dbi, $relation)
        );

        $isPrivileges = $route === '/server/privileges';

        $serverType = Util::getServerType();
        $serverVersion = $dbi->getVersion();
        $origAuthPlugin = $serverPrivileges->getCurrentAuthenticationPlugin(
            'change',
            $username,
            $hostname
        );

        $isNew = ($serverType === 'MySQL' && $serverVersion >= 50507)
            || ($serverType === 'MariaDB' && $serverVersion >= 50200);
        $hasMoreAuthPlugins = ($serverType === 'MySQL' && $serverVersion >= 50706)
            || ($dbi->isSuperuser() && $mode === 'edit_other');

        $activeAuthPlugins = ['mysql_native_password' => __('Native MySQL authentication')];

        if ($isNew && $hasMoreAuthPlugins) {
            $activeAuthPlugins = $serverPrivileges->getActiveAuthPlugins();
            if (isset($activeAuthPlugins['mysql_old_password'])) {
                unset($activeAuthPlugins['mysql_old_password']);
            }
        }

        $template = new Template();

        return $template->render('display/change_password', [
            'username' => $username,
            'hostname' => $hostname,
            'is_privileges' => $isPrivileges,
            'is_new' => $isNew,
            'has_more_auth_plugins' => $hasMoreAuthPlugins,
            'active_auth_plugins' => $activeAuthPlugins,
            'orig_auth_plugin' => $origAuthPlugin,
        ]);
    }
}
