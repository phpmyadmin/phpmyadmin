<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class RecentTableHandling implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        if ($this->config->settings['NumRecentTables'] === 0) {
            return $handler->handle($request);
        }

        $response = $handler->handle($request);

        $db = DatabaseName::tryFrom($request->getParam('db'));
        $table = TableName::tryFrom($request->getParam('table'));
        if ($db !== null && $table !== null) {
            $recentTable = new RecentFavoriteTable($db, $table);
            $isAddedOrError = RecentFavoriteTables::getInstance(TableType::Recent)->add($recentTable);
            if ($isAddedOrError instanceof Message) {
                $response->getBody()->write($isAddedOrError->getMessage());
            }
        }

        return $response;
    }
}
