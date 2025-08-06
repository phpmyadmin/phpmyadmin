<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function hash;
use function is_array;
use function json_decode;
use function json_encode;

#[Route('/sync-favorite-tables', ['POST'])]
final readonly class SyncFavoriteTablesController implements InvocableController
{
    public function __construct(private ResponseRenderer $response, private Relation $relation, private Config $config)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $favoriteInstance = RecentFavoriteTables::getInstance(TableType::Favorite);

        $favoriteTables = json_decode($request->getParsedBodyParamAsString('favoriteTables'), true);
        if (! is_array($favoriteTables)) {
            $favoriteTables = [];
        }

        // Required to keep each user's preferences separate.
        $user = hash('sha1', $this->config->selectedServer['user']);

        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->favoriteTablesFeature !== null) {
            if (
                $favoriteInstance->getTables() === []
                && isset($favoriteTables[$user])
                && is_array($favoriteTables[$user])
            ) {
                foreach ($favoriteTables[$user] as $value) {
                    $favoriteInstance->add(new RecentFavoriteTable(
                        DatabaseName::from($value['db']),
                        TableName::from($value['table']),
                    ));
                }
            }

            $favoriteTables[$user] = $favoriteInstance->getTables();

            // Set flag when localStorage and pmadb(if present) are in sync.
            $_SESSION['tmpval']['favorites_synced'][Current::$server] = true;

            $this->response->addJSON([
                'favoriteTables' => json_encode($favoriteTables),
                'list' => $favoriteInstance->getHtmlList(),
            ]);
        }

        return $this->response->response();
    }
}
