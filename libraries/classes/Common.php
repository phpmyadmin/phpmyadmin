<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\SqlParser\Lexer;

use function __;
use function defined;
use function register_shutdown_function;
use function strlen;

final class Common
{
    /**
     * Misc stuff and REQUIRED by ALL the scripts.
     * MUST be included by every script
     *
     * Among other things, it contains the advanced authentication work.
     *
     * Order of sections:
     *
     * the authentication libraries must be before the connection to db
     *
     * ... so the required order is:
     *
     * LABEL_variables_init
     *  - initialize some variables always needed
     * LABEL_parsing_config_file
     *  - parsing of the configuration file
     * LABEL_loading_language_file
     *  - loading language file
     * LABEL_setup_servers
     *  - check and setup configured servers
     * LABEL_theme_setup
     *  - setting up themes
     *
     * - load of MySQL extension (if necessary)
     * - loading of an authentication library
     * - db connection
     * - authentication work
     */
    public static function run(): void
    {
        global $containerBuilder, $errorHandler, $config, $server, $dbi, $request;
        global $lang, $cfg, $isConfigLoading, $auth_plugin, $route, $theme;
        global $urlParams, $isMinimumCommon, $sql_query, $token_mismatch;

        $request = ServerRequestFactory::createFromGlobals();

        $route = Routing::getCurrentRoute();

        if ($route === '/import-status') {
            $isMinimumCommon = true;
        }

        $containerBuilder = Core::getContainerBuilder();

        /** @var ErrorHandler $errorHandler */
        $errorHandler = $containerBuilder->get('error_handler');

        /**
         * Warning about missing PHP extensions.
         */
        Core::checkExtensions();

        /**
         * Configure required PHP settings.
         */
        Core::configure();

        /* start procedural code                       label_start_procedural         */

        Core::cleanupPathInfo();

        /* parsing configuration file                  LABEL_parsing_config_file      */

        /** @var bool $isConfigLoading Indication for the error handler */
        $isConfigLoading = false;

        /**
         * Force reading of config file, because we removed sensitive values
         * in the previous iteration.
         *
         * @var Config $config
         */
        $config = $containerBuilder->get('config');

        register_shutdown_function([Config::class, 'fatalErrorHandler']);

        /**
         * include session handling after the globals, to prevent overwriting
         */
        if (! defined('PMA_NO_SESSION')) {
            Session::setUp($config, $errorHandler);
        }

        /**
         * init some variables LABEL_variables_init
         */

        /**
         * holds parameters to be passed to next page
         *
         * @global array $urlParams
         */
        $urlParams = [];
        $containerBuilder->setParameter('url_params', $urlParams);

        Core::setGotoAndBackGlobals($containerBuilder, $config);

        Core::checkTokenRequestParam();

        Core::setDatabaseAndTableFromRequest($containerBuilder);

        /**
         * SQL query to be executed
         *
         * @global string $sql_query
         */
        $sql_query = '';
        if ($request->isPost()) {
            $sql_query = $request->getParsedBodyParam('sql_query', '');
        }

        $containerBuilder->setParameter('sql_query', $sql_query);

        //$_REQUEST['set_theme'] // checked later in this file LABEL_theme_setup
        //$_REQUEST['server']; // checked later in this file
        //$_REQUEST['lang'];   // checked by LABEL_loading_language_file

        /* loading language file                       LABEL_loading_language_file    */

        /**
         * lang detection is done here
         */
        $language = LanguageManager::getInstance()->selectLanguage();
        $language->activate();

        /**
         * check for errors occurred while loading configuration
         * this check is done here after loading language files to present errors in locale
         */
        $config->checkPermissions();
        $config->checkErrors();

        /* Check server configuration */
        Core::checkConfiguration();

        /* Check request for possible attacks */
        Core::checkRequest();

        /* setup servers                                       LABEL_setup_servers    */

        $config->checkServers();

        /**
         * current server
         *
         * @global integer $server
         */
        $server = $config->selectServer();
        $urlParams['server'] = $server;
        $containerBuilder->setParameter('server', $server);
        $containerBuilder->setParameter('url_params', $urlParams);

        /**
         * BC - enable backward compatibility
         * exports all configuration settings into globals ($cfg global)
         */
        $config->enableBc();

        /* setup themes                                          LABEL_theme_setup    */

        $theme = ThemeManager::initializeTheme();

        /** @var DatabaseInterface $dbi */
        $dbi = null;

        if (isset($isMinimumCommon)) {
            $config->loadUserPreferences();
            $containerBuilder->set('theme_manager', ThemeManager::getInstance());
            Tracker::enable();

            return;
        }

        /**
         * save some settings in cookies
         *
         * @todo should be done in PhpMyAdmin\Config
         */
        $config->setCookie('pma_lang', (string) $lang);

        ThemeManager::getInstance()->setThemeCookie();

        $dbi = DatabaseInterface::load();
        $containerBuilder->set(DatabaseInterface::class, $dbi);
        $containerBuilder->setAlias('dbi', DatabaseInterface::class);

        if (! empty($cfg['Server'])) {
            $config->getLoginCookieValidityFromCache($server);

            $auth_plugin = Plugins::getAuthPlugin();
            $auth_plugin->authenticate();

            Core::connectToDatabaseServer($dbi, $auth_plugin);

            $auth_plugin->rememberCredentials();

            $auth_plugin->checkTwoFactor();

            /* Log success */
            Logging::logUser($cfg['Server']['user']);

            if ($dbi->getVersion() < $cfg['MysqlMinVersion']['internal']) {
                Core::fatalError(
                    __('You should upgrade to %s %s or later.'),
                    [
                        'MySQL',
                        $cfg['MysqlMinVersion']['human'],
                    ]
                );
            }

            // Sets the default delimiter (if specified).
            $sqlDelimiter = $request->getParam('sql_delimiter', '');
            if (strlen($sqlDelimiter) > 0) {
                Lexer::$DEFAULT_DELIMITER = $sqlDelimiter;
            }

            unset($sqlDelimiter);

            // TODO: Set SQL modes too.
        } else { // end server connecting
            $response = ResponseRenderer::getInstance();
            $response->getHeader()->disableMenuAndConsole();
            $response->getFooter()->setMinimal();
        }

        $response = ResponseRenderer::getInstance();

        /**
         * There is no point in even attempting to process
         * an ajax request if there is a token mismatch
         */
        if ($response->isAjax() && $request->isPost() && $token_mismatch) {
            $response->setRequestStatus(false);
            $response->addJSON(
                'message',
                Message::error(__('Error: Token mismatch'))
            );
            exit;
        }

        Profiling::check($dbi, $response);

        $containerBuilder->set('response', ResponseRenderer::getInstance());

        // load user preferences
        $config->loadUserPreferences();

        $containerBuilder->set('theme_manager', ThemeManager::getInstance());

        /* Tell tracker that it can actually work */
        Tracker::enable();

        if (empty($server) || ! isset($cfg['ZeroConf']) || $cfg['ZeroConf'] !== true) {
            return;
        }

        $dbi->postConnectControl();
    }
}
