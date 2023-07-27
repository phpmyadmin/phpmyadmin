<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Exceptions\AuthenticationPluginException;
use PhpMyAdmin\Exceptions\ConfigException;
use PhpMyAdmin\Exceptions\MissingExtensionException;
use PhpMyAdmin\Exceptions\SessionHandlerException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Routing\Routing;
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

final class Application
{
    private static ServerRequest|null $request = null;

    public function __construct(
        private readonly ErrorHandler $errorHandler,
        private readonly Config $config,
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public static function init(): self
    {
        /** @var Application $application */
        $application = Core::getContainerBuilder()->get(self::class);

        return $application;
    }

    public function run(bool $isSetupPage = false): void
    {
        $request = self::getRequest()->withAttribute('isSetupPage', $isSetupPage);
        $response = $this->handle($request);
        if ($response === null) {
            return;
        }

        $emitter = new SapiEmitter();
        $emitter->emit($response);
    }

    private function handle(ServerRequest $request): Response|null
    {
        $isSetupPage = (bool) $request->getAttribute('isSetupPage');

        $GLOBALS['errorHandler'] = $this->errorHandler;
        $GLOBALS['config'] = $this->config;

        try {
            $this->checkRequiredPhpExtensions();
        } catch (MissingExtensionException $exception) {
            // Disables template caching because the cache directory is not known yet.
            $this->template->disableCache();

            return $this->getGenericErrorResponse($exception->getMessage());
        }

        $this->configurePhpSettings();

        try {
            $this->config->loadAndCheck(CONFIG_FILE);
        } catch (ConfigException $exception) {
            // Disables template caching because the cache directory is not known yet.
            $this->template->disableCache();

            return $this->getGenericErrorResponse($exception->getMessage());
        }

        $route = $request->getRoute();

        $isMinimumCommon = $isSetupPage || $route === '/import-status' || $route === '/url' || $route === '/messages';

        $request = $this->updateUriScheme($this->config, $request);

        if ($route !== '/messages') {
            try {
                // Include session handling after the globals, to prevent overwriting.
                Session::setUp($this->config, $this->errorHandler);
            } catch (SessionHandlerException $exception) {
                return $this->getGenericErrorResponse($exception->getMessage());
            }
        }

        $request = Core::populateRequestWithEncryptedQueryParams($request);

        $container = Core::getContainerBuilder();

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

        $this->setGotoAndBackGlobals($container, $this->config);
        $this->checkTokenRequestParam();
        $this->setDatabaseAndTableFromRequest($container, $request);
        $this->setSQLQueryGlobalFromRequest($container, $request);

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
            $this->config->checkPermissions();
            $this->config->checkErrors();
        } catch (ConfigException $exception) {
            return $this->getGenericErrorResponse($exception->getMessage());
        }

        try {
            $this->checkServerConfiguration();
            $this->checkRequest();
        } catch (RuntimeException $exception) {
            return $this->getGenericErrorResponse($exception->getMessage());
        }

        $this->setCurrentServerGlobal($container, $this->config, $request->getParam('server'));

        $GLOBALS['cfg'] = $this->config->settings;
        $settings = $this->config->getSettings();

        /** @var ThemeManager $themeManager */
        $themeManager = $container->get(ThemeManager::class);
        $GLOBALS['theme'] = $themeManager->initializeTheme();

        $GLOBALS['dbi'] = null;

        if ($isMinimumCommon) {
            $this->config->loadUserPreferences($themeManager, true);
            Tracker::enable();

            if ($route === '/url') {
                UrlRedirector::redirect($_GET['url'] ?? '');
            }

            if ($isSetupPage) {
                $this->setupPageBootstrap($this->config);

                return Routing::callSetupController($request, $this->responseFactory);
            }

            return Routing::callControllerForRoute(
                $request,
                Routing::getDispatcher(),
                $container,
                $this->responseFactory,
            );
        }

        /**
         * save some settings in cookies
         */
        $this->config->setCookie('pma_lang', (string) $GLOBALS['lang']);

        $themeManager->setThemeCookie();

        $GLOBALS['dbi'] = DatabaseInterface::load();
        $container->set(DatabaseInterface::class, $GLOBALS['dbi']);
        $container->setAlias('dbi', DatabaseInterface::class);

        $currentServer = $this->config->getCurrentServer();
        if ($currentServer !== null) {
            $this->config->getLoginCookieValidityFromCache($GLOBALS['server']);

            /** @var AuthenticationPluginFactory $authPluginFactory */
            $authPluginFactory = $container->get(AuthenticationPluginFactory::class);
            try {
                $authPlugin = $authPluginFactory->create();
            } catch (AuthenticationPluginException $exception) {
                return $this->getGenericErrorResponse($exception->getMessage());
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

            $this->connectToDatabaseServer($GLOBALS['dbi'], $authPlugin, $currentServer);
            $authPlugin->rememberCredentials();
            $authPlugin->checkTwoFactor();

            /* Log success */
            Logging::logUser($this->config, $currentServer->user);

            if ($GLOBALS['dbi']->getVersion() < $settings->mysqlMinVersion['internal']) {
                return $this->getGenericErrorResponse(sprintf(
                    __('You should upgrade to %s %s or later.'),
                    'MySQL',
                    $settings->mysqlMinVersion['human'],
                ));
            }

            /** @var mixed $sqlDelimiter */
            $sqlDelimiter = $request->getParam('sql_delimiter', '');
            if (is_string($sqlDelimiter) && $sqlDelimiter !== '') {
                // Sets the default delimiter (if specified).
                Lexer::$defaultDelimiter = $sqlDelimiter;
            }

            // TODO: Set SQL modes too.
        } else { // end server connecting
            $responseRenderer = ResponseRenderer::getInstance();
            $responseRenderer->setAjax($request->isAjax());
            $responseRenderer->getHeader()->disableMenuAndConsole();
            $responseRenderer->setMinimalFooter();
        }

        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax($request->isAjax());

        /**
         * There is no point in even attempting to process
         * an ajax request if there is a token mismatch
         */
        if ($request->isAjax() && $request->isPost() && $GLOBALS['token_mismatch']) {
            $responseRenderer->setRequestStatus(false);
            $responseRenderer->addJSON(
                'message',
                Message::error(__('Error: Token mismatch')),
            );

            return null;
        }

        Profiling::check($GLOBALS['dbi'], $responseRenderer);

        $container->set('response', ResponseRenderer::getInstance());

        // load user preferences
        $this->config->loadUserPreferences($themeManager);

        /* Tell tracker that it can actually work */
        Tracker::enable();

        if (! empty($GLOBALS['server']) && $settings->zeroConf) {
            /** @var Relation $relation */
            $relation = $container->get('relation');
            $GLOBALS['dbi']->postConnectControl($relation);
        }

        return Routing::callControllerForRoute($request, Routing::getDispatcher(), $container, $this->responseFactory);
    }

    /**
     * Checks that required PHP extensions are there.
     */
    private function checkRequiredPhpExtensions(): void
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
    private function configurePhpSettings(): void
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

    private function setGotoAndBackGlobals(ContainerInterface $container, Config $config): void
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
    public function checkTokenRequestParam(): void
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

    private function setDatabaseAndTableFromRequest(ContainerInterface $container, ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;

        $db = DatabaseName::tryFrom($request->getParam('db'));
        $table = TableName::tryFrom($request->getParam('table'));

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
    private function checkServerConfiguration(): void
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
    private function checkRequest(): void
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

    private function connectToDatabaseServer(
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
            self::$request = ServerRequestFactory::create()->fromGlobals();
        }

        return self::$request;
    }

    private function setupPageBootstrap(Config $config): void
    {
        // use default error handler
        restore_error_handler();

        // Save current language in a cookie, since it was not set in Common::run().
        $config->setCookie('pma_lang', $GLOBALS['lang']);
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

    private function setSQLQueryGlobalFromRequest(ContainerInterface $container, ServerRequest $request): void
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

    private function setCurrentServerGlobal(
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

    private function getGenericErrorResponse(string $message): Response
    {
        $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $response->getBody()->write($this->template->render('error/generic', [
            'lang' => $GLOBALS['lang'] ?? 'en',
            'dir' => $GLOBALS['text_dir'] ?? 'ltr',
            'error_message' => $message,
        ]));

        return $response;
    }

    private function updateUriScheme(Config $config, ServerRequest $request): ServerRequest
    {
        $uriScheme = $config->isHttps() ? 'https' : 'http';
        $uri = $request->getUri();
        if ($uri->getScheme() === $uriScheme) {
            return $request;
        }

        return $request->withUri($uri->withScheme($uriScheme));
    }
}
