<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\UrlParams;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class DatabaseAndTableSetting implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $this->setDatabaseAndTableFromRequest($request);

        return $handler->handle($request);
    }

    private function setDatabaseAndTableFromRequest(ServerRequest $request): void
    {
        $db = DatabaseName::tryFrom($request->getParam('db'));
        $table = TableName::tryFrom($request->getParam('table'));

        Current::$database = $db?->getName() ?? '';
        Current::$table = $table?->getName() ?? '';

        UrlParams::$params['db'] = Current::$database;
        UrlParams::$params['table'] = Current::$table;
    }
}
