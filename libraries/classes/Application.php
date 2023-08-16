<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Exceptions\AuthenticationPluginException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Handler\ApplicationHandler;
use PhpMyAdmin\Http\Handler\QueueRequestHandler;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Middleware\ConfigErrorAndPermissionChecking;
use PhpMyAdmin\Middleware\ConfigLoading;
use PhpMyAdmin\Middleware\CurrentServerGlobalSetting;
use PhpMyAdmin\Middleware\DatabaseAndTableSetting;
use PhpMyAdmin\Middleware\EncryptedQueryParamsHandling;
use PhpMyAdmin\Middleware\ErrorHandling;
use PhpMyAdmin\Middleware\GlobalConfigSetting;
use PhpMyAdmin\Middleware\LanguageAndThemeCookieSaving;
use PhpMyAdmin\Middleware\LanguageLoading;
use PhpMyAdmin\Middleware\MinimumCommonRedirection;
use PhpMyAdmin\Middleware\OutputBuffering;
use PhpMyAdmin\Middleware\PhpExtensionsChecking;
use PhpMyAdmin\Middleware\PhpSettingsConfiguration;
use PhpMyAdmin\Middleware\RequestProblemChecking;
use PhpMyAdmin\Middleware\RouteParsing;
use PhpMyAdmin\Middleware\ServerConfigurationChecking;
use PhpMyAdmin\Middleware\SessionHandling;
use PhpMyAdmin\Middleware\SetupPageRedirection;
use PhpMyAdmin\Middleware\SqlQueryGlobalSetting;
use PhpMyAdmin\Middleware\ThemeInitialization;
use PhpMyAdmin\Middleware\TokenRequestParamChecking;
use PhpMyAdmin\Middleware\UriSchemeUpdating;
use PhpMyAdmin\Middleware\UrlParamsSetting;
use PhpMyAdmin\Middleware\UrlRedirection;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Routing\Routing;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Tracking\Tracker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

use function __;
use function define;
use function function_exists;
use function hash_equals;
use function is_array;
use function is_scalar;
use function is_string;
use function session_id;
use function sprintf;
use function strlen;
use function trigger_error;

use const E_USER_ERROR;

class Application
{
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
        $GLOBALS['errorHandler'] = $this->errorHandler;

        $requestHandler = new QueueRequestHandler(new ApplicationHandler($this));
        $requestHandler->add(new ErrorHandling($this->errorHandler));
        $requestHandler->add(new OutputBuffering());
        $requestHandler->add(new PhpExtensionsChecking($this, $this->template, $this->responseFactory));
        $requestHandler->add(new ServerConfigurationChecking($this->template, $this->responseFactory));
        $requestHandler->add(new PhpSettingsConfiguration());
        $requestHandler->add(new RouteParsing());
        $requestHandler->add(new ConfigLoading($this->config, $this->template, $this->responseFactory));
        $requestHandler->add(new UriSchemeUpdating($this->config));
        $requestHandler->add(new SessionHandling(
            $this->config,
            $this->errorHandler,
            $this->template,
            $this->responseFactory,
        ));
        $requestHandler->add(new EncryptedQueryParamsHandling());
        $requestHandler->add(new UrlParamsSetting($this->config));
        $requestHandler->add(new TokenRequestParamChecking($this));
        $requestHandler->add(new DatabaseAndTableSetting($this));
        $requestHandler->add(new SqlQueryGlobalSetting());
        $requestHandler->add(new LanguageLoading());
        $requestHandler->add(new ConfigErrorAndPermissionChecking(
            $this->config,
            $this->template,
            $this->responseFactory,
        ));
        $requestHandler->add(new RequestProblemChecking($this->template, $this->responseFactory));
        $requestHandler->add(new CurrentServerGlobalSetting($this->config));
        $requestHandler->add(new GlobalConfigSetting($this->config));
        $requestHandler->add(new ThemeInitialization());
        $requestHandler->add(new UrlRedirection($this->config));
        $requestHandler->add(new SetupPageRedirection($this->config, $this->responseFactory));
        $requestHandler->add(new MinimumCommonRedirection($this->config, $this->responseFactory));
        $requestHandler->add(new LanguageAndThemeCookieSaving($this->config));

        $runner = new RequestHandlerRunner(
            $requestHandler,
            new SapiEmitter(),
            static function () use ($isSetupPage): ServerRequestInterface {
                return ServerRequestFactory::create()->fromGlobals()->withAttribute('isSetupPage', $isSetupPage);
            },
            function (Throwable $throwable): ResponseInterface {
                $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
                $response->getBody()->write(sprintf('An error occurred: %s', $throwable->getMessage()));

                return $response;
            },
        );

        $runner->run();
    }

    public function handle(ServerRequest $request): Response|null
    {
        $route = $request->getRoute();

        $container = Core::getContainerBuilder();

        $settings = $this->config->getSettings();

        /** @var ThemeManager $themeManager */
        $themeManager = $container->get(ThemeManager::class);

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
            $authPlugin->checkTwoFactor($request);

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
    public function checkRequiredPhpExtensions(): void
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

    public function setDatabaseAndTableFromRequest(ContainerInterface $container, ServerRequest $request): void
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

    private function getGenericErrorResponse(string $message): Response
    {
        $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);

        return $response->write($this->template->render('error/generic', [
            'lang' => $GLOBALS['lang'] ?? 'en',
            'dir' => $GLOBALS['text_dir'] ?? 'ltr',
            'error_message' => $message,
        ]));
    }
}
