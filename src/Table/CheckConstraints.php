<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\CheckConstraint;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;

use function __;
use function implode;
use function in_array;
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

            if ($oldCheckConstraint->getLevel() == "Table") {
                $sqlQuery .= sprintf(' DROP CONSTRAINT %s;', $oldCheckConstraintName);
            } else {

            }
        }

        if ($checkConstraint->getLevel() == "Table") {
            $sqlQuery .= sprintf(' ADD CONSTRAINT %s CHECK(%s);', $oldCheckConstraintName, $checkConstraint->getClause());
        } else {

        }

        return $sqlQuery;
    }

    public function getSqlQueryForRename(string $oldIndexName, CheckConstraint $index, string $db, string $table): string
    {
        if (! Compatibility::isCompatibleRenameIndex($this->dbi->getVersion())) {
            return $this->getSqlQueryForCreateOrEdit($oldIndexName, $index, $db, $table);
        }

        if ($oldIndexName === 'PRIMARY') {
            if ($index->getName() === '') {
                $index->setName('PRIMARY');
            } elseif ($index->getName() !== 'PRIMARY') {
                $this->error = Message::error(
                    __('The name of the primary key must be "PRIMARY"!'),
                );
            }
        }

        if ($index->getName() === 'PRIMARY') {
            $this->error = Message::error(
                __('Can\'t rename index to PRIMARY!'),
            );
        }

        return QueryGenerator::getSqlQueryForIndexRename(
            $db,
            $table,
            $oldIndexName,
            $index->getName(),
        );
    }

    public function executeAddIndexSql(string|DatabaseName $db, string $sql): Message
    {
        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql);

        if (! $result) {
            return Message::error($this->dbi->getError());
        }

        return Message::success();
    }

    public function hasPrimaryKey(string|TableName $table): bool
    {
        $result = $this->dbi->query('SHOW KEYS FROM ' . Util::backquote($table));
        foreach ($result as $row) {
            if ($row['Key_name'] === 'PRIMARY') {
                return true;
            }
        }

        return false;
    }
}
