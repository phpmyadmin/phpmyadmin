<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\MultiTableQuery;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Database\MultiTableQuery;
use PhpMyAdmin\Database\QueryValidator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

use function is_string;

final class QueryController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly QueryValidator|null $validator = null,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        // Ensure 'sql_query' parameter is always a string
        $query = $request->getParsedBodyParamAsString('sql_query');
        $db = $request->getParam('db');
        $table = $request->getParam('table');

        if (! is_string($db)) {
            $this->response->addJSON('error', 'Invalid query or database name.');

            return $this->response->response();
        }

        $context = ['db' => $db];

        // Revised: Only add 'table' to $context if it is a string
        if (is_string($table)) {
            $context['table'] = $table;
        }

        if ($this->validator !== null) {
            $validationResult =
                $this->validator->validateQuery($query, $table !== null && is_string($table) ? $table : null, $context);

            if (! $validationResult['isValid']) {
                $this->response->addJSON('error', $validationResult['error']);

                return $this->response->response();
            }

            $this->response->addHTML(
                MultiTableQuery::displayResults(
                    $validationResult['query'],
                    $db,
                ),
            );
        }

        return $this->response->response();
    }
}
