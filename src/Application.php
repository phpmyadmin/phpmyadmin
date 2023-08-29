<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Handler\ApplicationHandler;
use PhpMyAdmin\Http\Handler\QueueRequestHandler;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Middleware\Authentication;
use PhpMyAdmin\Middleware\ConfigErrorAndPermissionChecking;
use PhpMyAdmin\Middleware\ConfigLoading;
use PhpMyAdmin\Middleware\CurrentServerGlobalSetting;
use PhpMyAdmin\Middleware\DatabaseAndTableSetting;
use PhpMyAdmin\Middleware\DatabaseServerVersionChecking;
use PhpMyAdmin\Middleware\EncryptedQueryParamsHandling;
use PhpMyAdmin\Middleware\ErrorHandling;
use PhpMyAdmin\Middleware\LanguageAndThemeCookieSaving;
use PhpMyAdmin\Middleware\LanguageLoading;
use PhpMyAdmin\Middleware\LoginCookieValiditySetting;
use PhpMyAdmin\Middleware\MinimumCommonRedirection;
use PhpMyAdmin\Middleware\OutputBuffering;
use PhpMyAdmin\Middleware\PhpExtensionsChecking;
use PhpMyAdmin\Middleware\PhpSettingsConfiguration;
use PhpMyAdmin\Middleware\ProfilingChecking;
use PhpMyAdmin\Middleware\RequestProblemChecking;
use PhpMyAdmin\Middleware\ResponseRendererLoading;
use PhpMyAdmin\Middleware\RouteParsing;
use PhpMyAdmin\Middleware\ServerConfigurationChecking;
use PhpMyAdmin\Middleware\SessionHandling;
use PhpMyAdmin\Middleware\SetupPageRedirection;
use PhpMyAdmin\Middleware\SqlDelimiterSetting;
use PhpMyAdmin\Middleware\SqlQueryGlobalSetting;
use PhpMyAdmin\Middleware\ThemeInitialization;
use PhpMyAdmin\Middleware\TokenMismatchChecking;
use PhpMyAdmin\Middleware\TokenRequestParamChecking;
use PhpMyAdmin\Middleware\TrackerEnabling;
use PhpMyAdmin\Middleware\UriSchemeUpdating;
use PhpMyAdmin\Middleware\UrlParamsSetting;
use PhpMyAdmin\Middleware\UrlRedirection;
use PhpMyAdmin\Middleware\UserPreferencesLoading;
use PhpMyAdmin\Middleware\ZeroConfPostConnection;
use PhpMyAdmin\Routing\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function sprintf;

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
        $requestHandler = new QueueRequestHandler(new ApplicationHandler($this));
        $requestHandler->add(new ErrorHandling($this->errorHandler));
        $requestHandler->add(new OutputBuffering());
        $requestHandler->add(new PhpExtensionsChecking($this->template, $this->responseFactory));
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
        $requestHandler->add(new TokenRequestParamChecking());
        $requestHandler->add(new DatabaseAndTableSetting());
        $requestHandler->add(new SqlQueryGlobalSetting());
        $requestHandler->add(new LanguageLoading());
        $requestHandler->add(new ConfigErrorAndPermissionChecking(
            $this->config,
            $this->template,
            $this->responseFactory,
        ));
        $requestHandler->add(new RequestProblemChecking($this->template, $this->responseFactory));
        $requestHandler->add(new CurrentServerGlobalSetting($this->config));
        $requestHandler->add(new ThemeInitialization());
        $requestHandler->add(new UrlRedirection($this->config));
        $requestHandler->add(new SetupPageRedirection($this->config, $this->responseFactory));
        $requestHandler->add(new MinimumCommonRedirection($this->config, $this->responseFactory));
        $requestHandler->add(new LanguageAndThemeCookieSaving($this->config));
        $requestHandler->add(new LoginCookieValiditySetting($this->config));
        $requestHandler->add(new Authentication($this->config, $this->template, $this->responseFactory));
        $requestHandler->add(new DatabaseServerVersionChecking($this->config, $this->template, $this->responseFactory));
        $requestHandler->add(new SqlDelimiterSetting($this->config));
        $requestHandler->add(new ResponseRendererLoading($this->config));
        $requestHandler->add(new TokenMismatchChecking());
        $requestHandler->add(new ProfilingChecking());
        $requestHandler->add(new UserPreferencesLoading($this->config));
        $requestHandler->add(new TrackerEnabling());
        $requestHandler->add(new ZeroConfPostConnection($this->config));

        $runner = new RequestHandlerRunner(
            $requestHandler,
            new SapiEmitter(),
            static fn (): ServerRequestInterface => ServerRequestFactory::create()
                ->fromGlobals()
                ->withAttribute('isSetupPage', $isSetupPage),
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
        return Routing::callControllerForRoute(
            $request,
            Routing::getDispatcher(),
            Core::getContainerBuilder(),
            $this->responseFactory,
        );
    }
}
