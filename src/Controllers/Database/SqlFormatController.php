<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Utils\Formatter;

/**
 * Format SQL for SQL editors.
 */
final class SqlFormatController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        /** @var string $query */
        $query = $request->getParsedBodyParam('sql', '');
        if ($request->getParsedBodyParam('formatSingleLine') === 'true') {
            $this->response->addJSON(['sql' => Formatter::format($query, ['line_ending' => ' ', 'indentation' => ''])]);
        } else {
            $this->response->addJSON(['sql' => Formatter::format($query)]);
        }

        return $this->response->response();
    }
}
