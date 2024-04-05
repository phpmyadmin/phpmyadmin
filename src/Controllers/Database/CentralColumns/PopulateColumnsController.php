<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\CentralColumns;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class PopulateColumnsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private CentralColumns $centralColumns,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $columns = $this->centralColumns->getColumnsNotInCentralList(
            Current::$database,
            $request->getParsedBodyParam('selectedTable'),
        );
        $this->response->render('database/central_columns/populate_columns', ['columns' => $columns]);

        return null;
    }
}
