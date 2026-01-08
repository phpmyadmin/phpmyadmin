<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final readonly class MinimumCommonRedirection implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactory $responseFactory,
        private UserPreferencesHandler $userPreferencesHandler,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->isMinimumCommon($request->getAttribute('route'))) {
            return $handler->handle($request);
        }

        $container = ContainerBuilder::getContainer();
        $this->userPreferencesHandler->loadUserPreferences(true);
        assert($request instanceof ServerRequest);

        try {
            return Routing::callControllerForRoute(
                $request,
                Routing::getDispatcher(),
                $container,
                $this->responseFactory,
            );
        } catch (ExitException) {
            return ResponseRenderer::getInstance()->response();
        }
    }

    private function isMinimumCommon(mixed $route): bool
    {
        return $route === '/import-status' || $route === '/messages';
    }
}
