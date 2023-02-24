<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\CentralColumns;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\CentralColumns;
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

    public function __invoke(ServerRequest $request): void
    {
        $columns = $this->centralColumns->getColumnsNotInCentralList(
            $GLOBALS['db'],
            $request->getParsedBodyParam('selectedTable'),
        );
        $this->render('database/central_columns/populate_columns', ['columns' => $columns]);
    }
}
