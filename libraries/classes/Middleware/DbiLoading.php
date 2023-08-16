<?php

declare(strict_types=1);

namespace PhpMyAdmin\Middleware;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DbiLoading implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['dbi'] = DatabaseInterface::load();
        $container = Core::getContainerBuilder();
        $container->set(DatabaseInterface::class, $GLOBALS['dbi']);
        $container->setAlias('dbi', DatabaseInterface::class);

        return $handler->handle($request);
    }
}
