<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\ServerRequest;

final class RecentTablesListController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        if (! $request->isAjax()) {
            return;
        }

        $this->response->addJSON(['list' => RecentFavoriteTables::getInstance(TableType::Recent)->getHtmlList()]);
    }
}
