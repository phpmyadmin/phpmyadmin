<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Http\ServerRequest;

final class RecentTablesListController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        if (! $request->isAjax()) {
            return;
        }

        $this->response->addJSON(['list' => RecentFavoriteTable::getInstance('recent')->getHtmlList()]);
    }
}
