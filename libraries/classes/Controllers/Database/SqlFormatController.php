<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\SqlParser\Utils\Formatter;

/**
 * Format SQL for SQL editors.
 */
class SqlFormatController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        /** @var string $query */
        $query = $request->getParsedBodyParam('sql', '');
        $this->response->addJSON(['sql' => Formatter::format($query)]);
    }
}
