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
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Charsets\Collation;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\GitRevision;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\Util;

/**
 * Class HomeController
 * @package PhpMyAdmin\Controllers
 */
class HomeController extends AbstractController
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ThemeManager
     */
    private $themeManager;

    /**
     * HomeController constructor.
     *
     * @param Response          $response     Response instance
     * @param DatabaseInterface $dbi          DatabaseInterface instance
     * @param Template          $template     Template object
     * @param Config            $config       Config instance
     * @param ThemeManager      $themeManager ThemeManager instance
     */
    public function __construct($response, $dbi, Template $template, $config, ThemeManager $themeManager)
    {
        parent::__construct($response, $dbi, $template);
        $this->config = $config;
        $this->themeManager = $themeManager;
    }


    /**
     * @return string HTML
     */
    public function index(): string
    {
        global $cfg, $server, $collation_connection, $message;

        $languageManager = LanguageManager::getInstance();

        if (! empty($message)) {
            $displayMessage = Util::getMessage($message);
            unset($message);
        }
        if (isset($_SESSION['partial_logout'])) {
            $partialLogout = Message::success(__(
                'You were logged out from one server, to logout completely '
                . 'from phpMyAdmin, you need to logout from all servers.'
            ))->getDisplay();
            unset($_SESSION['partial_logout']);
        }

        $syncFavoriteTables = RecentFavoriteTable::getInstance('favorite')
            ->getHtmlSyncFavoriteTables();

        $hasServer = $server > 0 || count($cfg['Servers']) > 1;
        if ($hasServer) {
            $hasServerSelection = $cfg['ServerDefault'] == 0
                || (! $cfg['NavigationDisplayServers']
                && (count($cfg['Servers']) > 1
                || ($server == 0 && count($cfg['Servers']) === 1)));
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

                $charsets = Charsets::getCharsets($this->dbi, $cfg['Server']['DisableIS']);
                $collations = Charsets::getCollations($this->dbi, $cfg['Server']['DisableIS']);
                $charsetsList = [];
                /** @var Charset $charset */
                foreach ($charsets as $charset) {
                    $collationsList = [];
                    /** @var Collation $collation */
                    foreach ($collations[$charset->getName()] as $collation) {
                        $collationsList[] = [
                            'name' => $collation->getName(),
                            'description' => $collation->getDescription(),
                            'is_selected' => $collation_connection === $collation->getName(),
                        ];
                    }
                    $charsetsList[] = [
                        'name' => $charset->getName(),
                        'description' => $charset->getDescription(),
                        'collations' => $collationsList,
                    ];
                }

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
            $languageSelector = $languageManager->getSelectorDisplay($this->template);
        }

        $themeSelection = '';
        if ($cfg['ThemeManager']) {
            $themeSelection = $this->themeManager->getHtmlSelectBox();
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

            $serverCharset = Charsets::getServerCharset($this->dbi, $cfg['Server']['DisableIS']);
            $databaseServer = [
                'host' => $hostInfo,
                'type' => Util::getServerType(),
                'connection' => Util::getServerSSL(),
                'version' => $this->dbi->getVersionString() . ' - ' . $this->dbi->getVersionComment(),
                'protocol' => $this->dbi->getProtoInfo(),
                'user' => $this->dbi->fetchValue('SELECT USER();'),
                'charset' => $serverCharset->getDescription() . ' (' . $serverCharset->getName() . ')',
            ];
        }

        $webServer = [];
        if ($cfg['ShowServerInfo']) {
            $webServer['software'] = $_SERVER['SERVER_SOFTWARE'];

            if ($server > 0) {
                $clientVersion = $this->dbi->getClientInfo();
                if (preg_match('#\d+\.\d+\.\d+#', $clientVersion)) {
                    $clientVersion = 'libmysql - ' . $clientVersion;
                }

                $webServer['database'] = $clientVersion;
                $webServer['php_extensions'] = Util::listPHPExtensions();
                $webServer['php_version'] = PHP_VERSION;
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

        $relation = new Relation($this->dbi);
        if ($server > 0) {
            $cfgRelation = $relation->getRelationsParam();
            if (! $cfgRelation['allworks']
                && $cfg['PmaNoRelation_DisableWarning'] == false
            ) {
                $messageText = __(
                    'The phpMyAdmin configuration storage is not completely '
                    . 'configured, some extended features have been deactivated. '
                    . '%sFind out why%s. '
                );
                if ($cfg['ZeroConf'] == true) {
                    $messageText .= '<br>' .
                        __(
                            'Or alternately go to \'Operations\' tab of any database '
                            . 'to set it up there.'
                        );
                }
                $messageInstance = Message::notice($messageText);
                $messageInstance->addParamHtml('<a href="./chk_rel.php" data-post="' . Url::getCommon() . '">');
                $messageInstance->addParamHtml('</a>');
                /* Show error if user has configured something, notice elsewhere */
                if (! empty($cfg['Servers'][$server]['pmadb'])) {
                    $messageInstance->isError(true);
                }
                $configStorageMessage = $messageInstance->getDisplay();
            }
        }

        $this->checkRequirements();

        return $this->template->render('home/index', [
            'message' => $displayMessage ?? '',
            'partial_logout' => $partialLogout ?? '',
            'is_git_revision' => $this->config->isGitRevision(),
            'server' => $server,
            'sync_favorite_tables' => $syncFavoriteTables,
            'has_server' => $hasServer,
            'is_demo' => $cfg['DBG']['demo'],
            'has_server_selection' => $hasServerSelection ?? false,
            'server_selection' => $serverSelection ?? '',
            'change_password' => $changePassword ?? '',
            'charsets' => $charsetsList ?? [],
            'language_selector' => $languageSelector,
            'theme_selection' => $themeSelection,
            'user_preferences' => $userPreferences ?? '',
            'database_server' => $databaseServer,
            'web_server' => $webServer,
            'php_info' => $phpInfo ?? '',
            'is_version_checked' => $cfg['VersionCheck'],
            'phpmyadmin_version' => PMA_VERSION,
            'config_storage_message' => $configStorageMessage ?? '',
        ]);
    }

    /**
     * @param array $params Request parameters
     * @return void
     */
    public function setTheme(array $params): void
    {
        $this->themeManager->setActiveTheme($params['set_theme']);
        $this->themeManager->setThemeCookie();

        $userPreferences = new UserPreferences();
        $preferences = $userPreferences->load();
        $preferences['config_data']['ThemeDefault'] = $params['set_theme'];
        $userPreferences->save($preferences['config_data']);
    }

    /**
     * @param array $params Request parameters
     * @return void
     */
    public function setCollationConnection(array $params): void
    {
        $this->config->setUserValue(
            null,
            'DefaultConnectionCollation',
            $params['collation_connection'],
            'utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array JSON
     */
    public function reloadRecentTablesList(): array
    {
        return [
            'list' => RecentFavoriteTable::getInstance('recent')->getHtmlList(),
        ];
    }

    /**
     * @return string HTML
     */
    public function gitRevision(): string
    {
        return (new GitRevision(
            $this->response,
            $this->config,
            $this->template
        ))->display();
    }

    /**
     * @return void
     */
    private function checkRequirements(): void
    {
        global $cfg, $server, $lang;

        /**
         * mbstring is used for handling multibytes inside parser, so it is good
         * to tell user something might be broken without it, see bug #1063149.
         */
        if (! extension_loaded('mbstring')) {
            trigger_error(
                __(
                    'The mbstring PHP extension was not found and you seem to be using'
                    . ' a multibyte charset. Without the mbstring extension phpMyAdmin'
                    . ' is unable to split strings correctly and it may result in'
                    . ' unexpected results.'
                ),
                E_USER_WARNING
            );
        }

        /**
         * Missing functionality
         */
        if (! extension_loaded('curl') && ! ini_get('allow_url_fopen')) {
            trigger_error(
                __(
                    'The curl extension was not found and allow_url_fopen is '
                    . 'disabled. Due to this some features such as error reporting '
                    . 'or version check are disabled.'
                )
            );
        }

        if ($cfg['LoginCookieValidityDisableWarning'] == false) {
            /**
             * Check whether session.gc_maxlifetime limits session validity.
             */
            $gc_time = (int) ini_get('session.gc_maxlifetime');
            if ($gc_time < $cfg['LoginCookieValidity']) {
                trigger_error(
                    __(
                        'Your PHP parameter [a@https://secure.php.net/manual/en/session.' .
                        'configuration.php#ini.session.gc-maxlifetime@_blank]session.' .
                        'gc_maxlifetime[/a] is lower than cookie validity configured ' .
                        'in phpMyAdmin, because of this, your login might expire sooner ' .
                        'than configured in phpMyAdmin.'
                    ),
                    E_USER_WARNING
                );
            }
        }

        /**
         * Check whether LoginCookieValidity is limited by LoginCookieStore.
         */
        if ($cfg['LoginCookieStore'] != 0
            && $cfg['LoginCookieStore'] < $cfg['LoginCookieValidity']
        ) {
            trigger_error(
                __(
                    'Login cookie store is lower than cookie validity configured in ' .
                    'phpMyAdmin, because of this, your login will expire sooner than ' .
                    'configured in phpMyAdmin.'
                ),
                E_USER_WARNING
            );
        }

        /**
         * Warning if using the default MySQL controluser account
         */
        if ($server != 0
            && isset($cfg['Server']['controluser']) && $cfg['Server']['controluser'] == 'pma'
            && isset($cfg['Server']['controlpass']) && $cfg['Server']['controlpass'] == 'pmapass'
        ) {
            trigger_error(
                __(
                    'Your server is running with default values for the ' .
                    'controluser and password (controlpass) and is open to ' .
                    'intrusion; you really should fix this security weakness' .
                    ' by changing the password for controluser \'pma\'.'
                ),
                E_USER_WARNING
            );
        }

        /**
         * Check if user does not have defined blowfish secret and it is being used.
         */
        if (! empty($_SESSION['encryption_key'])) {
            if (empty($cfg['blowfish_secret'])) {
                trigger_error(
                    __(
                        'The configuration file now needs a secret passphrase (blowfish_secret).'
                    ),
                    E_USER_WARNING
                );
            } elseif (strlen($cfg['blowfish_secret']) < 32) {
                trigger_error(
                    __(
                        'The secret passphrase in configuration (blowfish_secret) is too short.'
                    ),
                    E_USER_WARNING
                );
            }
        }

        /**
         * Check for existence of config directory which should not exist in
         * production environment.
         */
        if (@file_exists(ROOT_PATH . 'config')) {
            trigger_error(
                __(
                    'Directory [code]config[/code], which is used by the setup script, ' .
                    'still exists in your phpMyAdmin directory. It is strongly ' .
                    'recommended to remove it once phpMyAdmin has been configured. ' .
                    'Otherwise the security of your server may be compromised by ' .
                    'unauthorized people downloading your configuration.'
                ),
                E_USER_WARNING
            );
        }

        /**
         * Warning about Suhosin only if its simulation mode is not enabled
         */
        if ($cfg['SuhosinDisableWarning'] == false
            && ini_get('suhosin.request.max_value_length')
            && ini_get('suhosin.simulation') == '0'
        ) {
            trigger_error(
                sprintf(
                    __(
                        'Server running with Suhosin. Please refer ' .
                        'to %sdocumentation%s for possible issues.'
                    ),
                    '[doc@faq1-38]',
                    '[/doc]'
                ),
                E_USER_WARNING
            );
        }

        /* Missing template cache */
        if ($this->config->getTempDir('twig') === null) {
            trigger_error(
                sprintf(
                    __(
                        'The $cfg[\'TempDir\'] (%s) is not accessible. ' .
                        'phpMyAdmin is not able to cache templates and will ' .
                        'be slow because of this.'
                    ),
                    $this->config->get('TempDir')
                ),
                E_USER_WARNING
            );
        }

        /**
         * Warning about incomplete translations.
         *
         * The data file is created while creating release by ./scripts/remove-incomplete-mo
         */
        if (@file_exists(ROOT_PATH . 'libraries/language_stats.inc.php')) {
            include ROOT_PATH . 'libraries/language_stats.inc.php';
            /*
             * This message is intentionally not translated, because we're
             * handling incomplete translations here and focus on english
             * speaking users.
             */
            if (isset($GLOBALS['language_stats'][$lang])
                && $GLOBALS['language_stats'][$lang] < $cfg['TranslationWarningThreshold']
            ) {
                trigger_error(
                    'You are using an incomplete translation, please help to make it '
                    . 'better by [a@https://www.phpmyadmin.net/translate/'
                    . '@_blank]contributing[/a].',
                    E_USER_NOTICE
                );
            }
        }
    }
}
