<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;

final class QueryController extends AbstractController
{
    public function __invoke(ServerRequest $request): Response|null
    {
        $this->response->addHTML(MultiTableQuery::displayResults(
            $request->getParsedBodyParam('sql_query'),
            $request->getParam('db'),
        ));

        return null;
    }
}
