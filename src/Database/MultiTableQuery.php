<?php
/**
 * Handles DB Multi-table query
 */

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Dbal\DatabaseInterface;

use function md5;
use function sprintf;

/**
 * Class to handle database Multi-table querying
 */
class MultiTableQuery
{
    public function __construct(private readonly DatabaseInterface $dbi)
    {
    }

    /** @return array<array{hash: string, columns: string[]}> */
    public function getColumnsInTables(string $db): array
    {
        $columnsInTables = $this->dbi->query(sprintf(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.columns WHERE table_schema = %s',
            $this->dbi->quoteString($db),
        ));

        $tables = [];
        /** @var array{TABLE_NAME:string, COLUMN_NAME:string} $column */
        foreach ($columnsInTables as $column) {
            $table = $column['TABLE_NAME'];
            $tables[$table]['hash'] ??= md5($table);
            $tables[$table]['columns'][] = $column['COLUMN_NAME'];
        }

        return $tables;
    }
}
