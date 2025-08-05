<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Components\OptionsArray;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\AlterStatement;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Statements\DropStatement;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function implode;
use function in_array;
use function sprintf;

class TableMover
{
    /**
     * A string containing the SQL query for constraints to be execute after all tables have been created.
     */
    public string $sqlConstraintsQuery = '';

    public function __construct(private readonly DatabaseInterface $dbi, private readonly Relation $relation)
    {
    }

    public function moveCopy(
        string $sourceDb,
        string $sourceTable,
        string $targetDb,
        string $targetTable,
        MoveScope $what,
        MoveMode $mode,
        bool $addDropIfExists,
    ): bool {
        // Try moving the tables directly, using native `RENAME` statement.
        if ($what === MoveScope::Move) {
            $tbl = new Table($sourceTable, $sourceDb, $this->dbi);
            if ($tbl->rename($targetTable, $targetDb)) {
                Current::$message = Message::success($tbl->getLastMessage());

                return true;
            }
        }

        $missingDatabaseMessage = $this->checkWhetherDatabasesExist($sourceDb, $targetDb);
        if ($missingDatabaseMessage !== null) {
            Current::$message = $missingDatabaseMessage;

            return false;
        }

        // Setting required export settings.
        Export::$asFile = true;

        // Selecting the database could avoid some problems with replicated
        // databases, when moving table from replicated one to not replicated one.
        $this->dbi->selectDb($targetDb);

        if ($what !== MoveScope::DataOnly) {
            $maintainRelations = $this->handleStructureCreation(
                $sourceTable,
                $sourceDb,
                $targetDb,
                $targetTable,
                $addDropIfExists,
                $what,
                $mode,
            );
        } else {
            Current::$sqlQuery = '';
        }

        $table = new Table($targetTable, $targetDb, $this->dbi);
        if ($what !== MoveScope::StructureOnly && ! $table->isView()) {
            $this->copyData($sourceDb, $sourceTable, $targetDb, $targetTable);
        }

        if ($what === MoveScope::Move) {
            $this->dropOldStructure($sourceDb, $sourceTable);

            // Rename table in configuration storage
            $this->relation->renameTable($sourceDb, $targetDb, $sourceTable, $targetTable);

            return true;
        }

        if ($what === MoveScope::DataOnly || isset($maintainRelations)) {
            return true;
        }

        // We are copying so create new entries as duplicates from old PMA DBs
        $relationParameters = $this->relation->getRelationParameters();

        if ($relationParameters->columnCommentsFeature !== null) {
            // Get all comments and MIME-Types for current table
            $commentsCopyRs = $this->dbi->queryAsControlUser(
                'SELECT column_name, comment'
                . ($relationParameters->browserTransformationFeature !== null
                ? ', mimetype, transformation, transformation_options'
                : '')
                . ' FROM '
                . Util::backquote($relationParameters->columnCommentsFeature->database)
                . '.'
                . Util::backquote($relationParameters->columnCommentsFeature->columnInfo)
                . ' WHERE '
                . ' db_name = ' . $this->dbi->quoteString($sourceDb, ConnectionType::ControlUser)
                . ' AND '
                . ' table_name = ' . $this->dbi->quoteString($sourceTable, ConnectionType::ControlUser),
            );

            // Write every comment as new copied entry. [MIME]
            foreach ($commentsCopyRs as $commentsCopyRow) {
                $newCommentQuery = 'REPLACE INTO '
                    . Util::backquote($relationParameters->columnCommentsFeature->database)
                    . '.' . Util::backquote($relationParameters->columnCommentsFeature->columnInfo)
                    . ' (db_name, table_name, column_name, comment'
                    . ($relationParameters->browserTransformationFeature !== null
                        ? ', mimetype, transformation, transformation_options'
                        : '')
                    . ') VALUES(' . $this->dbi->quoteString($targetDb, ConnectionType::ControlUser)
                    . ',' . $this->dbi->quoteString($targetTable, ConnectionType::ControlUser) . ','
                    . $this->dbi->quoteString($commentsCopyRow['column_name'], ConnectionType::ControlUser)
                    . ','
                    . $this->dbi->quoteString($commentsCopyRow['comment'], ConnectionType::ControlUser)
                    . ($relationParameters->browserTransformationFeature !== null
                        ? ',' . $this->dbi->quoteString($commentsCopyRow['mimetype'], ConnectionType::ControlUser)
                        . ',' . $this->dbi->quoteString($commentsCopyRow['transformation'], ConnectionType::ControlUser)
                        . ',' . $this->dbi->quoteString(
                            $commentsCopyRow['transformation_options'],
                            ConnectionType::ControlUser,
                        )
                        : '')
                    . ')';
                $this->dbi->queryAsControlUser($newCommentQuery);
            }

            unset($commentsCopyRs);
        }

        // duplicating the bookmarks must not be done here, but
        // just once per db

        $getFields = ['display_field'];
        $whereFields = ['db_name' => $sourceDb, 'table_name' => $sourceTable];
        $newFields = ['db_name' => $targetDb, 'table_name' => $targetTable];
        $this->duplicateInfo(
            RelationParameters::DISPLAY_WORK,
            RelationParameters::TABLE_INFO,
            $getFields,
            $whereFields,
            $newFields,
        );

        /** @todo revise this code when we support cross-db relations */
        $getFields = ['master_field', 'foreign_table', 'foreign_field'];
        $whereFields = ['master_db' => $sourceDb, 'master_table' => $sourceTable];
        $newFields = ['master_db' => $targetDb, 'foreign_db' => $targetDb, 'master_table' => $targetTable];
        $this->duplicateInfo(
            RelationParameters::REL_WORK,
            RelationParameters::RELATION,
            $getFields,
            $whereFields,
            $newFields,
        );

        $getFields = ['foreign_field', 'master_table', 'master_field'];
        $whereFields = ['foreign_db' => $sourceDb, 'foreign_table' => $sourceTable];
        $newFields = ['master_db' => $targetDb, 'foreign_db' => $targetDb, 'foreign_table' => $targetTable];
        $this->duplicateInfo(
            RelationParameters::REL_WORK,
            RelationParameters::RELATION,
            $getFields,
            $whereFields,
            $newFields,
        );

        return true;
    }

    /**
     * Inserts existing entries in a PMA_* table by reading a value from an old
     * entry
     *
     * @param string   $work        The array index, which Relation feature to check ('relwork', 'commwork', ...)
     * @param string   $table       The array index, which PMA-table to update ('bookmark', 'relation', ...)
     * @param string[] $getFields   Which fields will be SELECT'ed from the old entry
     * @param mixed[]  $whereFields Which fields will be used for the WHERE query (array('FIELDNAME' => 'FIELDVALUE'))
     * @param mixed[]  $newFields   Which fields will be used as new VALUES. These are the important keys which differ
     *                            from the old entry (array('FIELDNAME' => 'NEW FIELDVALUE'))
     */
    public function duplicateInfo(
        string $work,
        string $table,
        array $getFields,
        array $whereFields,
        array $newFields,
    ): int|bool {
        $relationParameters = $this->relation->getRelationParameters();
        $relationParams = $relationParameters->toArray();
        $lastId = -1;

        if (! isset($relationParams[$work], $relationParams[$table]) || ! $relationParams[$work]) {
            return true;
        }

        $selectParts = [];
        $rowFields = [];
        foreach ($getFields as $getField) {
            $selectParts[] = Util::backquote($getField);
            $rowFields[] = $getField;
        }

        $whereParts = [];
        foreach ($whereFields as $where => $value) {
            $whereParts[] = Util::backquote((string) $where) . ' = '
                . $this->dbi->quoteString((string) $value, ConnectionType::ControlUser);
        }

        $newParts = [];
        $newValueParts = [];
        foreach ($newFields as $where => $value) {
            $newParts[] = Util::backquote((string) $where);
            $newValueParts[] = $this->dbi->quoteString((string) $value, ConnectionType::ControlUser);
        }

        $tableCopyQuery = '
            SELECT ' . implode(', ', $selectParts) . '
              FROM ' . Util::backquote($relationParameters->db) . '.'
            . Util::backquote((string) $relationParams[$table]) . '
             WHERE ' . implode(' AND ', $whereParts);

        // must use DatabaseInterface::QUERY_BUFFERED here, since we execute
        // another query inside the loop
        $tableCopyRs = $this->dbi->queryAsControlUser($tableCopyQuery);

        foreach ($tableCopyRs as $tableCopyRow) {
            $valueParts = [];
            foreach ($tableCopyRow as $key => $val) {
                if (! in_array($key, $rowFields)) {
                    continue;
                }

                $valueParts[] = $this->dbi->quoteString($val, ConnectionType::ControlUser);
            }

            $newTableQuery = 'INSERT IGNORE INTO '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote((string) $relationParams[$table])
                . ' (' . implode(', ', $selectParts) . ', '
                . implode(', ', $newParts) . ') VALUES ('
                . implode(', ', $valueParts) . ', '
                . implode(', ', $newValueParts) . ')';

            $this->dbi->queryAsControlUser($newTableQuery);
            $lastId = $this->dbi->insertId();
        }

        return $lastId;
    }

    private function getConstraintsSqlWithoutNames(string $constraintsSql, Expression $destination): string
    {
        $parser = new Parser($constraintsSql);

        /**
         * The ALTER statement that generates the constraints.
         *
         * @var AlterStatement $statement
         */
        $statement = $parser->statements[0];

        // Changing the altered table to the destination.
        $statement->table = $destination;

        // Removing the name of the constraints.
        foreach ($statement->altered as $altered) {
            // All constraint names are removed because they must be unique.
            if (! $altered->options->has('CONSTRAINT')) {
                continue;
            }

            $altered->field = null;
        }

        // Building back the query.
        return $statement->build() . ';';
    }

    private function checkWhetherDatabasesExist(string $sourceDb, string $targetDb): Message|null
    {
        $databaseList = $this->dbi->getDatabaseList();

        if (! $databaseList->exists($sourceDb)) {
            return Message::rawError(
                sprintf(
                    __('Source database `%s` was not found!'),
                    htmlspecialchars($sourceDb),
                ),
            );
        }

        if (! $databaseList->exists($targetDb)) {
            return Message::rawError(
                sprintf(
                    __('Target database `%s` was not found!'),
                    htmlspecialchars($targetDb),
                ),
            );
        }

        return null;
    }

    private function createNewStructure(
        string $sqlStructure,
        Expression $destination,
        MoveScope $what,
        string $targetDb,
    ): void {
        $parser = new Parser($sqlStructure);

        if (empty($parser->statements[0])) {
            return;
        }

        /** @var CreateStatement $statement */
        $statement = $parser->statements[0];

        // Changing the destination.
        $statement->name = $destination;

        $sqlStructure = $statement->build() . ';';

        // This is to avoid some issues when renaming databases with views
        // See: https://github.com/phpmyadmin/phpmyadmin/issues/16422
        if ($what === MoveScope::Move) {
            $this->dbi->selectDb($targetDb);
        }

        $this->dbi->query($sqlStructure);
        Current::$sqlQuery .= "\n" . $sqlStructure;
    }

    private function executeDropIfExists(string $targetTable, string $targetDb, Expression $destination): void
    {
        $statement = new DropStatement();

        $tbl = new Table($targetTable, $targetDb, $this->dbi);

        $statement->options = new OptionsArray(
            [$tbl->isView() ? 'VIEW' : 'TABLE', 'IF EXISTS'],
        );

        $statement->fields = [$destination];

        $dropQuery = $statement->build() . ';';

        $this->dbi->query($dropQuery);
        Current::$sqlQuery .= "\n" . $dropQuery;
    }

    private function createIndexes(string $sql, Expression $destination): void
    {
        $parser = new Parser($sql);

        $sqlIndexes = '';
        /**
         * The ALTER statement that generates the indexes.
         *
         * @var AlterStatement $statement
         */
        foreach ($parser->statements as $statement) {
            // Changing the altered table to the destination.
            $statement->table = $destination;

            // Removing the name of the constraints.
            foreach ($statement->altered as $altered) {
                // All constraint names are removed because they must be unique.
                if (! $altered->options->has('CONSTRAINT')) {
                    continue;
                }

                $altered->field = null;
            }

            $sqlIndex = $statement->build() . ';';

            $this->dbi->query($sqlIndex);

            $sqlIndexes .= $sqlIndex;
        }

        Current::$sqlQuery .= "\n" . $sqlIndexes;
    }

    private function executeAlterAutoIncrement(string $sql, Expression $destination): void
    {
        $parser = new Parser($sql);

        /**
         * The ALTER statement that alters the AUTO_INCREMENT value.
         */
        $statement = $parser->statements[0];
        if (! ($statement instanceof AlterStatement)) {
            return;
        }

        // Changing the altered table to the destination.
        $statement->table = $destination;

        $query = $statement->build() . ';';

        $this->dbi->query($query);
        Current::$sqlQuery .= "\n" . $query;
    }

    private function dropOldStructure(string $sourceDb, string $sourceTable): void
    {
        // This could avoid some problems with replicated databases, when
        // moving table from replicated one to not replicated one
        $this->dbi->selectDb($sourceDb);

        $sourceTableObj = new Table($sourceTable, $sourceDb, $this->dbi);
        $sqlDropQuery = $sourceTableObj->isView() ? 'DROP VIEW ' : 'DROP TABLE ';

        $sqlDropQuery .= Util::backquote($sourceDb) . '.' . Util::backquote($sourceTable);
        $this->dbi->query($sqlDropQuery);

        Current::$sqlQuery .= "\n\n" . $sqlDropQuery . ';';
    }

    private function copyData(string $sourceDb, string $sourceTable, string $targetDb, string $targetTable): void
    {
        $sqlSetMode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
        $this->dbi->query($sqlSetMode);
        Current::$sqlQuery .= "\n\n" . $sqlSetMode . ';';

        $oldTable = new Table($sourceTable, $sourceDb, $this->dbi);
        $nonGeneratedCols = $oldTable->getNonGeneratedColumns();
        if ($nonGeneratedCols === []) {
            return;
        }

        $sqlInsertData = 'INSERT INTO ' . Util::backquote($targetDb) . '.' . Util::backquote($targetTable) . '('
            . implode(', ', $nonGeneratedCols)
            . ') SELECT ' . implode(', ', $nonGeneratedCols)
            . ' FROM ' . Util::backquote($sourceDb) . '.' . Util::backquote($sourceTable);

        $this->dbi->query($sqlInsertData);
        Current::$sqlQuery .= "\n\n" . $sqlInsertData . ';';
    }

    private function handleStructureCreation(
        string $sourceTable,
        string $sourceDb,
        string $targetDb,
        string $targetTable,
        bool $addDropIfExists,
        MoveScope $what,
        MoveMode $mode,
    ): bool {
        $maintainRelations = false;

        /**
         * Instance used for exporting the current structure of the table.
         *
         * @var ExportSql $exportSqlPlugin
         */
        $exportSqlPlugin = Plugins::getPlugin('export', 'sql', ExportType::Table);
        // It is better that all identifiers are quoted
        $exportSqlPlugin->useSqlBackquotes(true);

        ExportSql::$noConstraintsComments = true;
        $exportSqlPlugin->setAutoIncrement(isset($_POST['sql_auto_increment']) && (bool) $_POST['sql_auto_increment']);

        $isView = (new Table($sourceTable, $sourceDb, $this->dbi))->isView();
        /**
         * The old structure of the table.
         */
        $sqlStructure = $exportSqlPlugin->getTableDef($sourceDb, $sourceTable, false, $isView);

        // -----------------------------------------------------------------
        // Phase 0: Preparing structures used.

        /**
         * The destination where the table is moved or copied to.
         */
        $destination = new Expression($targetDb, $targetTable, '');

        // Find server's SQL mode so the builder can generate correct
        // queries.
        // One of the options that alters the behaviour is `ANSI_QUOTES`.
        Context::setMode((string) $this->dbi->fetchValue('SELECT @@sql_mode'));

        // -----------------------------------------------------------------
        // Phase 1: Dropping existent element of the same name (if exists
        // and required).

        if ($addDropIfExists) {
            $this->executeDropIfExists($targetTable, $targetDb, $destination);

            // If an existing table gets deleted, maintain any entries for
            // the PMA_* tables.
            $maintainRelations = true;
        }

        // -----------------------------------------------------------------
        // Phase 2: Generating the new query of this structure.

        $this->createNewStructure($sqlStructure, $destination, $what, $targetDb);

        // -----------------------------------------------------------------
        // Phase 3: Adding constraints.
        // All constraint names are removed because they must be unique.
        $this->sqlConstraintsQuery = $exportSqlPlugin->sqlConstraintsQuery; // This line is probably not needed.
        if ($what === MoveScope::Move && $exportSqlPlugin->sqlConstraintsQuery !== '') {
            $this->sqlConstraintsQuery = $this->getConstraintsSqlWithoutNames(
                $exportSqlPlugin->sqlConstraintsQuery,
                $destination,
            );

            Current::$sqlQuery .= "\n" . $this->sqlConstraintsQuery;

            // We can only execute it if both tables have been created.
            // When performing the whole database move,
            // the constraints can only be created after all tables have been created.
            // Thus, we must keep the global so that the caller can execute these queries.
            if ($mode === MoveMode::SingleTable) {
                $this->dbi->query($this->sqlConstraintsQuery);
                $this->sqlConstraintsQuery = '';
            }
        }

        // -----------------------------------------------------------------
        // Phase 4: Adding indexes.
        // View phase 3.

        if ($exportSqlPlugin->sqlIndexes !== null) {
            $this->createIndexes($exportSqlPlugin->sqlIndexes, $destination);
        }

        // -----------------------------------------------------------------
        // Phase 5: Adding AUTO_INCREMENT.

        if ($exportSqlPlugin->sqlAutoIncrements !== null) {
            $this->executeAlterAutoIncrement($exportSqlPlugin->sqlAutoIncrements, $destination);
        }

        return $maintainRelations;
    }
}
