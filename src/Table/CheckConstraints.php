<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\CheckConstraint;
use PhpMyAdmin\ColumnFull;
use PhpMyAdmin\Current;
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

        if ($checkConstraint->getLevel() === CheckConstraint::COLUMN) {
            // If column constraint the edit is same as creating a new one
            $column = $this->dbi->getColumns(Current::$database, Current::$table, true)[$checkConstraint->getName()];
            $sqlQuery .= ' CHANGE ';
            $sqlQuery .=  Table::generateAlter(
                $checkConstraint->getName(),
                $checkConstraint->getName(),
                explode("(", $column->type)[0],
                explode(")", explode("(", $column->type)[1])[0], "",
                $column->collation == NULL ? "" : $column->collation,
                $column->isNull ? "YES" : "NO", "",
                $column->default ?? "",
                $column->extra,
                $column->comment,
                "", "", "",
            );

            $sqlQuery .= ' CHECK(' . $checkConstraint->getClause() . ');';
            return $sqlQuery; // Column constraints can be edited in 1 step
        }

        // Drops the old check constraint
        if ($oldCheckConstraintName !== null) {
            $oldCheckConstraint = CheckConstraint::singleton(
                $this->dbi,
                $dbName,
                $tableName,
                CheckConstraint::getQualifiedName($oldCheckConstraintName, $checkConstraint->getLevel()) // We can do this because we cannot edit the level of an existing check constraint
            );

            if ($oldCheckConstraint->getLevel() === CheckConstraint::TABLE) {
                $sqlQuery .= sprintf(' DROP CONSTRAINT %s, ', $oldCheckConstraintName);
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
        } else {
            // This means that we don't know the level of the check constraint.
            // This is because that the LEVEL column in information_schema.CHECK_CONSTRAINTS is not available.
            // It was introduced around MariaDB version 10.5.10 (check constraints par se are available since 10.2.1) and is still NOT present AT ALL in MySQL
            // In both systems information_schema.CHECK_CONSTRAINTS seems to store both table and column check constraints
            // Not sure what to do in this scenario
        }

        return $sqlQuery;
    }

    public static function getSqlForColumnConstraintDrop(string $dbName, string $tableName, CheckConstraint $checkConstraint) {
        $sqlQuery = sprintf(
            'ALTER TABLE %s.%s',
            Util::backquote($dbName),
            Util::backquote($tableName),
        );

        $dbi = DatabaseInterface::$instance;
        $column = $dbi->getColumns(Current::$database, Current::$table, true)[$checkConstraint->getName()];
        $sqlQuery .= ' CHANGE ';
        $sqlQuery .=  Table::generateAlter(
            $checkConstraint->getName(),
            $checkConstraint->getName(),
            explode("(", $column->type)[0],
            explode(")", explode("(", $column->type)[1])[0], "",
            $column->collation == NULL ? "" : $column->collation,
            $column->isNull ? "YES" : "NO", "",
            $column->default ?? "",
            $column->extra,
            $column->comment,
            "", "", "",
        );

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
