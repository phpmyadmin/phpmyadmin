<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Git;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function count;
use function extension_loaded;
use function file_exists;
use function ini_get;
use function mb_strlen;
use function preg_match;
use function sprintf;

use const PHP_VERSION;
use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

class HomeController extends AbstractController
{
    /** @var Config */
    private $config;

    /** @var ThemeManager */
    private $themeManager;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @var array<int, array<string, string>>
     * @psalm-var list<array{message: string, severity: 'warning'|'notice'}>
     */
    private $errors = [];

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Config $config,
        ThemeManager $themeManager,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->config = $config;
        $this->themeManager = $themeManager;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['server'] = $GLOBALS['server'] ?? null;
        $GLOBALS['collation_connection'] = $GLOBALS['collation_connection'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['show_query'] = $GLOBALS['show_query'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        if ($this->response->isAjax() && ! empty($_REQUEST['access_time'])) {
            return;
        }

        $this->addScriptFiles(['home.js']);

        // This is for $cfg['ShowDatabasesNavigationAsTree'] = false;
        // See: https://github.com/phpmyadmin/phpmyadmin/issues/16520
        // The DB is defined here and sent to the JS front-end to refresh the DB tree
        $GLOBALS['db'] = $_POST['db'] ?? '';
        $GLOBALS['table'] = '';
        $GLOBALS['show_query'] = '1';
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        if ($GLOBALS['server'] > 0 && $this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $languageManager = LanguageManager::getInstance();

        if (! empty($GLOBALS['message'])) {
            $displayMessage = Generator::getMessage($GLOBALS['message']);
            unset($GLOBALS['message']);
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

        $hasServer = $GLOBALS['server'] > 0 || count($GLOBALS['cfg']['Servers']) > 1;
        if ($hasServer) {
            $hasServerSelection = $GLOBALS['cfg']['ServerDefault'] == 0
                || (! $GLOBALS['cfg']['NavigationDisplayServers']
                && (count($GLOBALS['cfg']['Servers']) > 1
                || ($GLOBALS['server'] == 0 && count($GLOBALS['cfg']['Servers']) === 1)));
            if ($hasServerSelection) {
                $serverSelection = Select::render(true, true);
            }

            if ($GLOBALS['server'] > 0) {
                $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
                $checkUserPrivileges->getPrivileges();

                $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
                $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
                $charsetsList = [];
                foreach ($charsets as $charset) {
                    $collationsList = [];
                    foreach ($collations[$charset->getName()] as $collation) {
                        $collationsList[] = [
                            'name' => $collation->getName(),
                            'description' => $collation->getDescription(),
                            'is_selected' => $GLOBALS['collation_connection'] === $collation->getName(),
                        ];
                    }

                    $charsetsList[] = [
                        'name' => $charset->getName(),
                        'description' => $charset->getDescription(),
                        'collations' => $collationsList,
                    ];
                }
            }
        }

        $availableLanguages = [];
        if (empty($GLOBALS['cfg']['Lang']) && $languageManager->hasChoice()) {
            $availableLanguages = $languageManager->sortedLanguages();
        }

        $databaseServer = [];
        if ($GLOBALS['server'] > 0 && $GLOBALS['cfg']['ShowServerInfo']) {
            $hostInfo = '';
            if (! empty($GLOBALS['cfg']['Server']['verbose'])) {
                $hostInfo .= $GLOBALS['cfg']['Server']['verbose'] . ' (';
            }

            $hostInfo .= $this->dbi->getHostInfo();
            if (! empty($GLOBALS['cfg']['Server']['verbose'])) {
                $hostInfo .= ')';
            }

            $serverCharset = Charsets::getServerCharset($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
            $databaseServer = [
                'host' => $hostInfo,
                'type' => Util::getServerType(),
                'connection' => Generator::getServerSSL(),
                'version' => $this->dbi->getVersionString() . ' - ' . $this->dbi->getVersionComment(),
                'protocol' => $this->dbi->getProtoInfo(),
                'user' => $this->dbi->fetchValue('SELECT USER();'),
                'charset' => $serverCharset->getDescription() . ' (' . $serverCharset->getName() . ')',
            ];
        }

        $webServer = [];
        if ($GLOBALS['cfg']['ShowServerInfo']) {
            $webServer['software'] = $_SERVER['SERVER_SOFTWARE'] ?? null;

            if ($GLOBALS['server'] > 0) {
                $clientVersion = $this->dbi->getClientInfo();
                if (preg_match('#\d+\.\d+\.\d+#', $clientVersion)) {
                    $clientVersion = 'libmysql - ' . $clientVersion;
                }

                $webServer['database'] = $clientVersion;
                $webServer['php_extensions'] = Util::listPHPExtensions();
                $webServer['php_version'] = PHP_VERSION;
            }
        }

        $relation = new Relation($this->dbi);
        if ($GLOBALS['server'] > 0) {
            $relationParameters = $relation->getRelationParameters();
            if (! $relationParameters->hasAllFeatures() && $GLOBALS['cfg']['PmaNoRelation_DisableWarning'] == false) {
                $messageText = __(
                    'The phpMyAdmin configuration storage is not completely '
                    . 'configured, some extended features have been deactivated. '
                    . '%sFind out why%s. '
                );
                if ($GLOBALS['cfg']['ZeroConf'] == true) {
                    $messageText .= '<br>' .
                        __('Or alternately go to \'Operations\' tab of any database to set it up there.');
                }

                $messageInstance = Message::notice($messageText);
                $messageInstance->addParamHtml(
                    '<a href="' . Url::getFromRoute('/check-relations')
                    . '" data-post="' . Url::getCommon() . '">'
                );
                $messageInstance->addParamHtml('</a>');
                /* Show error if user has configured something, notice elsewhere */
                if (! empty($GLOBALS['cfg']['Servers'][$GLOBALS['server']]['pmadb'])) {
                    $messageInstance->isError(true);
                }

                $configStorageMessage = $messageInstance->getDisplay();
            }
        }

        $this->checkRequirements();

        $git = new Git($this->config->get('ShowGitRevision') ?? true);

        $this->render('home/index', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'message' => $displayMessage ?? '',
            'partial_logout' => $partialLogout ?? '',
            'is_git_revision' => $git->isGitRevision(),
            'server' => $GLOBALS['server'],
            'sync_favorite_tables' => $syncFavoriteTables,
            'has_server' => $hasServer,
            'is_demo' => $GLOBALS['cfg']['DBG']['demo'],
            'has_server_selection' => $hasServerSelection ?? false,
            'server_selection' => $serverSelection ?? '',
            'has_change_password_link' => $GLOBALS['cfg']['Server']['auth_type'] !== 'config'
                && $GLOBALS['cfg']['ShowChgPassword'],
            'charsets' => $charsetsList ?? [],
            'available_languages' => $availableLanguages,
            'database_server' => $databaseServer,
            'web_server' => $webServer,
            'show_php_info' => $GLOBALS['cfg']['ShowPhpInfo'],
            'is_version_checked' => $GLOBALS['cfg']['VersionCheck'],
            'phpmyadmin_version' => Version::VERSION,
            'phpmyadmin_major_version' => Version::SERIES,
            'config_storage_message' => $configStorageMessage ?? '',
            'has_theme_manager' => $GLOBALS['cfg']['ThemeManager'],
            'themes' => $this->themeManager->getThemesArray(),
            'errors' => $this->errors,
        ]);
    }

    private function checkRequirements(): void
    {
        $GLOBALS['server'] = $GLOBALS['server'] ?? null;

        $this->checkPhpExtensionsRequirements();

        if ($GLOBALS['cfg']['LoginCookieValidityDisableWarning'] == false) {
            /**
             * Check whether session.gc_maxlifetime limits session validity.
             */
            $gc_time = (int) ini_get('session.gc_maxlifetime');
            if ($gc_time < $GLOBALS['cfg']['LoginCookieValidity']) {
                $this->errors[] = [
                    'message' => __(
                        'Your PHP parameter [a@https://www.php.net/manual/en/session.' .
                        'configuration.php#ini.session.gc-maxlifetime@_blank]session.' .
                        'gc_maxlifetime[/a] is lower than cookie validity configured ' .
                        'in phpMyAdmin, because of this, your login might expire sooner ' .
                        'than configured in phpMyAdmin.'
                    ),
                    'severity' => 'warning',
                ];
            }
        }

        /**
         * Check whether LoginCookieValidity is limited by LoginCookieStore.
         */
        if (
            $GLOBALS['cfg']['LoginCookieStore'] != 0
            && $GLOBALS['cfg']['LoginCookieStore'] < $GLOBALS['cfg']['LoginCookieValidity']
        ) {
            $this->errors[] = [
                'message' => __(
                    'Login cookie store is lower than cookie validity configured in ' .
                    'phpMyAdmin, because of this, your login will expire sooner than ' .
                    'configured in phpMyAdmin.'
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Warning if using the default MySQL controluser account
         */
        if (
            isset($GLOBALS['cfg']['Server']['controluser'], $GLOBALS['cfg']['Server']['controlpass'])
            && $GLOBALS['server'] != 0
            && $GLOBALS['cfg']['Server']['controluser'] === 'pma'
            && $GLOBALS['cfg']['Server']['controlpass'] === 'pmapass'
        ) {
            $this->errors[] = [
                'message' => __(
                    'Your server is running with default values for the ' .
                    'controluser and password (controlpass) and is open to ' .
                    'intrusion; you really should fix this security weakness' .
                    ' by changing the password for controluser \'pma\'.'
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Check if user does not have defined blowfish secret and it is being used.
         */
        if (! empty($_SESSION['encryption_key'])) {
            if (empty($GLOBALS['cfg']['blowfish_secret'])) {
                $this->errors[] = [
                    'message' => __(
                        'The configuration file now needs a secret passphrase (blowfish_secret).'
                    ),
                    'severity' => 'warning',
                ];
            } elseif (mb_strlen($GLOBALS['cfg']['blowfish_secret'], '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                $this->errors[] = [
                    'message' => sprintf(
                        __(
                            'The secret passphrase in configuration (blowfish_secret) is not the correct length.'
                            . ' It should be %d bytes long.'
                        ),
                        SODIUM_CRYPTO_SECRETBOX_KEYBYTES
                    ),
                    'severity' => 'warning',
                ];
            }
        }

        /**
         * Check for existence of config directory which should not exist in
         * production environment.
         */
        if (@file_exists(ROOT_PATH . 'config')) {
            $this->errors[] = [
                'message' => __(
                    'Directory [code]config[/code], which is used by the setup script, ' .
                    'still exists in your phpMyAdmin directory. It is strongly ' .
                    'recommended to remove it once phpMyAdmin has been configured. ' .
                    'Otherwise the security of your server may be compromised by ' .
                    'unauthorized people downloading your configuration.'
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Warning about Suhosin only if its simulation mode is not enabled
         */
        if (
            $GLOBALS['cfg']['SuhosinDisableWarning'] == false
            && ini_get('suhosin.request.max_value_length')
            && ini_get('suhosin.simulation') == '0'
        ) {
            $this->errors[] = [
                'message' => sprintf(
                    __(
                        'Server running with Suhosin. Please refer to %sdocumentation%s for possible issues.'
                    ),
                    '[doc@faq1-38]',
                    '[/doc]'
                ),
                'severity' => 'warning',
            ];
        }

        /* Missing template cache */
        if ($this->config->getTempDir('twig') === null) {
            $this->errors[] = [
                'message' => sprintf(
                    __(
                        'The $cfg[\'TempDir\'] (%s) is not accessible. ' .
                        'phpMyAdmin is not able to cache templates and will ' .
                        'be slow because of this.'
                    ),
                    $this->config->get('TempDir')
                ),
                'severity' => 'warning',
            ];
        }

        $this->checkLanguageStats();
    }

    private function checkLanguageStats(): void
    {
        $GLOBALS['lang'] = $GLOBALS['lang'] ?? null;

        /**
         * Warning about incomplete translations.
         *
         * The data file is created while creating release by ./scripts/remove-incomplete-mo
         */
        if (! @file_exists(ROOT_PATH . 'libraries/language_stats.inc.php')) {
            return;
        }

        /** @psalm-suppress MissingFile */
        include ROOT_PATH . 'libraries/language_stats.inc.php';
        /*
         * This message is intentionally not translated, because we're
         * handling incomplete translations here and focus on english
         * speaking users.
         */
        if (
            ! isset($GLOBALS['language_stats'][$GLOBALS['lang']])
            || $GLOBALS['language_stats'][$GLOBALS['lang']] >= $GLOBALS['cfg']['TranslationWarningThreshold']
        ) {
            return;
        }

        $this->errors[] = [
            'message' => 'You are using an incomplete translation, please help to make it '
                . 'better by [a@https://www.phpmyadmin.net/translate/'
                . '@_blank]contributing[/a].',
            'severity' => 'notice',
        ];
    }

    private function checkPhpExtensionsRequirements(): void
    {
        /**
         * mbstring is used for handling multibytes inside parser, so it is good
         * to tell user something might be broken without it, see bug #1063149.
         */
        if (! extension_loaded('mbstring')) {
            $this->errors[] = [
                'message' => __(
                    'The mbstring PHP extension was not found and you seem to be using'
                    . ' a multibyte charset. Without the mbstring extension phpMyAdmin'
                    . ' is unable to split strings correctly and it may result in'
                    . ' unexpected results.'
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Missing functionality
         */
        if (extension_loaded('curl') || ini_get('allow_url_fopen')) {
            return;
        }

        $this->errors[] = [
            'message' =>  __(
                'The curl extension was not found and allow_url_fopen is '
                . 'disabled. Due to this some features such as error reporting '
                . 'or version check are disabled.'
            ),
            'severity' => 'notice',
        ];
    }
}
