<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Routing\Routing;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class RouteParsing implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $route = $request->getRoute();
        /** @psalm-suppress DeprecatedProperty */
        Routing::$route = $route;

        return $handler->handle($request->withAttribute('route', $route));
    }
}
