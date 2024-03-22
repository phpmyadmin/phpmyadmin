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
    private array $tables = [];

    public function __construct(private readonly DatabaseInterface $dbi)
    {
    }

    public function selectDatabase(DatabaseName $databaseName): bool
    {
        return $this->dbi->selectDb($databaseName);
    }

    /**
     * Check if a table exists in the given database.
     * It will return true if the table exists, regardless if it's temporary or permanent.
     */
    public function hasTable(DatabaseName $database, TableName $table): bool
    {
        if (in_array($database->getName() . '.' . $table->getName(), $this->tables, true)) {
            return true;
        }

        if ($this->tableExists($database, $table)) {
            $this->tables[] = $database->getName() . '.' . $table->getName();

            return true;
        }

        return false;
    }

    private function tableExists(DatabaseName $database, TableName $table): bool
    {
        // SHOW TABLES doesn't show temporary tables, so try select.
        return $this->dbi->tryQuery(sprintf(
            'SELECT 1 FROM %s.%s LIMIT 1;',
            Util::backquote($database),
            Util::backquote($table),
        )) !== false;
    }
}
