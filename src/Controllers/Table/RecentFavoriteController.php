<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidIdentifier;
use PhpMyAdmin\Identifiers\TableName;

use function __;

/**
 * Browse recent and favorite tables chosen from navigation.
 */
final class RecentFavoriteController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        try {
            $db = DatabaseName::from($request->getParam('db'));
            $table = TableName::from($request->getParam('table'));
        } catch (InvalidIdentifier) {
            $this->redirect('/', ['message' => __('Invalid database or table name.')]);

            return;
        }

        $favoriteTable = new RecentFavoriteTable($db, $table);
        RecentFavoriteTables::getInstance(TableType::Recent)->removeIfInvalid($favoriteTable);
        RecentFavoriteTables::getInstance(TableType::Favorite)->removeIfInvalid($favoriteTable);

        $this->redirect('/sql', ['db' => $db->getName(), 'table' => $table->getName()]);
    }
}
