<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Http\ServerRequest;

final class QueryController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $params = [
            'sql_query' => $_POST['sql_query'],
            'db' => $_POST['db'] ?? $_GET['db'] ?? null,
        ];

        $this->response->addHTML(MultiTableQuery::displayResults($params['sql_query'], $params['db']));
    }
}
