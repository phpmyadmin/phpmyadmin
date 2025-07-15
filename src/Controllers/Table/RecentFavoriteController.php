<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;

/**
 * Browse recent and favorite tables chosen from navigation.
 */
#[Route('/table/recent-favorite', ['GET', 'POST'])]
final class RecentFavoriteController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        try {
            $db = DatabaseName::from($request->getParam('db'));
            $table = TableName::from($request->getParam('table'));
        } catch (InvalidIdentifier) {
            return $this->response->redirectToRoute('/', ['message' => __('Invalid database or table name.')]);
        }

        $favoriteTable = new RecentFavoriteTable($db, $table);
        RecentFavoriteTables::getInstance(TableType::Recent)->removeIfInvalid($favoriteTable);
        RecentFavoriteTables::getInstance(TableType::Favorite)->removeIfInvalid($favoriteTable);

        return $this->response->redirectToRoute('/sql', ['db' => $db->getName(), 'table' => $table->getName()]);
    }
}
