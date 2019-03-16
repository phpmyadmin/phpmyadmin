<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\HomeController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Class HomeController
 * @package PhpMyAdmin\Controllers
 */
class HomeController extends AbstractController
{
    /**
     * @return string HTML
     */
    public function index(): string
    {
        global $cfg, $server, $collation_connection;

        $languageManager = LanguageManager::getInstance();

        $syncFavoriteTables = RecentFavoriteTable::getInstance('favorite')->getHtmlSyncFavoriteTables();

        $hasServer = $server > 0 || count($cfg['Servers']) > 1;
        if ($hasServer) {
            $hasServerSelection = $cfg['ServerDefault'] == 0
                || (! $cfg['NavigationDisplayServers']
                && (count($cfg['Servers']) > 1
                || ($server == 0 && count($cfg['Servers']) == 1)));
            if ($hasServerSelection) {
                $serverSelection = Select::render(true, true);
            }

            if ($server > 0) {
                $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
                $checkUserPrivileges->getPrivileges();

                if (($cfg['Server']['auth_type'] != 'config') && $cfg['ShowChgPassword']) {
                    $changePassword = $this->template->render('list/item', [
                        'content' => Util::getImage('s_passwd') . ' ' . __(
                            'Change password'
                        ),
                        'id' => 'li_change_password',
                        'class' => 'no_bullets',
                        'url' => [
                            'href' => 'user_password.php' . Url::getCommon(),
                            'target' => null,
                            'id' => 'change_password_anchor',
                            'class' => 'ajax',
                        ],
                        'mysql_help_page' => null,
                    ]);
                }

                $serverCollation = Charsets::getCollationDropdownBox(
                    $this->dbi,
                    $cfg['Server']['DisableIS'],
                    'collation_connection',
                    'select_collation_connection',
                    $collation_connection,
                    true,
                    true
                );

                $userPreferences = $this->template->render('list/item', [
                    'content' => Util::getImage('b_tblops') . ' ' . __(
                        'More settings'
                    ),
                    'id' => 'li_user_preferences',
                    'class' => 'no_bullets',
                    'url' => [
                        'href' => 'prefs_manage.php' . Url::getCommon(),
                        'target' => null,
                        'id' => null,
                        'class' => null,
                    ],
                    'mysql_help_page' => null,
                ]);
            }
        }

        $languageSelector = '';
        if (empty($cfg['Lang']) && $languageManager->hasChoice()) {
            $languageSelector = $languageManager->getSelectorDisplay();
        }

        $themeSelection = '';
        if ($cfg['ThemeManager']) {
            $themeSelection = ThemeManager::getInstance()->getHtmlSelectBox();
        }

        $databaseServer = [];
        if ($server > 0 && $cfg['ShowServerInfo']) {
            $hostInfo = '';
            if (! empty($cfg['Server']['verbose'])) {
                $hostInfo .= $cfg['Server']['verbose'];
                if ($cfg['ShowServerInfo']) {
                    $hostInfo .= ' (';
                }
            }
            if ($cfg['ShowServerInfo'] || empty($cfg['Server']['verbose'])) {
                $hostInfo .= $this->dbi->getHostInfo();
            }
            if (! empty($cfg['Server']['verbose']) && $cfg['ShowServerInfo']) {
                $hostInfo .= ')';
            }

            $unicode = Charsets::$mysql_charset_map['utf-8'];
            $charsets = Charsets::getMySQLCharsetsDescriptions(
                $this->dbi,
                $cfg['Server']['DisableIS']
            );

            $databaseServer = [
                'host' => $hostInfo,
                'type' => Util::getServerType(),
                'connection' => Util::getServerSSL(),
                'version' => $this->dbi->getVersionString() . ' - ' . $this->dbi->getVersionComment(),
                'protocol' => $this->dbi->getProtoInfo(),
                'user' => $this->dbi->fetchValue('SELECT USER();'),
                'charset' => $charsets[$unicode] . ' (' . $unicode . ')',
            ];
        }

        $webServer = [];
        if ($cfg['ShowServerInfo']) {
            $webServer['software'] = $_SERVER['SERVER_SOFTWARE'];

            if ($server > 0) {
                $client_version_str = $this->dbi->getClientInfo();
                if (preg_match('#\d+\.\d+\.\d+#', $client_version_str)) {
                    $client_version_str = 'libmysql - ' . $client_version_str;
                }

                $webServer['database'] = $client_version_str;
                $webServer['php_extensions'] = Util::listPHPExtensions();
                $webServer['php_version'] = phpversion();
            }
        }
        if ($cfg['ShowPhpInfo']) {
            $phpInfo = $this->template->render('list/item', [
                'content' => __('Show PHP information'),
                'id' => 'li_phpinfo',
                'class' => null,
                'url' => [
                    'href' => 'phpinfo.php' . Url::getCommon(),
                    'target' => '_blank',
                    'id' => null,
                    'class' => null,
                ],
                'mysql_help_page' => null,
            ]);
        }

        return $this->template->render('home/index', [
            'server' => $server,
            'sync_favorite_tables' => $syncFavoriteTables,
            'has_server' => $hasServer,
            'is_demo' => $cfg['DBG']['demo'],
            'has_server_selection' => $hasServerSelection ?? false,
            'server_selection' => $serverSelection ?? '',
            'change_password' => $changePassword ?? '',
            'server_collation' => $serverCollation ?? '',
            'language_selector' => $languageSelector,
            'theme_selection' => $themeSelection,
            'user_preferences' => $userPreferences ?? '',
            'database_server' => $databaseServer,
            'web_server' => $webServer,
            'php_info' => $phpInfo ?? '',
            'is_version_checked' => $cfg['VersionCheck'],
            'phpmyadmin_version' => PMA_VERSION,
        ]);
    }
}
