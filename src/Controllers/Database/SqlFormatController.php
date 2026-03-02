<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlParser\Utils\Formatter;

/**
 * Format SQL for SQL editors.
 */
#[Route('/database/sql/format', ['POST'])]
final readonly class SqlFormatController implements InvocableController
{
    public function __construct(private ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $query = $request->getParsedBodyParamAsString('sql', '');
        if ($request->getParsedBodyParamAsString('formatSingleLine') === 'true') {
            $this->response->addJSON([
                'sql' => Formatter::format($query, ['type' => 'text', 'line_ending' => ' ', 'indentation' => '']),
            ]);
        } else {
            $this->response->addJSON(['sql' => Formatter::format($query, ['type' => 'text'])]);
        }

        return $this->response->response();
    }
}
