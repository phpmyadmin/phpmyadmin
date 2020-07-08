<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\RecentFavoriteTable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Browse recent and favorite tables chosen from navigation.
 */
class RecentFavoriteController extends AbstractController
{
    public function index(Request $request, Response $response): Response
    {
        global $containerBuilder;

        RecentFavoriteTable::getInstance('recent')->removeIfInvalid(
            $_REQUEST['db'],
            $_REQUEST['table']
        );

        RecentFavoriteTable::getInstance('favorite')->removeIfInvalid(
            $_REQUEST['db'],
            $_REQUEST['table']
        );

        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $controller->index($request, $response);

        return $response;
    }
}
