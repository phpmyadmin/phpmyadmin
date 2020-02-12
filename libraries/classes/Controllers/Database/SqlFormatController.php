<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\SqlParser\Utils\Formatter;
use function strlen;

/**
 * Format SQL for SQL editors.
 */
class SqlFormatController extends AbstractController
{
    /**
     * @param array $params Request parameters
     */
    public function index(array $params): void
    {
        $query = strlen((string) $params['sql']) > 0 ? $params['sql'] : '';
        $this->response->addJSON(['sql' => Formatter::format($query)]);
    }
}
