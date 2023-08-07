<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;

use function in_array;
use function sprintf;

final class DbTableExists
{
    /** @psalm-var list<non-empty-string> */
    private array $databases = [];

    /** @psalm-var list<non-empty-string> */
    private array $tables = [];

    public function __construct(private readonly DatabaseInterface $dbi)
    {
    }

    public function hasDatabase(DatabaseName $databaseName): bool
    {
        if (in_array($databaseName->getName(), $this->databases, true)) {
            return true;
        }

        if ($this->dbi->selectDb($databaseName)) {
            $this->databases[] = $databaseName->getName();

            return true;
        }

        return false;
    }

    public function hasTable(DatabaseName $database, TableName $table): bool
    {
        if (in_array($database->getName() . '.' . $table->getName(), $this->tables, true)) {
            return true;
        }

        if (
            $this->hasCachedTableContent($database, $table)
            || $this->isPermanentTable($table)
            || $this->isTemporaryTable($table)
        ) {
            $this->tables[] = $database->getName() . '.' . $table->getName();

            return true;
        }

        return false;
    }

    private function hasCachedTableContent(DatabaseName $database, TableName $table): bool
    {
        return (bool) $this->dbi->getCache()->getCachedTableContent([$database->getName(), $table->getName()]);
    }

    private function isPermanentTable(TableName $table): bool
    {
        $result = $this->dbi->tryQuery(sprintf('SHOW TABLES LIKE %s;', $this->dbi->quoteString($table->getName())));

        return $result !== false && $result->numRows() > 0;
    }

    /**
     * SHOW TABLES doesn't show temporary tables, so try select
     * as it can happen just in case temporary table. It should be fast.
     */
    private function isTemporaryTable(TableName $table): bool
    {
        return $this->dbi->tryQuery(sprintf('SELECT 1 FROM %s LIMIT 1;', Util::backquote($table))) !== false;
    }
}
