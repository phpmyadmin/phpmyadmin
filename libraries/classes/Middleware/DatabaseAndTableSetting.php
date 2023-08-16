<?php

declare(strict_types=1);

namespace PhpMyAdmin\Middleware;

use PhpMyAdmin\Application;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class DatabaseAndTableSetting implements MiddlewareInterface
{
    public function __construct(private readonly Application $application)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $container = Core::getContainerBuilder();
        $this->application->setDatabaseAndTableFromRequest($container, $request);

        return $handler->handle($request);
    }
}
