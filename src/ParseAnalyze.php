<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\SqlParser\Utils\StatementInfo;

use function count;
use function strcasecmp;

/**
 * Parse and analyse a SQL query
 */
class ParseAnalyze
{
    /**
     * Calls the parser on a query
     *
     * @param string $sqlQuery the query to parse
     * @param string $db       the current database
     *
     * @return array{StatementInfo, string, string, bool}
     */
    public static function sqlQuery(string $sqlQuery, string $db): array
    {
        $info = Query::getAll($sqlQuery);

        $table = '';

        $reload = $info->statement instanceof AlterStatement
            || $info->statement instanceof CreateStatement
            || $info->statement instanceof DropStatement;

        // If the targeted table (and database) are different than the ones that is
        // currently browsed, edit `$db` and `$table` to match them so other elements
        // (page headers, links, navigation panel) can be updated properly.
        if ($info->selectTables !== []) {
            // Previous table and database name is stored to check if it changed.
            $previousDb = $db;

            if (count($info->selectTables) > 1) {
                /**
                 * @todo if there are more than one table name in the Select:
                 * - do not extract the first table name
                 * - do not show a table name in the page header
                 * - do not display the sub-pages links)
                 */
                $table = '';
            } else {
                $table = $info->selectTables[0][0] ?? '';
                if (isset($info->selectTables[0][1])) {
                    $db = $info->selectTables[0][1];
                }
            }

            // There is no point checking if a reloading is required if we already decided
            // to reload. Also, no reload is required for AJAX requests.
            $response = ResponseRenderer::getInstance();
            if (! $reload && ! $response->isAjax()) {
                // NOTE: Database names are case-insensitive.
                $reload = strcasecmp($db, $previousDb) !== 0;
            }
        }

        return [$info, $db, $table, $reload];
    }
}
