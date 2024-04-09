<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

final class RecentTablesListController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        if (! $request->isAjax()) {
            return null;
        }

        $this->response->addJSON(['list' => RecentFavoriteTables::getInstance(TableType::Recent)->getHtmlList()]);

        return null;
    }
}
