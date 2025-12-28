<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Git;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function count;
use function extension_loaded;
use function file_exists;
use function ini_get;
use function is_array;
use function mb_strlen;
use function preg_match;
use function sprintf;

use const PHP_VERSION;
use const SODIUM_CRYPTO_SECRETBOX_KEYBYTES;

#[Route('[/]', ['GET', 'POST'])]
final class HomeController implements InvocableController
{
    /**
     * @var array<int, array<string, string>>
     * @psalm-var list<array{message: string, severity: 'warning'|'notice'}>
     */
    private array $errors = [];

    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Config $config,
        private readonly ThemeManager $themeManager,
        private readonly DatabaseInterface $dbi,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->shouldRedirectToDatabaseOrTablePage($request)) {
            return $this->redirectToDatabaseOrTablePage($request);
        }

        if ($request->isAjax() && ! empty($_REQUEST['access_time'])) {
            return $this->response->response();
        }

        $this->response->addScriptFiles(['home.js']);

        // This is for $cfg['ShowDatabasesNavigationAsTree'] = false;
        // See: https://github.com/phpmyadmin/phpmyadmin/issues/16520
        // The DB is defined here and sent to the JS front-end to refresh the DB tree
        Current::$database = $request->getParsedBodyParamAsString('db', '');
        Current::$table = '';

        if (Current::$server > 0 && $this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $languageManager = LanguageManager::getInstance();

        if (Current::$message !== null) {
            $displayMessage = Generator::getMessage(Current::$message);
            Current::$message = null;
        }

        if (isset($_SESSION['partial_logout'])) {
            $partialLogout = Message::success(__(
                'You were logged out from one server, to logout completely '
                . 'from phpMyAdmin, you need to logout from all servers.',
            ))->getDisplay();
            unset($_SESSION['partial_logout']);
        }

        $syncFavoriteTables = RecentFavoriteTables::getInstance(TableType::Favorite)
            ->getHtmlSyncFavoriteTables();

        $hasServer = Current::$server > 0 || count($this->config->settings['Servers']) > 1;
        if ($hasServer) {
            $hasServerSelection = $this->config->config->ServerDefault === 0
                || (
                    $this->config->settings['NavigationDisplayServers']
                    && (
                        count($this->config->settings['Servers']) > 1
                        || (Current::$server === 0 && count($this->config->settings['Servers']) === 1)
                    )
                );
            if ($hasServerSelection) {
                $serverSelection = Select::render(true);
            }

            if (Current::$server > 0) {
                $charsets = Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']);
                $collations = Charsets::getCollations($this->dbi, $this->config->selectedServer['DisableIS']);
                $charsetsList = [];
                foreach ($charsets as $charset) {
                    $collationsList = [];
                    foreach ($collations[$charset->getName()] as $collation) {
                        $collationsList[] = [
                            'name' => $collation->getName(),
                            'description' => $collation->getDescription(),
                            'is_selected' => $this->dbi->getDefaultCollation() === $collation->getName(),
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
        if ($this->config->config->Lang === '' && $languageManager->hasChoice()) {
            $availableLanguages = $languageManager->sortedLanguages();
        }

        $showServerInfo = $this->config->settings['ShowServerInfo'];
        $databaseServer = [];
        if (Current::$server > 0 && ($showServerInfo === true || $showServerInfo === 'database-server')) {
            $hostInfo = '';
            if (! empty($this->config->selectedServer['verbose'])) {
                $hostInfo .= $this->config->selectedServer['verbose'] . ' (';
            }

            $hostInfo .= $this->dbi->getHostInfo();
            if (! empty($this->config->selectedServer['verbose'])) {
                $hostInfo .= ')';
            }

            $serverCharset = Charsets::getServerCharset($this->dbi, $this->config->selectedServer['DisableIS']);
            $databaseServer = [
                'host' => $hostInfo,
                'hostname' => $this->dbi->fetchValue('SELECT @@hostname;'),
                'type' => Util::getServerType(),
                'connection' => Generator::getServerSSL(),
                'version' => $this->dbi->getVersionString() . ' - ' . $this->dbi->getVersionComment(),
                'user' => $this->dbi->fetchValue('SELECT USER();'),
                'charset' => $serverCharset->getDescription() . ' (' . $serverCharset->getName() . ')',
            ];
        }

        $webServer = [];
        if ($showServerInfo === true || $showServerInfo === 'web-server') {
            $webServer['software'] = $_SERVER['SERVER_SOFTWARE'] ?? null;

            if (Current::$server > 0) {
                $clientVersion = $this->dbi->getClientInfo();
                if (preg_match('#\d+\.\d+\.\d+#', $clientVersion) === 1) {
                    $clientVersion = 'libmysql - ' . $clientVersion;
                }

                $webServer['database'] = $clientVersion;
                $webServer['php_extensions'] = Util::listPHPExtensions();
                $webServer['php_version'] = PHP_VERSION;
            }
        }

        $relation = new Relation($this->dbi);
        if (Current::$server > 0 && $relation->arePmadbTablesAllDisabled() === false) {
            $relationParameters = $relation->getRelationParameters();
            if (
                ! $relationParameters->hasAllFeatures()
                && ! $this->config->settings['PmaNoRelation_DisableWarning']
            ) {
                $messageText = __(
                    'The phpMyAdmin configuration storage is not completely '
                    . 'configured, some extended features have been deactivated. '
                    . '%sFind out why%s. ',
                );
                if ($this->config->settings['ZeroConf']) {
                    $messageText .= '<br>'
                        . __('Or alternately go to \'Operations\' tab of any database to set it up there.');
                }

                $messageInstance = Message::notice($messageText);
                $messageInstance->addParamHtml(
                    '<a href="' . Url::getFromRoute('/check-relations')
                    . '" data-post="' . Url::getCommon() . '">',
                );
                $messageInstance->addParamHtml('</a>');
                /* Show error if user has configured something, notice elsewhere */
                if (! empty($this->config->settings['Servers'][Current::$server]['pmadb'])) {
                    $messageInstance->setType(MessageType::Error);
                }

                $configStorageMessage = $messageInstance->getDisplay();
            }
        }

        $this->checkRequirements();

        $git = new Git($this->config->config->ShowGitRevision);

        $this->response->render('home/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'message' => $displayMessage ?? '',
            'partial_logout' => $partialLogout ?? '',
            'is_git_revision' => $git->isGitRevision(),
            'server' => Current::$server,
            'sync_favorite_tables' => $syncFavoriteTables,
            'has_server' => $hasServer,
            'is_demo' => $this->config->config->debug->demo,
            'has_server_selection' => $hasServerSelection ?? false,
            'server_selection' => $serverSelection ?? '',
            'has_change_password_link' => $this->config->selectedServer['auth_type'] !== 'config'
                && $this->config->settings['ShowChgPassword'],
            'charsets' => $charsetsList ?? [],
            'available_languages' => $availableLanguages,
            'database_server' => $databaseServer,
            'web_server' => $webServer,
            'show_php_info' => $this->config->settings['ShowPhpInfo'],
            'is_version_checked' => $this->config->settings['VersionCheck'],
            'phpmyadmin_major_version' => Version::SERIES,
            'config_storage_message' => $configStorageMessage ?? '',
            'has_theme_manager' => $this->config->settings['ThemeManager'],
            'themes' => $this->themeManager->getThemesArray(),
            'errors' => $this->errors,
        ]);

        return $this->response->response();
    }

    private function checkRequirements(): void
    {
        $this->checkPhpExtensionsRequirements();

        if (! $this->config->settings['LoginCookieValidityDisableWarning']) {
            /**
             * Check whether session.gc_maxlifetime limits session validity.
             */
            $gcTime = (int) ini_get('session.gc_maxlifetime');
            if ($gcTime < $this->config->config->LoginCookieValidity) {
                $this->errors[] = [
                    'message' => __(
                        'Your PHP parameter [a@https://www.php.net/manual/en/session.' .
                        'configuration.php#ini.session.gc-maxlifetime@_blank]session.' .
                        'gc_maxlifetime[/a] is lower than cookie validity configured ' .
                        'in phpMyAdmin, because of this, your login might expire sooner ' .
                        'than configured in phpMyAdmin.',
                    ),
                    'severity' => 'warning',
                ];
            }
        }

        /**
         * Check whether LoginCookieValidity is limited by LoginCookieStore.
         */
        if (
            $this->config->settings['LoginCookieStore'] !== 0
            && $this->config->settings['LoginCookieStore'] < $this->config->config->LoginCookieValidity
        ) {
            $this->errors[] = [
                'message' => __(
                    'Login cookie store is lower than cookie validity configured in ' .
                    'phpMyAdmin, because of this, your login will expire sooner than ' .
                    'configured in phpMyAdmin.',
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Warning if using the default MySQL controluser account
         */
        if (
            isset($this->config->selectedServer['controluser'], $this->config->selectedServer['controlpass'])
            && Current::$server > 0
            && $this->config->selectedServer['controluser'] === 'pma'
            && $this->config->selectedServer['controlpass'] === 'pmapass'
        ) {
            $this->errors[] = [
                'message' => __(
                    'Your server is running with default values for the ' .
                    'controluser and password (controlpass) and is open to ' .
                    'intrusion; you really should fix this security weakness' .
                    ' by changing the password for controluser \'pma\'.',
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Check if user does not have defined blowfish secret and it is being used.
         */
        if (! empty($_SESSION['encryption_key'])) {
            // This can happen if the user did use getenv() to set blowfish_secret
            $encryptionKeyLength = mb_strlen($this->config->config->blowfish_secret, '8bit');

            if ($encryptionKeyLength < SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                $this->errors[] = [
                    'message' => __(
                        'The configuration file needs a valid key for cookie encryption.'
                        . ' A temporary key was automatically generated for you.'
                        . ' Please refer to the [doc@cfg_blowfish_secret]documentation[/doc].',
                    ),
                    'severity' => 'warning',
                ];
            } elseif ($encryptionKeyLength > SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
                $this->errors[] = [
                    'message' => sprintf(
                        __(
                            'The cookie encryption key in the configuration file is longer than necessary.'
                            . ' It should only be %d bytes long.'
                            . ' Please refer to the [doc@cfg_blowfish_secret]documentation[/doc].',
                        ),
                        SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
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
                    'unauthorized people downloading your configuration.',
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Warning about Suhosin only if its simulation mode is not enabled
         */
        if (
            ! $this->config->settings['SuhosinDisableWarning']
            && ini_get('suhosin.request.max_value_length')
            && ini_get('suhosin.simulation') == '0'
        ) {
            $this->errors[] = [
                'message' => sprintf(
                    __(
                        'Server running with Suhosin. Please refer to %sdocumentation%s for possible issues.',
                    ),
                    '[doc@faq1-38]',
                    '[/doc]',
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
                        'be slow because of this.',
                    ),
                    $this->config->config->TempDir,
                ),
                'severity' => 'warning',
            ];
        }

        /**
         * Check for missing HTTP_HOST
         * This commonly occurs with nginx >= 1.25.0 and HTTP/3 configurations
         */
        if (Core::getEnv('HTTP_HOST') === '') {
            $this->errors[] = [
                'message' => __(
                    'The [code]HTTP_HOST[/code] variable is missing,'
                    . ' which might cause phpMyAdmin to not work properly.'
                    . ' Please refer to [doc@faq1-46]documentation[/doc] for possible issues.',
                ),
                'severity' => 'warning',
            ];
        }

        $this->checkLanguageStats();
    }

    private function checkLanguageStats(): void
    {
        /**
         * Warning about incomplete translations.
         *
         * The data file is created while creating release by ./bin/remove-incomplete-mo
         */
        if (! @file_exists(ROOT_PATH . 'app/language_stats.inc.php')) {
            return;
        }

        /** @psalm-suppress MissingFile */
        $languageStats = include ROOT_PATH . 'app/language_stats.inc.php';
        if (
            ! is_array($languageStats)
            || ! isset($languageStats[Current::$lang])
            || $languageStats[Current::$lang] >= $this->config->settings['TranslationWarningThreshold']
        ) {
            return;
        }

        /**
         * This message is intentionally not translated, because we're handling incomplete translations here and focus
         * on english speaking users.
         */
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
                    . ' unexpected results.',
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
            'message' => __(
                'The curl extension was not found and allow_url_fopen is '
                . 'disabled. Due to this some features such as error reporting '
                . 'or version check are disabled.',
            ),
            'severity' => 'notice',
        ];
    }

    /** @see https://docs.phpmyadmin.net/en/latest/faq.html#faq1-34 phpMyAdmin FAQ 1.34 */
    private function shouldRedirectToDatabaseOrTablePage(ServerRequest $request): bool
    {
        return ! $request->isAjax()
            && $request->getMethod() === RequestMethodInterface::METHOD_GET
            && $request->hasQueryParam('db')
            && DatabaseName::tryFrom($request->getQueryParam('db')) !== null;
    }

    /** @see https://docs.phpmyadmin.net/en/latest/faq.html#faq1-34 phpMyAdmin FAQ 1.34 */
    private function redirectToDatabaseOrTablePage(ServerRequest $request): Response
    {
        $db = DatabaseName::from($request->getQueryParam('db'));
        $table = TableName::tryFrom($request->getQueryParam('table'));
        $route = '/database/structure';
        $params = ['db' => $db->getName()];
        if ($table !== null) {
            $route = '/sql';
            $params['table'] = $table->getName();
        }

        return $this->responseFactory->createResponse(StatusCodeInterface::STATUS_FOUND)
            ->withHeader('Location', './index.php?route=' . $route . Url::getCommonRaw($params, '&'));
    }
}
