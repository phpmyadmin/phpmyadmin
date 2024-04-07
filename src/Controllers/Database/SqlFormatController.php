<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Utils\Formatter;
use PhpMyAdmin\Template;

/**
 * Format SQL for SQL editors.
 */
final class SqlFormatController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Template $template)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        /** @var string $query */
        $query = $request->getParsedBodyParam('sql', '');
        $this->response->addJSON(['sql' => Formatter::format($query)]);

        return null;
    }
}
