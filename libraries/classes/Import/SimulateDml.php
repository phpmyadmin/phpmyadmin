<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_reverse;
use function implode;

final class SimulateDml
{
    public function __construct(private DatabaseInterface $dbi)
    {
    }

    public function getError(): string
    {
        return $this->dbi->getError();
    }

    /**
     * Find the matching rows for UPDATE/DELETE query.
     *
     * @return array<string, int|string>
     * @psalm-return array{
     *   sql_query: string,
     *   matched_rows: (int|numeric-string),
     *   matched_rows_url: string
     * }
     */
    public function getMatchedRows(
        string $query,
        Parser $parser,
        DeleteStatement|UpdateStatement|Statement $statement,
    ): array {
        $matchedRowQuery = '';
        if ($statement instanceof DeleteStatement) {
            $matchedRowQuery = $this->getSimulatedDeleteQuery($parser, $statement);
        } elseif ($statement instanceof UpdateStatement) {
            $matchedRowQuery = $this->getSimulatedUpdateQuery($parser, $statement);
        }

        // Execute the query and get the number of matched rows.
        $matchedRows = $this->executeMatchedRowQuery($matchedRowQuery);
        $matchedRowsUrl = Url::getFromRoute('/sql', [
            'db' => $GLOBALS['db'],
            'sql_query' => $matchedRowQuery,
            'sql_signature' => Core::signSqlQuery($matchedRowQuery),
        ]);

        return [
            'sql_query' => Html\Generator::formatSql($query),
            'matched_rows' => $matchedRows,
            'matched_rows_url' => $matchedRowsUrl,
        ];
    }

    /**
     * Executes the matched_row_query and returns the resultant row count.
     *
     * @param string $matchedRowQuery SQL query
     */
    private function executeMatchedRowQuery(string $matchedRowQuery): int
    {
        $this->dbi->selectDb($GLOBALS['db']);

        // Execute the query.
        return (int) $this->dbi->fetchValue($matchedRowQuery);
    }

    /**
     * Transforms a DELETE query into SELECT statement.
     *
     * @return string SQL query
     */
    private function getSimulatedDeleteQuery(Parser $parser, DeleteStatement $statement): string
    {
        $tableReferences = Query::getTables($statement);
        Assert::count($tableReferences, 1, 'No joins allowed in simulation query');
        Assert::notNull($parser->list, 'Parser list not set');

        $condition = Query::getClause($statement, $parser->list, 'WHERE');
        $where = $condition === '' ? '' : ' WHERE ' . $condition;
        $order = $statement->order === null || $statement->order === []
            ? ''
            : ' ORDER BY ' . Query::getClause($statement, $parser->list, 'ORDER BY');
        $limit = $statement->limit === null ? '' : ' LIMIT ' . Query::getClause($statement, $parser->list, 'LIMIT');

        return 'SELECT COUNT(*) FROM (' .
            'SELECT 1 FROM ' . $tableReferences[0] . $where . $order . $limit . ') AS `pma_tmp`';
    }

    /**
     * Transforms a UPDATE query into SELECT statement.
     *
     * @return string SQL query
     */
    private function getSimulatedUpdateQuery(Parser $parser, UpdateStatement $statement): string
    {
        $tableReferences = Query::getTables($statement);
        Assert::count($tableReferences, 1, 'No joins allowed in simulation query');
        Assert::isNonEmptyList($statement->set, 'SET statements missing');
        Assert::notNull($parser->list, 'Parser list not set');
        $newValues = [];
        $oldValues = [];
        $newColumns = [];
        $oldColumns = [];
        $i = 0;
        $handledColumns = [];
        foreach (array_reverse($statement->set) as $set) {
            if (array_key_exists($set->column, $handledColumns)) {
                continue;
            }

            $handledColumns[$set->column] = true;
            $oldValues[] = $set->column . ' AS ' . ($oldColumns[] = Util::backquote('o' . $i));
            $newValues[] = $set->value . ' AS ' . ($newColumns[] = Util::backquote('n' . $i));
            ++$i;
        }

        $condition = Query::getClause($statement, $parser->list, 'WHERE');
        $where = $condition === '' ? '' : ' WHERE ' . $condition;
        $order = $statement->order === null || $statement->order === []
            ? ''
            : ' ORDER BY ' . Query::getClause($statement, $parser->list, 'ORDER BY');
        $limit = $statement->limit === null ? '' : ' LIMIT ' . Query::getClause($statement, $parser->list, 'LIMIT');

        return 'SELECT COUNT(*)' .
            ' FROM (SELECT ' . implode(',', $newValues) . ') AS `pma_new`' .
            ' JOIN (' .
            'SELECT ' . implode(', ', $oldValues) . ' FROM ' . $tableReferences[0] . $where . $order . $limit .
            ') AS `pma_old`' .
            ' WHERE NOT (' . implode(', ', $newColumns) . ') <=> (' . implode(', ', $oldColumns) . ')';
    }
}
