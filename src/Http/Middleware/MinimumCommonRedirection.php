<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Routing;
use PhpMyAdmin\Theme\ThemeManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class MinimumCommonRedirection implements MiddlewareInterface
{
    public function __construct(private readonly Config $config, private readonly ResponseFactory $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->isMinimumCommon($request->getAttribute('route'))) {
            return $handler->handle($request);
        }

        $container = ContainerBuilder::getContainer();
        /** @var ThemeManager $themeManager */
        $themeManager = $container->get(ThemeManager::class);
        $this->config->loadUserPreferences($themeManager, true);
        assert($request instanceof ServerRequest);

        try {
            $response = Routing::callControllerForRoute(
                $request,
                Routing::getDispatcher(),
                $container,
                $this->responseFactory,
            );
            if ($response === null) {
                throw new ExitException();
            }
        } catch (ExitException) {
            $response = ResponseRenderer::getInstance()->response();
        }

        return $response;
    }

    private function isMinimumCommon(mixed $route): bool
    {
        return $route === '/import-status' || $route === '/messages';
    }
}
