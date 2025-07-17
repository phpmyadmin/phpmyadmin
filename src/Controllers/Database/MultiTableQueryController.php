<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;

/**
 * Handles database multi-table querying
 */
#[Route('/database/multi-table-query', ['GET'])]
final class MultiTableQueryController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['database/multi_table_query.js', 'database/query_generator.js']);

        $queryInstance = new MultiTableQuery($this->dbi, $this->template, Current::$database);

        $this->response->addHTML($queryInstance->getFormHtml());

        return $this->response->response();
    }
}
