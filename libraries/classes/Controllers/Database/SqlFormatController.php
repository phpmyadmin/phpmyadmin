<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\SqlParser\Utils\Formatter;

/**
 * Format SQL for SQL editors.
 */
class SqlFormatController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return array
     */
    public function index(array $params): array
    {
        $query = strlen((string) $params['sql']) > 0 ? $params['sql'] : '';
        return ['sql' => Formatter::format($query)];
    }
}
