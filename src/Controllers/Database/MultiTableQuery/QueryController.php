<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/database/multi-table-query/query', ['POST'])]
final class QueryController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addHTML(MultiTableQuery::displayResults(
            $request->getParsedBodyParamAsString('sql_query'),
            $request->getParam('db'),
        ));

        return $this->response->response();
    }
}
