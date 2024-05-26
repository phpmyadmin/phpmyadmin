<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\CheckConstraint;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;

use function __;
use function sprintf;

final class CheckConstraints
{
    private Message|null $error = null;

    public function __construct(private readonly DatabaseInterface $dbi)
    {
    }

    public function getError(): Message|null
    {
        return $this->error;
    }

    /**
     * Function to get the sql query for index creation or edit
     *
     * @param CheckConstraint $checkConstraint current check constraint
     */
    public function getSqlQueryForCreateOrEdit(
        string|null $oldCheckConstraintName,
        CheckConstraint $checkConstraint,
        string $dbName,
        string $tableName,
    ): string {
        // $sql_query is the one displayed in the query box
        $sqlQuery = sprintf(
            'ALTER TABLE %s.%s',
            Util::backquote($dbName),
            Util::backquote($tableName),
        );

        // Drops the old check constraint
        if ($oldCheckConstraintName !== null) {
            $oldCheckConstraint = CheckConstraint::singleton($this->dbi, $dbName, $tableName, $oldCheckConstraintName);

            if ($oldCheckConstraint->getLevel() === CheckConstraint::TABLE) {
                $sqlQuery .= sprintf(' DROP CONSTRAINT %s;', $oldCheckConstraintName);
            } else if ($checkConstraint->getLevel() === CheckConstraint::COLUMN) {
                // TODO: implement sql for dropping column check constraints
            } else {
                // This means that we don't know the level of the check constraint.
                // This is because that the LEVEL column in information_schema.CHECK_CONSTRAINTS is not available.
                // It was introduced around MariaDB version 10.5.10 (check constraints par se are available since 10.2.1) and is still NOT present AT ALL in MySQL
                // In both systems information_schema.CHECK_CONSTRAINTS seems to store both table and column check constraints
                // Not sure what to do in this scenario
            }
        }

        if ($checkConstraint->getLevel() === CheckConstraint::TABLE) {
            $sqlQuery .= sprintf(' ADD CONSTRAINT %s CHECK(%s);', $checkConstraint->getName(), $checkConstraint->getClause());
        } else if ($checkConstraint->getLevel() === CheckConstraint::COLUMN) {
            // TODO: implement sql for adding column check constraints
        } else {
            // This means that we don't know the level of the check constraint.
            // This is because that the LEVEL column in information_schema.CHECK_CONSTRAINTS is not available.
            // It was introduced around MariaDB version 10.5.10 (check constraints par se are available since 10.2.1) and is still NOT present AT ALL in MySQL
            // In both systems information_schema.CHECK_CONSTRAINTS seems to store both table and column check constraints
            // Not sure what to do in this scenario
        }

        return $sqlQuery;
    }

    public function getSqlQueryForRename(string $oldIndexName, CheckConstraint $checkConstraint, string $db, string $table): string
    {
        return $this->getSqlQueryForCreateOrEdit($oldIndexName, $checkConstraint, $db, $table);
        if (! Compatibility::isCompatibleRenameIndex($this->dbi->getVersion())) {
        }


        return QueryGenerator::getSqlQueryForIndexRename(
            $db,
            $table,
            $oldIndexName,
            $checkConstraint->getName(),
        );
    }
}
