<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;
use PhpMyAdmin\Exceptions\AuthenticationPluginException;
use PhpMyAdmin\Exceptions\ConfigException;
use PhpMyAdmin\Exceptions\MissingExtensionException;
use PhpMyAdmin\Exceptions\SessionHandlerException;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Tracking\Tracker;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function __;
use function count;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function define;
use function extension_loaded;
use function function_exists;
use function hash_equals;
use function ini_get;
use function ini_set;
use function is_array;
use function is_scalar;
use function is_string;
use function mb_internal_encoding;
use function ob_start;
use function restore_error_handler;
use function session_id;
use function sprintf;
use function strlen;
use function trigger_error;

use const CONFIG_FILE;
use const E_USER_ERROR;

final class Common
{
    private static ServerRequest|null $request = null;

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
    public static function run(bool $isSetupPage = false): void
    {
        $GLOBALS['lang'] ??= null;
        $GLOBALS['theme'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['token_mismatch'] ??= null;

        $request = self::getRequest();
        $route = $request->getRoute();

        $isMinimumCommon = $isSetupPage || $route === '/import-status' || $route === '/url' || $route === '/messages';

        $container = Core::getContainerBuilder();

        /** @var ErrorHandler $errorHandler */
        $errorHandler = $container->get('error_handler');
        $GLOBALS['errorHandler'] = $errorHandler;

        try {
            self::checkRequiredPhpExtensions();
        } catch (MissingExtensionException $exception) {
            echo self::getGenericError($exception->getMessage());

            return;
        }

        self::configurePhpSettings();

        /** @var Config $config */
        $config = $container->get('config');
        $GLOBALS['config'] = $config;

        try {
            $config->loadAndCheck(CONFIG_FILE);
        } catch (ConfigException $exception) {
            echo self::getGenericError($exception->getMessage());

            return;
        }

        $request = self::updateUriScheme($config, $request);

        if ($route !== '/messages') {
            try {
                // Include session handling after the globals, to prevent overwriting.
                Session::setUp($config, $errorHandler);
            } catch (SessionHandlerException $exception) {
                echo self::getGenericError($exception->getMessage());

                return;
            }
        }

        $request = Core::populateRequestWithEncryptedQueryParams($request);

        /**
         * init some variables LABEL_variables_init
         */

        /**
         * holds parameters to be passed to next page
         *
         * @global array $urlParams
         */
        $GLOBALS['urlParams'] = [];
        $container->setParameter('url_params', $GLOBALS['urlParams']);

        self::setGotoAndBackGlobals($container, $config);
        self::checkTokenRequestParam();
        self::setDatabaseAndTableFromRequest($container, $request);
        self::setSQLQueryGlobalFromRequest($container, $request);

        //$_REQUEST['set_theme'] // checked later in this file LABEL_theme_setup
        //$_REQUEST['server']; // checked later in this file
        //$_REQUEST['lang'];   // checked by LABEL_loading_language_file

        /* loading language file                       LABEL_loading_language_file    */

        /**
         * lang detection is done here
         */
        $language = LanguageManager::getInstance()->selectLanguage();
        $language->activate();

        try {
            /**
             * check for errors occurred while loading configuration
             * this check is done here after loading language files to present errors in locale
             */
            $config->checkPermissions();
            $config->checkErrors();
        } catch (ConfigException $exception) {
            echo self::getGenericError($exception->getMessage());

            return;
        }

        try {
            self::checkServerConfiguration();
            self::checkRequest();
        } catch (RuntimeException $exception) {
            echo self::getGenericError($exception->getMessage());

            return;
        }

        self::setCurrentServerGlobal($container, $config, $request->getParam('server'));

        $GLOBALS['cfg'] = $config->settings;
        $settings = $config->getSettings();

        /** @var ThemeManager $themeManager */
        $themeManager = $container->get(ThemeManager::class);
        $GLOBALS['theme'] = $themeManager->initializeTheme();

        $GLOBALS['dbi'] = null;

        if ($isMinimumCommon) {
            $config->loadUserPreferences($themeManager, true);
            Tracker::enable();

            if ($route === '/url') {
                UrlRedirector::redirect($_GET['url'] ?? '');
            }

            if ($isSetupPage) {
                self::setupPageBootstrap($config);
                Routing::callSetupController($request);

                return;
            }

            Routing::callControllerForRoute($request, Routing::getDispatcher(), $container);

            return;
        }

        /**
         * save some settings in cookies
         */
        $config->setCookie('pma_lang', (string) $GLOBALS['lang']);

        $themeManager->setThemeCookie();

        $GLOBALS['dbi'] = DatabaseInterface::load();
        $container->set(DatabaseInterface::class, $GLOBALS['dbi']);
        $container->setAlias('dbi', DatabaseInterface::class);

        $currentServer = $config->getCurrentServer();
        if ($currentServer !== null) {
            $config->getLoginCookieValidityFromCache($GLOBALS['server']);

            /** @var AuthenticationPluginFactory $authPluginFactory */
            $authPluginFactory = $container->get(AuthenticationPluginFactory::class);
            try {
                $authPlugin = $authPluginFactory->create();
            } catch (AuthenticationPluginException $exception) {
                echo self::getGenericError($exception->getMessage());

                return;
            }

            $authPlugin->authenticate();
            $currentServer = new Server($GLOBALS['cfg']['Server']);

            /* Enable LOAD DATA LOCAL INFILE for LDI plugin */
            if ($route === '/import' && ($_POST['format'] ?? '') === 'ldi') {
                // Switch this before the DB connection is done
                // phpcs:disable PSR1.Files.SideEffects
                define('PMA_ENABLE_LDI', 1);
                // phpcs:enable
            }

            self::connectToDatabaseServer($GLOBALS['dbi'], $authPlugin, $currentServer);
            $authPlugin->rememberCredentials();
            $authPlugin->checkTwoFactor();

            /* Log success */
            Logging::logUser($config, $currentServer->user);

            if ($GLOBALS['dbi']->getVersion() < $settings->mysqlMinVersion['internal']) {
                echo self::getGenericError(sprintf(
                    __('You should upgrade to %s %s or later.'),
                    'MySQL',
                    $settings->mysqlMinVersion['human'],
                ));

                return;
            }

            // Sets the default delimiter (if specified).
            $sqlDelimiter = $request->getParam('sql_delimiter', '');
            if (strlen($sqlDelimiter) > 0) {
                // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
                Lexer::$DEFAULT_DELIMITER = $sqlDelimiter;
            }

            // TODO: Set SQL modes too.
        } else { // end server connecting
            $response = ResponseRenderer::getInstance();
            $response->getHeader()->disableMenuAndConsole();
            $response->setMinimalFooter();
        }

        $response = ResponseRenderer::getInstance();

        /**
         * There is no point in even attempting to process
         * an ajax request if there is a token mismatch
         */
        if ($response->isAjax() && $request->isPost() && $GLOBALS['token_mismatch']) {
            $response->setRequestStatus(false);
            $response->addJSON(
                'message',
                Message::error(__('Error: Token mismatch')),
            );

            return;
        }

        Profiling::check($GLOBALS['dbi'], $response);

        $container->set('response', ResponseRenderer::getInstance());

        // load user preferences
        $config->loadUserPreferences($themeManager);

        /* Tell tracker that it can actually work */
        Tracker::enable();

        if (! empty($GLOBALS['server']) && $settings->zeroConf) {
            /** @var Relation $relation */
            $relation = $container->get('relation');
            $GLOBALS['dbi']->postConnectControl($relation);
        }

        Routing::callControllerForRoute($request, Routing::getDispatcher(), $container);
    }

    /**
     * Checks that required PHP extensions are there.
     */
    private static function checkRequiredPhpExtensions(): void
    {
        /**
         * Warning about mbstring.
         */
        if (! function_exists('mb_detect_encoding')) {
            Core::warnMissingExtension('mbstring');
        }

        /**
         * We really need this one!
         */
        if (! function_exists('preg_replace')) {
            Core::warnMissingExtension('pcre', true);
        }

        /**
         * JSON is required in several places.
         */
        if (! function_exists('json_encode')) {
            Core::warnMissingExtension('json', true);
        }

        /**
         * ctype is required for Twig.
         */
        if (! function_exists('ctype_alpha')) {
            Core::warnMissingExtension('ctype', true);
        }

        if (! function_exists('mysqli_connect')) {
            $moreInfo = sprintf(__('See %sour documentation%s for more information.'), '[doc@faqmysql]', '[/doc]');
            Core::warnMissingExtension('mysqli', true, $moreInfo);
        }

        if (! function_exists('session_name')) {
            Core::warnMissingExtension('session', true);
        }

        /**
         * hash is required for cookie authentication.
         */
        if (function_exists('hash_hmac')) {
            return;
        }

        Core::warnMissingExtension('hash', true);
    }

    /**
     * Applies changes to PHP configuration.
     */
    private static function configurePhpSettings(): void
    {
        /**
         * Set utf-8 encoding for PHP
         */
        ini_set('default_charset', 'utf-8');
        mb_internal_encoding('utf-8');

        /**
         * Set precision to sane value, with higher values
         * things behave slightly unexpectedly, for example
         * round(1.2, 2) returns 1.199999999999999956.
         */
        ini_set('precision', '14');

        /**
         * check timezone setting
         * this could produce an E_WARNING - but only once,
         * if not done here it will produce E_WARNING on every date/time function
         */
        date_default_timezone_set(@date_default_timezone_get());
    }

    private static function setGotoAndBackGlobals(ContainerInterface $container, Config $config): void
    {
        $GLOBALS['back'] ??= null;
        $GLOBALS['urlParams'] ??= null;

        // Holds page that should be displayed.
        $GLOBALS['goto'] = '';
        $container->setParameter('goto', $GLOBALS['goto']);

        if (isset($_REQUEST['goto']) && Core::checkPageValidity($_REQUEST['goto'])) {
            $GLOBALS['goto'] = $_REQUEST['goto'];
            $GLOBALS['urlParams']['goto'] = $GLOBALS['goto'];
            $container->setParameter('goto', $GLOBALS['goto']);
            $container->setParameter('url_params', $GLOBALS['urlParams']);
        } else {
            if ($config->issetCookie('goto')) {
                $config->removeCookie('goto');
            }

            unset($_REQUEST['goto'], $_GET['goto'], $_POST['goto']);
        }

        if (isset($_REQUEST['back']) && Core::checkPageValidity($_REQUEST['back'])) {
            // Returning page.
            $GLOBALS['back'] = $_REQUEST['back'];
            $container->setParameter('back', $GLOBALS['back']);

            return;
        }

        if ($config->issetCookie('back')) {
            $config->removeCookie('back');
        }

        unset($_REQUEST['back'], $_GET['back'], $_POST['back']);
    }

    /**
     * Check whether user supplied token is valid, if not remove any possibly
     * dangerous stuff from request.
     *
     * Check for token mismatch only if the Request method is POST.
     * GET Requests would never have token and therefore checking
     * mis-match does not make sense.
     */
    public static function checkTokenRequestParam(): void
    {
        $GLOBALS['token_mismatch'] = true;
        $GLOBALS['token_provided'] = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        if (isset($_POST['token']) && is_scalar($_POST['token']) && strlen((string) $_POST['token']) > 0) {
            $GLOBALS['token_provided'] = true;
            $GLOBALS['token_mismatch'] = ! @hash_equals($_SESSION[' PMA_token '], (string) $_POST['token']);
        }

        if (! $GLOBALS['token_mismatch']) {
            return;
        }

        // Warn in case the mismatch is result of failed setting of session cookie
        if (isset($_POST['set_session']) && $_POST['set_session'] !== session_id()) {
            trigger_error(
                __(
                    'Failed to set session cookie. Maybe you are using HTTP instead of HTTPS to access phpMyAdmin.',
                ),
                E_USER_ERROR,
            );
        }

        /**
         * We don't allow any POST operation parameters if the token is mismatched
         * or is not provided.
         */
        $allowList = ['ajax_request'];
        Sanitize::removeRequestVars($allowList);
    }

    private static function setDatabaseAndTableFromRequest(ContainerInterface $container, ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;

        $db = DatabaseName::tryFromValue($request->getParam('db'));
        $table = TableName::tryFromValue($request->getParam('table'));

        $GLOBALS['db'] = $db?->getName() ?? '';
        $GLOBALS['table'] = $table?->getName() ?? '';

        if (! is_array($GLOBALS['urlParams'])) {
            $GLOBALS['urlParams'] = [];
        }

        $GLOBALS['urlParams']['db'] = $GLOBALS['db'];
        $GLOBALS['urlParams']['table'] = $GLOBALS['table'];
        $container->setParameter('url_params', $GLOBALS['urlParams']);
    }

    /**
     * Check whether PHP configuration matches our needs.
     */
    private static function checkServerConfiguration(): void
    {
        /**
         * As we try to handle charsets by ourself, mbstring overloads just
         * break it, see bug 1063821.
         *
         * We specifically use empty here as we are looking for anything else than
         * empty value or 0.
         */
        if (extension_loaded('mbstring') && ! empty(ini_get('mbstring.func_overload'))) {
            throw new RuntimeException(__(
                'You have enabled mbstring.func_overload in your PHP '
                . 'configuration. This option is incompatible with phpMyAdmin '
                . 'and might cause some data to be corrupted!',
            ));
        }

        /**
         * The ini_set and ini_get functions can be disabled using
         * disable_functions but we're relying quite a lot of them.
         */
        if (function_exists('ini_get') && function_exists('ini_set')) {
            return;
        }

        throw new RuntimeException(__(
            'The ini_get and/or ini_set functions are disabled in php.ini. phpMyAdmin requires these functions!',
        ));
    }

    /**
     * Checks request and fails with fatal error if something problematic is found
     */
    private static function checkRequest(): void
    {
        if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            throw new RuntimeException(__('GLOBALS overwrite attempt'));
        }

        /**
         * protect against possible exploits - there is no need to have so much variables
         */
        if (count($_REQUEST) <= 1000) {
            return;
        }

        throw new RuntimeException(__('possible exploit'));
    }

    private static function connectToDatabaseServer(
        DatabaseInterface $dbi,
        AuthenticationPlugin $auth,
        Server $currentServer,
    ): void {
        /**
         * Try to connect MySQL with the control user profile (will be used to get the privileges list for the current
         * user but the true user link must be open after this one, so it would be default one for all the scripts).
         */
        $controlConnection = null;
        if ($currentServer->controlUser !== '') {
            $controlConnection = $dbi->connect($currentServer, Connection::TYPE_CONTROL);
        }

        // Connects to the server (validates user's login)
        $userConnection = $dbi->connect($currentServer, Connection::TYPE_USER);
        if ($userConnection === null) {
            $auth->showFailure('mysql-denied');
        }

        if ($controlConnection !== null) {
            return;
        }

        /**
         * Open separate connection for control queries, this is needed to avoid problems with table locking used in
         * main connection and phpMyAdmin issuing queries to configuration storage, which is not locked by that time.
         */
        $dbi->connect($currentServer, Connection::TYPE_USER, Connection::TYPE_CONTROL);
    }

    public static function getRequest(): ServerRequest
    {
        if (self::$request === null) {
            self::$request = ServerRequestFactory::createFromGlobals();
        }

        return self::$request;
    }

    private static function setupPageBootstrap(Config $config): void
    {
        // use default error handler
        restore_error_handler();

        // Save current language in a cookie, since it was not set in Common::run().
        $config->setCookie('pma_lang', (string) $GLOBALS['lang']);
        $config->set('is_setup', true);

        $GLOBALS['ConfigFile'] = new ConfigFile();
        $GLOBALS['ConfigFile']->setPersistKeys([
            'DefaultLang',
            'ServerDefault',
            'UploadDir',
            'SaveDir',
            'Servers/1/verbose',
            'Servers/1/host',
            'Servers/1/port',
            'Servers/1/socket',
            'Servers/1/auth_type',
            'Servers/1/user',
            'Servers/1/password',
        ]);

        $GLOBALS['dbi'] = DatabaseInterface::load();

        // allows for redirection even after sending some data
        ob_start();
    }

    private static function setSQLQueryGlobalFromRequest(ContainerInterface $container, ServerRequest $request): void
    {
        $sqlQuery = '';
        if ($request->isPost()) {
            /** @var mixed $sqlQuery */
            $sqlQuery = $request->getParsedBodyParam('sql_query');
            if (! is_string($sqlQuery)) {
                $sqlQuery = '';
            }
        }

        $GLOBALS['sql_query'] = $sqlQuery;
        $container->setParameter('sql_query', $sqlQuery);
    }

    private static function setCurrentServerGlobal(
        ContainerInterface $container,
        Config $config,
        mixed $serverParamFromRequest,
    ): void {
        $server = $config->selectServer($serverParamFromRequest);
        $GLOBALS['server'] = $server;
        $GLOBALS['urlParams']['server'] = $server;
        $container->setParameter('server', $server);
        $container->setParameter('url_params', $GLOBALS['urlParams']);
    }

    private static function getGenericError(string $message): string
    {
        return (new Template())->render('error/generic', [
            'lang' => $GLOBALS['lang'] ?? 'en',
            'dir' => $GLOBALS['text_dir'] ?? 'ltr',
            'error_message' => $message,
        ]);
    }

    private static function updateUriScheme(Config $config, ServerRequest $request): ServerRequest
    {
        $uriScheme = $config->isHttps() ? 'https' : 'http';
        $uri = $request->getUri();
        if ($uri->getScheme() === $uriScheme) {
            return $request;
        }

        return $request->withUri($uri->withScheme($uriScheme));
    }
}
