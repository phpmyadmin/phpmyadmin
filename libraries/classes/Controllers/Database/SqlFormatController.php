<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\SqlParser\Utils\Formatter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function strlen;

/**
 * Format SQL for SQL editors.
 */
class SqlFormatController extends AbstractController
{
    public function index(Request $request, Response $response): Response
    {
        $params = ['sql' => $_POST['sql'] ?? null];
        $query = strlen((string) $params['sql']) > 0 ? $params['sql'] : '';
        $this->response->addJSON(['sql' => Formatter::format($query)]);

        return $response;
    }
}
