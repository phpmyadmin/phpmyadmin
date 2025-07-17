<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\CentralColumns;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/database/central-columns/populate', ['POST'])]
final class PopulateColumnsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly CentralColumns $centralColumns,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $columns = $this->centralColumns->getColumnsNotInCentralList(
            Current::$database,
            $request->getParsedBodyParamAsString('selectedTable'),
        );
        $this->response->render('database/central_columns/populate_columns', ['columns' => $columns]);

        return $this->response->response();
    }
}
