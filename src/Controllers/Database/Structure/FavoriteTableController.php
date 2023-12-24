<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function json_decode;
use function json_encode;
use function md5;
use function sha1;

final class FavoriteTableController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;

        if (Current::$database === '') {
            return;
        }

        $config = Config::getInstance();
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

        if (! $request->isAjax()) {
            return;
        }

        $favoriteInstance = RecentFavoriteTables::getInstance(TableType::Favorite);

        $favoriteTables = $request->getParam('favoriteTables');
        $favoriteTables = $favoriteTables !== null ? json_decode($favoriteTables, true) : [];

        // Required to keep each user's preferences separate.
        $user = sha1($config->selectedServer['user']);

        // Request for Synchronization of favorite tables.
        if ($request->getParam('sync_favorite_tables') !== null) {
            $relationParameters = $this->relation->getRelationParameters();
            if ($relationParameters->favoriteTablesFeature !== null) {
                $this->response->addJSON($this->synchronizeFavoriteTables(
                    $favoriteInstance,
                    $user,
                    $favoriteTables,
                ));
            }

            return;
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No databases selected.')));

            return;
        }

        $changes = true;
        /** @var string $favoriteTableName */
        $favoriteTableName = $request->getParam('favorite_table', '');
        $favoriteTable = new RecentFavoriteTable($databaseName, TableName::from($favoriteTableName));
        $alreadyFavorite = $favoriteInstance->contains($favoriteTable);

        if (isset($_REQUEST['remove_favorite'])) {
            if ($alreadyFavorite) {
                // If already in favorite list, remove it.
                $favoriteInstance->remove($favoriteTable);
                $alreadyFavorite = false; // for favorite_anchor template
            }
        } elseif (isset($_REQUEST['add_favorite'])) {
            if (! $alreadyFavorite) {
                $numTables = count($favoriteInstance->getTables());
                if ($numTables == $config->settings['NumFavoriteTables']) {
                    $changes = false;
                } else {
                    // Otherwise add to favorite list.
                    $favoriteInstance->add($favoriteTable);
                    $alreadyFavorite = true; // for favorite_anchor template
                }
            }
        }

        $favoriteTables[$user] = $favoriteInstance->getTables();

        $json = [];
        $json['changes'] = $changes;
        if (! $changes) {
            $json['message'] = $this->template->render('components/error_message', [
                'msg' => __('Favorite List is full!'),
            ]);
            $this->response->addJSON($json);

            return;
        }

        // Check if current table is already in favorite list.
        $favoriteParams = [
            'db' => Current::$database,
            'ajax_request' => true,
            'favorite_table' => $favoriteTableName,
            ($alreadyFavorite ? 'remove' : 'add') . '_favorite' => true,
        ];

        $json['user'] = $user;
        $json['favoriteTables'] = json_encode($favoriteTables);
        $json['list'] = $favoriteInstance->getHtmlList();
        $json['anchor'] = $this->template->render('database/structure/favorite_anchor', [
            'table_name_hash' => md5($favoriteTableName),
            'db_table_name_hash' => md5(Current::$database . '.' . $favoriteTableName),
            'fav_params' => $favoriteParams,
            'already_favorite' => $alreadyFavorite,
        ]);

        $this->response->addJSON($json);
    }

    /**
     * Synchronize favorite tables
     *
     * @param RecentFavoriteTables $favoriteInstance Instance of this class
     * @param string               $user             The user hash
     * @param mixed[]              $favoriteTables   Existing favorites
     *
     * @return mixed[]
     */
    private function synchronizeFavoriteTables(
        RecentFavoriteTables $favoriteInstance,
        string $user,
        array $favoriteTables,
    ): array {
        if ($favoriteInstance->getTables() === [] && isset($favoriteTables[$user])) {
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

        return ['favoriteTables' => json_encode($favoriteTables), 'list' => $favoriteInstance->getHtmlList()];
    }
}
