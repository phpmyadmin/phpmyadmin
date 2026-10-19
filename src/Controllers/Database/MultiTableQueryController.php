<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;

/**
 * Handles database multi-table querying
 */
#[Route('/database/multi-table-query', ['GET'])]
final readonly class MultiTableQueryController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null) {
            return $this->response->missingParameterError('db');
        }

        $this->response->addScriptFiles(['database/multi_table_query.js', 'database/query_generator.js']);

        $multiTableQuery = new MultiTableQuery($this->dbi);

        $this->response->addHTML($this->template->render('database/multi_table_query/form', [
            'db' => $databaseName->getName(),
            'tables' => $multiTableQuery->getColumnsInTables($databaseName->getName()),
            'default_no_of_columns' => 3,
        ]));

        return $this->response->response();
    }
}
