<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\SqlParser\Utils\Formatter;

use function strlen;

/**
 * Format SQL for SQL editors.
 */
class SqlFormatController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $params = ['sql' => $request->getParsedBodyParam('sql')];
        $query = strlen((string) $params['sql']) > 0 ? $params['sql'] : '';
        $this->response->addJSON(['sql' => Formatter::format($query)]);
    }
}
