<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use PhpMyAdmin\Container\ContainerBuilder;
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
use PhpMyAdmin\Http\Middleware\RecentTableHandling;
use PhpMyAdmin\Http\Middleware\RequestProblemChecking;
use PhpMyAdmin\Http\Middleware\ResponseRendererLoading;
use PhpMyAdmin\Http\Middleware\RouteParsing;
use PhpMyAdmin\Http\Middleware\ServerConfigurationChecking;
use PhpMyAdmin\Http\Middleware\SessionHandling;
use PhpMyAdmin\Http\Middleware\SetupPageRedirection;
use PhpMyAdmin\Http\Middleware\SqlDelimiterSetting;
use PhpMyAdmin\Http\Middleware\SqlQueryGlobalSetting;
use PhpMyAdmin\Http\Middleware\StatementHistory;
use PhpMyAdmin\Http\Middleware\ThemeInitialization;
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

readonly class Application
{
    public function __construct(private ResponseFactory $responseFactory)
    {
    }

    public static function init(): self
    {
        return ContainerBuilder::getContainer()->get(self::class);
    }

    public function run(bool $isSetupPage = false): void
    {
        $container = ContainerBuilder::getContainer();
        $requestHandler = new QueueRequestHandler($container, new ApplicationHandler($this));
        $requestHandler->add(ErrorHandling::class);
        $requestHandler->add(OutputBuffering::class);
        $requestHandler->add(PhpExtensionsChecking::class);
        $requestHandler->add(ServerConfigurationChecking::class);
        $requestHandler->add(PhpSettingsConfiguration::class);
        $requestHandler->add(RouteParsing::class);
        $requestHandler->add(ConfigLoading::class);
        $requestHandler->add(UriSchemeUpdating::class);
        $requestHandler->add(SessionHandling::class);
        $requestHandler->add(EncryptedQueryParamsHandling::class);
        $requestHandler->add(UrlParamsSetting::class);
        $requestHandler->add(TokenRequestParamChecking::class);
        $requestHandler->add(DatabaseAndTableSetting::class);
        $requestHandler->add(SqlQueryGlobalSetting::class);
        $requestHandler->add(LanguageLoading::class);
        $requestHandler->add(ConfigErrorAndPermissionChecking::class);
        $requestHandler->add(RequestProblemChecking::class);
        $requestHandler->add(CurrentServerGlobalSetting::class);
        $requestHandler->add(ThemeInitialization::class);
        $requestHandler->add(UrlRedirection::class);
        $requestHandler->add(SetupPageRedirection::class);
        $requestHandler->add(MinimumCommonRedirection::class);
        $requestHandler->add(LanguageAndThemeCookieSaving::class);
        $requestHandler->add(LoginCookieValiditySetting::class);
        $requestHandler->add(Authentication::class);
        $requestHandler->add(DatabaseServerVersionChecking::class);
        $requestHandler->add(SqlDelimiterSetting::class);
        $requestHandler->add(ResponseRendererLoading::class);
        $requestHandler->add(ProfilingChecking::class);
        $requestHandler->add(UserPreferencesLoading::class);
        $requestHandler->add(RecentTableHandling::class);
        $requestHandler->add(StatementHistory::class);

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

    public function handle(ServerRequest $request): Response
    {
        return Routing::callControllerForRoute(
            $request,
            Routing::getDispatcher(),
            ContainerBuilder::getContainer(),
            $this->responseFactory,
        );
    }
}
