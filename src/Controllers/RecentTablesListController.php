<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\RecentFavoriteTable;

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
