<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Handler\ApplicationHandler;
use PhpMyAdmin\Http\Handler\QueueRequestHandler;
use PhpMyAdmin\Http\Middleware\Authentication;
use PhpMyAdmin\Http\Middleware\ConfigErrorAndPermissionChecking;
use PhpMyAdmin\Http\Middleware\ConfigLoading;
use PhpMyAdmin\Http\Middleware\CurrentServerGlobalSetting;
use PhpMyAdmin\Http\Middleware\DatabaseAndTableSetting;
use PhpMyAdmin\Http\Middleware\DatabaseServerVersionChecking;
use PhpMyAdmin\Http\Middleware\EncryptedQueryParamsHandling;
use PhpMyAdmin\Http\Middleware\ErrorHandling;
use PhpMyAdmin\Http\Middleware\LanguageAndThemeCookieSaving;
use PhpMyAdmin\Http\Middleware\LanguageLoading;
use PhpMyAdmin\Http\Middleware\LoginCookieValiditySetting;
use PhpMyAdmin\Http\Middleware\MinimumCommonRedirection;
use PhpMyAdmin\Http\Middleware\OutputBuffering;
use PhpMyAdmin\Http\Middleware\PhpExtensionsChecking;
use PhpMyAdmin\Http\Middleware\PhpSettingsConfiguration;
use PhpMyAdmin\Http\Middleware\ProfilingChecking;
use PhpMyAdmin\Http\Middleware\RequestProblemChecking;
use PhpMyAdmin\Http\Middleware\ResponseRendererLoading;
use PhpMyAdmin\Http\Middleware\RouteParsing;
use PhpMyAdmin\Http\Middleware\ServerConfigurationChecking;
use PhpMyAdmin\Http\Middleware\SessionHandling;
use PhpMyAdmin\Http\Middleware\SetupPageRedirection;
use PhpMyAdmin\Http\Middleware\SqlDelimiterSetting;
use PhpMyAdmin\Http\Middleware\SqlQueryGlobalSetting;
use PhpMyAdmin\Http\Middleware\ThemeInitialization;
use PhpMyAdmin\Http\Middleware\TokenMismatchChecking;
use PhpMyAdmin\Http\Middleware\TokenRequestParamChecking;
use PhpMyAdmin\Http\Middleware\UriSchemeUpdating;
use PhpMyAdmin\Http\Middleware\UrlParamsSetting;
use PhpMyAdmin\Http\Middleware\UrlRedirection;
use PhpMyAdmin\Http\Middleware\UserPreferencesLoading;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
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
        $application = ContainerBuilder::getContainer()->get(self::class);

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
            ContainerBuilder::getContainer(),
            $this->responseFactory,
        );
    }
}
