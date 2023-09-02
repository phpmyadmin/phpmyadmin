<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function assert;
use function is_array;

final class DatabaseAndTableSetting implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $container = Core::getContainerBuilder();
        $this->setDatabaseAndTableFromRequest($container, $request);

        return $handler->handle($request);
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
}
