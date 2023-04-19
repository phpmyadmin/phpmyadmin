<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function mb_strpos;
use function mb_substr;

final class ShowGrants
{
    public readonly string $grants;
    public readonly string $tableName;
    public readonly string $dbName;
    private int $tableNameEndOffset;
    private int $tableNameStartOffset;
    private int $dbNameOffset;

    public function __construct(string $showGrants)
    {
        $this->dbNameOffset = (int) mb_strpos($showGrants, ' ON ') + 4;
        $this->tableNameEndOffset = (int) mb_strpos($showGrants, ' TO ');
        $this->tableNameStartOffset = $this->getTableNameStartOffset($showGrants);
        $this->grants = $this->getShowGrantsString($showGrants);
        $this->tableName = $this->getGrantsTableName($showGrants);
        $this->dbName = $this->getGrantsDbName($showGrants);
    }

    private function getTableNameStartOffset(string $showGrants): int
    {
        $tableNameStartOffset = mb_strpos($showGrants, '`.', $this->dbNameOffset);

        if (
            $tableNameStartOffset !== false
            && $tableNameStartOffset !== 0
            && $tableNameStartOffset < $this->tableNameEndOffset
        ) {
            return $tableNameStartOffset + 1;
        }

        return (int) mb_strpos($showGrants, '.', $this->dbNameOffset);
    }

    private function getShowGrantsString(string $showGrants): string
    {
        return mb_substr($showGrants, 6, (int) mb_strpos($showGrants, ' ON ') - 6);
    }

    private function getGrantsTableName(string $showGrants): string
    {
        $showGrantsTableName = mb_substr(
            $showGrants,
            $this->tableNameStartOffset + 1,
            $this->tableNameEndOffset - $this->tableNameStartOffset - 1,
        );

        return Util::unQuote($showGrantsTableName, '`');
    }

    private function getGrantsDbName(string $showGrants): string
    {
        $showGrantsDbName = mb_substr(
            $showGrants,
            $this->dbNameOffset,
            $this->tableNameStartOffset - $this->dbNameOffset,
        );

        return Util::unQuote($showGrantsDbName, '`');
    }
}
