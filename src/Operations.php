<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\Database\RoutineType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Table\MoveMode;
use PhpMyAdmin\Table\MoveScope;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Table\TableMover;
use PhpMyAdmin\Triggers\Triggers;

use function __;
use function array_merge;
use function count;
use function explode;
use function in_array;
use function is_scalar;
use function is_string;
use function mb_strtolower;
use function str_replace;
use function strtolower;
use function urldecode;

/**
 * Set of functions with the operations section in phpMyAdmin
 */
class Operations
{
    public static string $autoIncrement = '';

    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly Relation $relation,
        private readonly TableMover $tableMover,
    ) {
    }

    /**
     * Run the Procedure definitions and function definitions
     *
     * to avoid selecting alternatively the current and new db
     * we would need to modify the CREATE definitions to qualify
     * the db name
     *
     * @param string $db database name
     */
    public function runProcedureAndFunctionDefinitions(string $db, DatabaseName $newDatabaseName): void
    {
        foreach (Routines::getNames($this->dbi, $db, RoutineType::Procedure) as $procedureName) {
            $this->dbi->selectDb($db);
            $query = Routines::getProcedureDefinition($this->dbi, $db, $procedureName);
            if ($query === null) {
                continue;
            }

            // collect for later display
            Current::$sqlQuery .= "\n" . $query;
            $this->dbi->selectDb($newDatabaseName);
            $this->dbi->query($query);
        }

        foreach (Routines::getNames($this->dbi, $db, RoutineType::Function) as $functionName) {
            $this->dbi->selectDb($db);
            $query = Routines::getFunctionDefinition($this->dbi, $db, $functionName);
            if ($query === null) {
                continue;
            }

            // collect for later display
            Current::$sqlQuery .= "\n" . $query;
            $this->dbi->selectDb($newDatabaseName);
            $this->dbi->query($query);
        }
    }

    /**
     * Create database before copy
     */
    public function createDbBeforeCopy(UserPrivileges $userPrivileges, DatabaseName $newDatabaseName): void
    {
        $localQuery = 'CREATE DATABASE IF NOT EXISTS '
            . Util::backquote($newDatabaseName);
        if (isset($_POST['db_collation'])) {
            $localQuery .= ' DEFAULT'
                . Util::getCharsetQueryPart($_POST['db_collation']);
        }

        $localQuery .= ';';
        Current::$sqlQuery .= $localQuery;

        // save the original db name because Tracker.php which
        // may be called under $this->dbi->query() changes \PhpMyAdmin\Current::$database
        // for some statements, one of which being CREATE DATABASE
        $originalDb = Current::$database;
        $this->dbi->query($localQuery);
        Current::$database = $originalDb;

        // Set the SQL mode to NO_AUTO_VALUE_ON_ZERO to prevent MySQL from creating
        // export statements it cannot import
        $sqlSetMode = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'";
        $this->dbi->query($sqlSetMode);

        // rebuild the database list because Table::moveCopy
        // checks in this list if the target db exists
        $this->dbi->getDatabaseList()->build($userPrivileges);
    }

    /**
     * Get views as an array and create SQL view stand-in
     *
     * @param string[]  $tables          array of all tables in given db or dbs
     * @param ExportSql $exportSqlPlugin export plugin instance
     * @param string    $db              database name
     *
     * @return string[]
     */
    public function getViewsAndCreateSqlViewStandIn(
        array $tables,
        ExportSql $exportSqlPlugin,
        string $db,
        DatabaseName $newDatabaseName,
    ): array {
        $views = [];
        foreach ($tables as $table) {
            // to be able to rename a db containing views,
            // first all the views are collected and a stand-in is created
            // the real views are created after the tables
            if (! $this->dbi->getTable($db, $table)->isView()) {
                continue;
            }

            // If view exists, and 'add drop view' is selected: Drop it!
            if ($_POST['what'] !== 'nocopy' && isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true') {
                $dropQuery = 'DROP VIEW IF EXISTS '
                    . Util::backquote($newDatabaseName) . '.'
                    . Util::backquote($table);
                $this->dbi->query($dropQuery);

                Current::$sqlQuery .= "\n" . $dropQuery . ';';
            }

            $views[] = $table;
            // Create stand-in definition to resolve view dependencies
            $sqlViewStandin = $exportSqlPlugin->getTableDefStandIn($db, $table);
            $this->dbi->selectDb($newDatabaseName);
            $this->dbi->query($sqlViewStandin);
            Current::$sqlQuery .= "\n" . $sqlViewStandin;
        }

        return $views;
    }

    /**
     * Get sql query for copy/rename table and boolean for whether copy/rename or not
     *
     * @param string[] $tables array of all tables in given db or dbs
     * @param bool     $move   whether database name is empty or not
     * @param string   $db     database name
     *
     * @return string[] SQL queries for the constraints
     */
    public function copyTables(array $tables, bool $move, string $db, DatabaseName $newDatabaseName): array
    {
        $sqlContraints = [];
        foreach ($tables as $table) {
            // skip the views; we have created stand-in definitions
            if ($this->dbi->getTable($db, $table)->isView()) {
                continue;
            }

            // value of $what for this table only
            $copyMode = $_POST['what'];

            // do not copy the data from a Merge table
            // note: on the calling FORM, 'data' means 'structure and data'
            if ($this->dbi->getTable($db, $table)->isMerge()) {
                if ($copyMode === 'data') {
                    $copyMode = 'structure';
                }

                if ($copyMode === 'dataonly') {
                    $copyMode = 'nocopy';
                }
            }

            if ($copyMode === 'nocopy') {
                continue;
            }

            // keep the triggers from the original db+table
            // (third param is empty because delimiters are only intended
            //  for importing via the mysql client or our Import feature)
            $triggers = Triggers::getDetails($this->dbi, $db, $table);

            $moveScope = MoveScope::tryFrom($copyMode) ?? MoveScope::StructureAndData;
            if (
                ! $this->tableMover->moveCopy(
                    $db,
                    $table,
                    $newDatabaseName->getName(),
                    $table,
                    $move ? MoveScope::Move : $moveScope,
                    MoveMode::WholeDatabase,
                    isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true',
                )
            ) {
                break;
            }

            // apply the triggers to the destination db+table
            if ($triggers !== []) {
                $this->dbi->selectDb($newDatabaseName);
                foreach ($triggers as $trigger) {
                    $createSqlQuery = $trigger->getCreateSql('');
                    $this->dbi->query($createSqlQuery);
                    Current::$sqlQuery .= "\n" . $createSqlQuery . ';';
                }
            }

            // this does not apply to a rename operation
            if (! isset($_POST['add_constraints']) || $this->tableMover->sqlConstraintsQuery === '') {
                continue;
            }

            $sqlContraints[] = $this->tableMover->sqlConstraintsQuery;
            $this->tableMover->sqlConstraintsQuery = '';
        }

        return $sqlContraints;
    }

    /**
     * Run the EVENT definition for selected database
     *
     * to avoid selecting alternatively the current and new db
     * we would need to modify the CREATE definitions to qualify
     * the db name
     *
     * @param string $db database name
     */
    public function runEventDefinitionsForDb(string $db, DatabaseName $newDatabaseName): void
    {
        /** @var string[] $eventNames */
        $eventNames = $this->dbi->fetchSingleColumn(
            'SELECT EVENT_NAME FROM information_schema.EVENTS WHERE EVENT_SCHEMA= '
            . $this->dbi->quoteString($db) . ';',
        );

        foreach ($eventNames as $eventName) {
            $this->dbi->selectDb($db);
            $query = Events::getDefinition($this->dbi, $db, $eventName);
            // collect for later display
            Current::$sqlQuery .= "\n" . $query;
            $this->dbi->selectDb($newDatabaseName);
            $this->dbi->query($query);
        }
    }

    /**
     * Handle the views, return the boolean value whether table rename/copy or not
     *
     * @param string[] $views views as an array
     * @param bool     $move  whether database name is empty or not
     * @param string   $db    database name
     */
    public function handleTheViews(array $views, bool $move, string $db, DatabaseName $newDatabaseName): void
    {
        // Add DROP IF EXIST to CREATE VIEW query, to remove stand-in VIEW that was created earlier.
        foreach ($views as $view) {
            $copyingSucceeded = $this->tableMover->moveCopy(
                $db,
                $view,
                $newDatabaseName->getName(),
                $view,
                $move ? MoveScope::Move : MoveScope::StructureOnly,
                MoveMode::WholeDatabase,
                true,
            );
            if (! $copyingSucceeded) {
                break;
            }
        }
    }

    /**
     * Adjust the privileges after Renaming the db
     *
     * @param string $oldDb Database name before renaming
     */
    public function adjustPrivilegesMoveDb(
        UserPrivileges $userPrivileges,
        string $oldDb,
        DatabaseName $newDatabaseName,
    ): void {
        if (
            ! $userPrivileges->database || ! $userPrivileges->table
            || ! $userPrivileges->column || ! $userPrivileges->routines
            || ! $userPrivileges->isReload
        ) {
            return;
        }

        $this->dbi->selectDb('mysql');
        $newName = str_replace('_', '\_', $newDatabaseName->getName());
        $oldDb = str_replace('_', '\_', $oldDb);

        // For Db specific privileges
        $this->dbi->query('UPDATE ' . Util::backquote('db')
            . 'SET Db = ' . $this->dbi->quoteString($newName)
            . ' where Db = ' . $this->dbi->quoteString($oldDb) . ';');

        // For table specific privileges
        $this->dbi->query('UPDATE ' . Util::backquote('tables_priv')
            . 'SET Db = ' . $this->dbi->quoteString($newName)
            . ' where Db = ' . $this->dbi->quoteString($oldDb) . ';');

        // For column specific privileges
        $this->dbi->query('UPDATE ' . Util::backquote('columns_priv')
            . 'SET Db = ' . $this->dbi->quoteString($newName)
            . ' where Db = ' . $this->dbi->quoteString($oldDb) . ';');

        // For procedures specific privileges
        $this->dbi->query('UPDATE ' . Util::backquote('procs_priv')
            . 'SET Db = ' . $this->dbi->quoteString($newName)
            . ' where Db = ' . $this->dbi->quoteString($oldDb) . ';');

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Adjust the privileges after Copying the db
     *
     * @param string $oldDb Database name before copying
     */
    public function adjustPrivilegesCopyDb(
        UserPrivileges $userPrivileges,
        string $oldDb,
        DatabaseName $newDatabaseName,
    ): void {
        if (
            ! $userPrivileges->database || ! $userPrivileges->table
            || ! $userPrivileges->column || ! $userPrivileges->routines
            || ! $userPrivileges->isReload
        ) {
            return;
        }

        $this->dbi->selectDb('mysql');
        $newName = str_replace('_', '\_', $newDatabaseName->getName());
        $oldDb = str_replace('_', '\_', $oldDb);

        $queryDbSpecificOld = 'SELECT * FROM '
            . Util::backquote('db') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $oldPrivsDb = $this->dbi->fetchResult($queryDbSpecificOld, 0);

        foreach ($oldPrivsDb as $oldPriv) {
            $newDbDbPrivsQuery = 'INSERT INTO ' . Util::backquote('db')
                . ' VALUES("' . $oldPriv[0] . '", "' . $newName . '"';
            $privCount = count($oldPriv);
            for ($i = 2; $i < $privCount; $i++) {
                $newDbDbPrivsQuery .= ', "' . $oldPriv[$i] . '"';
            }

            $newDbDbPrivsQuery .= ')';

            $this->dbi->query($newDbDbPrivsQuery);
        }

        // For Table Specific privileges
        $queryTableSpecificOld = 'SELECT * FROM '
            . Util::backquote('tables_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $oldPrivsTable = $this->dbi->fetchResult($queryTableSpecificOld, 0);

        foreach ($oldPrivsTable as $oldPriv) {
            $newDbTablePrivsQuery = 'INSERT INTO ' . Util::backquote(
                'tables_priv',
            ) . ' VALUES("' . $oldPriv[0] . '", "' . $newName . '", "'
            . $oldPriv[2] . '", "' . $oldPriv[3] . '", "' . $oldPriv[4]
            . '", "' . $oldPriv[5] . '", "' . $oldPriv[6] . '", "'
            . $oldPriv[7] . '");';

            $this->dbi->query($newDbTablePrivsQuery);
        }

        // For Column Specific privileges
        $queryColSpecificOld = 'SELECT * FROM '
            . Util::backquote('columns_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $oldPrivsCol = $this->dbi->fetchResult($queryColSpecificOld, 0);

        foreach ($oldPrivsCol as $oldPriv) {
            $newDbColPrivsQuery = 'INSERT INTO ' . Util::backquote(
                'columns_priv',
            ) . ' VALUES("' . $oldPriv[0] . '", "' . $newName . '", "'
            . $oldPriv[2] . '", "' . $oldPriv[3] . '", "' . $oldPriv[4]
            . '", "' . $oldPriv[5] . '", "' . $oldPriv[6] . '");';

            $this->dbi->query($newDbColPrivsQuery);
        }

        // For Procedure Specific privileges
        $queryProcSpecificOld = 'SELECT * FROM '
            . Util::backquote('procs_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '";';

        $oldPrivsProc = $this->dbi->fetchResult($queryProcSpecificOld, 0);

        foreach ($oldPrivsProc as $oldPriv) {
            $newDbProcPrivsQuery = 'INSERT INTO ' . Util::backquote(
                'procs_priv',
            ) . ' VALUES("' . $oldPriv[0] . '", "' . $newName . '", "'
            . $oldPriv[2] . '", "' . $oldPriv[3] . '", "' . $oldPriv[4]
            . '", "' . $oldPriv[5] . '", "' . $oldPriv[6] . '", "'
            . $oldPriv[7] . '");';

            $this->dbi->query($newDbProcPrivsQuery);
        }

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Create all accumulated constraints
     *
     * @param string[] $sqlConstraints array of sql constraints for the database
     */
    public function createAllAccumulatedConstraints(array $sqlConstraints, DatabaseName $newDatabaseName): void
    {
        $this->dbi->selectDb($newDatabaseName);
        foreach ($sqlConstraints as $query) {
            $this->dbi->query($query);
            // and prepare to display them
            Current::$sqlQuery .= "\n" . $query;
        }
    }

    /**
     * Duplicate the bookmarks for the db (done once for each db)
     *
     * @param bool   $error whether table rename/copy or not
     * @param string $db    database name
     */
    public function duplicateBookmarks(bool $error, string $db, DatabaseName $newDatabaseName): void
    {
        if ($error || $db === $newDatabaseName->getName()) {
            return;
        }

        $getFields = ['user', 'label', 'query'];
        $whereFields = ['dbase' => $db];
        $newFields = ['dbase' => $newDatabaseName->getName()];
        $this->tableMover->duplicateInfo(
            RelationParameters::BOOKMARK_WORK,
            RelationParameters::BOOKMARK,
            $getFields,
            $whereFields,
            $newFields,
        );
    }

    /**
     * Get array of possible row formats
     *
     * @return string[][]
     */
    public function getPossibleRowFormat(): array
    {
        // the outer array is for engines, the inner array contains the dropdown
        // option values as keys then the dropdown option labels

        $possibleRowFormats = [
            'ARCHIVE' => ['COMPRESSED' => 'COMPRESSED'],
            'ARIA' => ['FIXED' => 'FIXED', 'DYNAMIC' => 'DYNAMIC', 'PAGE' => 'PAGE'],
            'MARIA' => ['FIXED' => 'FIXED', 'DYNAMIC' => 'DYNAMIC', 'PAGE' => 'PAGE'],
            'MYISAM' => ['FIXED' => 'FIXED', 'DYNAMIC' => 'DYNAMIC'],
            'PBXT' => ['FIXED' => 'FIXED', 'DYNAMIC' => 'DYNAMIC'],
            'INNODB' => ['COMPACT' => 'COMPACT', 'REDUNDANT' => 'REDUNDANT'],
        ];

        /** @var Innodb $innodbEnginePlugin */
        $innodbEnginePlugin = StorageEngine::getEngine('Innodb');
        $innodbPluginVersion = $innodbEnginePlugin->getInnodbPluginVersion();
        $innodbFileFormat = '';
        if ($innodbPluginVersion !== '') {
            $innodbFileFormat = $innodbEnginePlugin->getInnodbFileFormat() ?? '';
        }

        /**
         * Newer MySQL/MariaDB always return empty a.k.a '' on $innodbFileFormat otherwise
         * old versions of MySQL/MariaDB must be returning something or not empty.
         * This patch is to support newer MySQL/MariaDB while also for backward compatibilities.
         */
        if (
            strtolower($innodbFileFormat) === 'barracuda' || $innodbFileFormat === ''
            && $innodbEnginePlugin->supportsFilePerTable()
        ) {
            $possibleRowFormats['INNODB']['DYNAMIC'] = 'DYNAMIC';
            $possibleRowFormats['INNODB']['COMPRESSED'] = 'COMPRESSED';
        }

        return $possibleRowFormats;
    }

    /** @return array<string, string> */
    public function getPartitionMaintenanceChoices(): array
    {
        $choices = [
            'ANALYZE' => __('Analyze'),
            'CHECK' => __('Check'),
            'OPTIMIZE' => __('Optimize'),
            'REBUILD' => __('Rebuild'),
            'REPAIR' => __('Repair'),
            'TRUNCATE' => __('Truncate'),
        ];

        $partitionMethod = Partition::getPartitionMethod(Current::$database, Current::$table);

        // add COALESCE or DROP option to choices array depending on Partition method
        if (in_array($partitionMethod, ['RANGE', 'RANGE COLUMNS', 'LIST', 'LIST COLUMNS'], true)) {
            $choices['DROP'] = __('Drop');
        } else {
            $choices['COALESCE'] = __('Coalesce');
        }

        return $choices;
    }

    /**
     * @param mixed[] $urlParams          Array of url parameters.
     * @param bool    $hasRelationFeature If relation feature is enabled.
     *
     * @return mixed[]
     */
    public function getForeignersForReferentialIntegrityCheck(
        array $urlParams,
        bool $hasRelationFeature,
    ): array {
        if (! $hasRelationFeature) {
            return [];
        }

        $foreigners = [];
        $this->dbi->selectDb(Current::$database);
        $foreign = $this->relation->getForeignersInternal(Current::$database, Current::$table);

        foreach ($foreign as $master => $arr) {
            $joinQuery = 'SELECT '
                . Util::backquote(Current::$table) . '.*'
                . ' FROM ' . Util::backquote(Current::$table)
                . ' LEFT JOIN '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($arr['foreign_table']);

            if ($arr['foreign_table'] === Current::$table) {
                $foreignTable = Current::$table . '1';
                $joinQuery .= ' AS ' . Util::backquote($foreignTable);
            } else {
                $foreignTable = $arr['foreign_table'];
            }

            $joinQuery .= ' ON '
                . Util::backquote(Current::$table) . '.'
                . Util::backquote($master)
                . ' = '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($foreignTable) . '.'
                . Util::backquote($arr['foreign_field'])
                . ' WHERE '
                . Util::backquote($arr['foreign_db'])
                . '.'
                . Util::backquote($foreignTable) . '.'
                . Util::backquote($arr['foreign_field'])
                . ' IS NULL AND '
                . Util::backquote(Current::$table) . '.'
                . Util::backquote($master)
                . ' IS NOT NULL';
            $thisUrlParams = array_merge(
                $urlParams,
                ['sql_query' => $joinQuery, 'sql_signature' => Core::signSqlQuery($joinQuery)],
            );

            $foreigners[] = [
                'params' => $thisUrlParams,
                'master' => $master,
                'db' => $arr['foreign_db'],
                'table' => $arr['foreign_table'],
                'field' => $arr['foreign_field'],
            ];
        }

        return $foreigners;
    }

    /**
     * Get table alters array
     *
     * @param Table  $pmaTable            The Table object
     * @param string $packKeys            pack keys
     * @param string $checksum            value of checksum
     * @param string $pageChecksum        value of page checksum
     * @param string $delayKeyWrite       delay key write
     * @param string $rowFormat           row format
     * @param string $newTblStorageEngine table storage engine
     * @param string $transactional       value of transactional
     * @param string $tableCollation      collation of the table
     *
     * @return string[]
     */
    public function getTableAltersArray(
        Table $pmaTable,
        string $packKeys,
        string $checksum,
        string $pageChecksum,
        string $delayKeyWrite,
        string $rowFormat,
        string $newTblStorageEngine,
        string $transactional,
        string $tableCollation,
        string $tableStorageEngine,
    ): array {
        $tableAlters = [];

        if (isset($_POST['comment']) && urldecode($_POST['prev_comment']) !== $_POST['comment']) {
            $tableAlters[] = 'COMMENT = ' . $this->dbi->quoteString($_POST['comment']);
        }

        if (
            $newTblStorageEngine !== ''
            && mb_strtolower($newTblStorageEngine) !== mb_strtolower($tableStorageEngine)
        ) {
            $tableAlters[] = 'ENGINE = ' . $newTblStorageEngine;
        }

        if (! empty($_POST['tbl_collation']) && $_POST['tbl_collation'] !== $tableCollation) {
            $tableAlters[] = 'DEFAULT '
                . Util::getCharsetQueryPart($_POST['tbl_collation']);
        }

        if (
            $pmaTable->isEngine(['MYISAM', 'ARIA', 'ISAM'])
            && isset($_POST['new_pack_keys'])
            && $_POST['new_pack_keys'] !== $packKeys
        ) {
            $tableAlters[] = 'pack_keys = ' . $_POST['new_pack_keys'];
        }

        $newChecksum = empty($_POST['new_checksum']) ? '0' : '1';
        if ($pmaTable->isEngine(['MYISAM', 'ARIA']) && $newChecksum !== $checksum) {
            $tableAlters[] = 'checksum = ' . $newChecksum;
        }

        $newTransactional = empty($_POST['new_transactional']) ? '0' : '1';
        if ($pmaTable->isEngine('ARIA') && $newTransactional !== $transactional) {
            $tableAlters[] = 'TRANSACTIONAL = ' . $newTransactional;
        }

        $newPageChecksum = empty($_POST['new_page_checksum']) ? '0' : '1';
        if ($pmaTable->isEngine('ARIA') && $newPageChecksum !== $pageChecksum) {
            $tableAlters[] = 'PAGE_CHECKSUM = ' . $newPageChecksum;
        }

        $newDelayKeyWrite = empty($_POST['new_delay_key_write']) ? '0' : '1';
        if ($pmaTable->isEngine(['MYISAM', 'ARIA']) && $newDelayKeyWrite !== $delayKeyWrite) {
            $tableAlters[] = 'delay_key_write = ' . $newDelayKeyWrite;
        }

        if (
            $pmaTable->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT', 'ROCKSDB'])
            && ! empty($_POST['new_auto_increment'])
            && $_POST['new_auto_increment'] !== self::$autoIncrement
            && $_POST['new_auto_increment'] !== $_POST['hidden_auto_increment']
        ) {
            $tableAlters[] = 'auto_increment = ' . (int) $_POST['new_auto_increment'];
        }

        if (! empty($_POST['new_row_format'])) {
            $newRowFormat = $_POST['new_row_format'];
            $newRowFormatLower = mb_strtolower($newRowFormat);
            if (
                $pmaTable->isEngine(['MYISAM', 'ARIA', 'INNODB', 'PBXT'])
                && ($rowFormat === '' || $newRowFormatLower !== mb_strtolower($rowFormat))
                && in_array($newRowFormat, ['DEFAULT', 'DYNAMIC', 'FIXED', 'COMPRESSED', 'REDUNDANT', 'COMPACT'], true)
            ) {
                $tableAlters[] = 'ROW_FORMAT = ' . $newRowFormat;
            }
        }

        return $tableAlters;
    }

    /**
     * Get warning messages array
     *
     * @return string[]
     */
    public function getWarningMessagesArray(string $newTableStorageEngine): array
    {
        $warningMessages = [];
        foreach ($this->dbi->getWarnings() as $warning) {
            // In MariaDB 5.1.44, when altering a table from Maria to MyISAM
            // and if TRANSACTIONAL was set, the system reports an error;
            // I discussed with a Maria developer and he agrees that this
            // should not be reported with a Level of Error, so here
            // I just ignore it. But there are other 1478 messages
            // that it's better to show.
            if (
                $newTableStorageEngine === 'MYISAM'
                && $warning->code === 1478
                && $warning->level === 'Error'
            ) {
                continue;
            }

            $warningMessages[] = (string) $warning;
        }

        return $warningMessages;
    }

    /**
     * Adjust the privileges after renaming/moving a table
     *
     * @param string $oldDb    Database name before table renaming/moving table
     * @param string $oldTable Table name before table renaming/moving table
     * @param string $newDb    Database name after table renaming/ moving table
     * @param string $newTable Table name after table renaming/moving table
     */
    public function adjustPrivilegesRenameOrMoveTable(
        UserPrivileges $userPrivileges,
        string $oldDb,
        string $oldTable,
        string $newDb,
        string $newTable,
    ): void {
        if (! $userPrivileges->table || ! $userPrivileges->column || ! $userPrivileges->isReload) {
            return;
        }

        $this->dbi->selectDb('mysql');

        // For table specific privileges
        $this->dbi->query('UPDATE ' . Util::backquote('tables_priv')
            . 'SET Db = ' . $this->dbi->quoteString($newDb)
            . ', Table_name = ' . $this->dbi->quoteString($newTable)
            . ' where Db = ' . $this->dbi->quoteString($oldDb)
            . ' AND Table_name = ' . $this->dbi->quoteString($oldTable)
            . ';');

        // For column specific privileges
        $this->dbi->query('UPDATE ' . Util::backquote('columns_priv')
            . 'SET Db = ' . $this->dbi->quoteString($newDb)
            . ', Table_name = ' . $this->dbi->quoteString($newTable)
            . ' where Db = ' . $this->dbi->quoteString($oldDb)
            . ' AND Table_name = ' . $this->dbi->quoteString($oldTable)
            . ';');

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Adjust the privileges after copying a table
     *
     * @param string $oldDb    Database name before table copying
     * @param string $oldTable Table name before table copying
     * @param string $newDb    Database name after table copying
     * @param string $newTable Table name after table copying
     */
    public function adjustPrivilegesCopyTable(
        UserPrivileges $userPrivileges,
        string $oldDb,
        string $oldTable,
        string $newDb,
        string $newTable,
    ): void {
        if (! $userPrivileges->table || ! $userPrivileges->column || ! $userPrivileges->isReload) {
            return;
        }

        $this->dbi->selectDb('mysql');

        // For Table Specific privileges
        $queryTableSpecificOld = 'SELECT * FROM '
            . Util::backquote('tables_priv') . ' where '
            . 'Db = "' . $oldDb . '" AND Table_name = "' . $oldTable . '";';

        $oldPrivsTable = $this->dbi->fetchResult($queryTableSpecificOld, 0);

        foreach ($oldPrivsTable as $oldPriv) {
            $newDbTablePrivsQuery = 'INSERT INTO '
                . Util::backquote('tables_priv') . ' VALUES("'
                . $oldPriv[0] . '", "' . $newDb . '", "' . $oldPriv[2] . '", "'
                . $newTable . '", "' . $oldPriv[4] . '", "' . $oldPriv[5]
                . '", "' . $oldPriv[6] . '", "' . $oldPriv[7] . '");';

            $this->dbi->query($newDbTablePrivsQuery);
        }

        // For Column Specific privileges
        $queryColSpecificOld = 'SELECT * FROM '
            . Util::backquote('columns_priv') . ' WHERE '
            . 'Db = "' . $oldDb . '" AND Table_name = "' . $oldTable . '";';

        $oldPrivsCol = $this->dbi->fetchResult($queryColSpecificOld, 0);

        foreach ($oldPrivsCol as $oldPriv) {
            $newDbColPrivsQuery = 'INSERT INTO '
                . Util::backquote('columns_priv') . ' VALUES("'
                . $oldPriv[0] . '", "' . $newDb . '", "' . $oldPriv[2] . '", "'
                . $newTable . '", "' . $oldPriv[4] . '", "' . $oldPriv[5]
                . '", "' . $oldPriv[6] . '");';

            $this->dbi->query($newDbColPrivsQuery);
        }

        // Finally FLUSH the new privileges
        $this->dbi->tryQuery('FLUSH PRIVILEGES;');
    }

    /**
     * Change all collations and character sets of all columns in table
     *
     * @param string $db             Database name
     * @param string $table          Table name
     * @param string $tableCollation Collation Name
     */
    public function changeAllColumnsCollation(string $db, string $table, string $tableCollation): void
    {
        $this->dbi->selectDb($db);

        $changeAllCollationsQuery = 'ALTER TABLE '
            . Util::backquote($table)
            . ' CONVERT TO';

        [$charset] = explode('_', $tableCollation);

        $changeAllCollationsQuery .= ' CHARACTER SET ' . $charset
            . ($charset === $tableCollation ? '' : ' COLLATE ' . $tableCollation);

        $this->dbi->query($changeAllCollationsQuery);
    }

    /**
     * Move or copy a table
     *
     * @param string $db    current database name
     * @param string $table current table name
     */
    public function moveOrCopyTable(UserPrivileges $userPrivileges, string $db, string $table): Message
    {
        /**
         * Selects the database to work with
         */
        $this->dbi->selectDb($db);

        /**
         * $_POST['target_db'] could be empty in case we came from an input field
         * (when there are many databases, no drop-down)
         */
        $targetDb = $db;
        if (isset($_POST['target_db']) && is_string($_POST['target_db']) && $_POST['target_db'] !== '') {
            $targetDb = $_POST['target_db'];
        }

        /**
         * A target table name has been sent to this script -> do the work
         */
        if (isset($_POST['new_name']) && is_scalar($_POST['new_name']) && (string) $_POST['new_name'] !== '') {
            if ($db === $targetDb && $table === $_POST['new_name']) {
                if (isset($_POST['submit_move'])) {
                    $message = Message::error(__('Can\'t move table to same one!'));
                } else {
                    $message = Message::error(__('Can\'t copy table to same one!'));
                }
            } else {
                $move = isset($_POST['submit_move']);
                $this->tableMover->moveCopy(
                    $db,
                    $table,
                    $targetDb,
                    (string) $_POST['new_name'],
                    $move ? MoveScope::Move : MoveScope::from($_POST['what']),
                    MoveMode::SingleTable,
                    isset($_POST['drop_if_exists']) && $_POST['drop_if_exists'] === 'true',
                );

                if (! empty($_POST['adjust_privileges'])) {
                    if (isset($_POST['submit_move'])) {
                        $this->adjustPrivilegesRenameOrMoveTable(
                            $userPrivileges,
                            $db,
                            $table,
                            $targetDb,
                            (string) $_POST['new_name'],
                        );
                    } else {
                        $this->adjustPrivilegesCopyTable(
                            $userPrivileges,
                            $db,
                            $table,
                            $targetDb,
                            (string) $_POST['new_name'],
                        );
                    }

                    if (isset($_POST['submit_move'])) {
                        $message = Message::success(
                            __(
                                'Table %s has been moved to %s. Privileges have been adjusted.',
                            ),
                        );
                    } else {
                        $message = Message::success(
                            __(
                                'Table %s has been copied to %s. Privileges have been adjusted.',
                            ),
                        );
                    }
                } elseif (isset($_POST['submit_move'])) {
                    $message = Message::success(
                        __('Table %s has been moved to %s.'),
                    );
                } else {
                    $message = Message::success(
                        __('Table %s has been copied to %s.'),
                    );
                }

                $old = Util::backquote($db) . '.'
                    . Util::backquote($table);
                $message->addParam($old);

                $newName = (string) $_POST['new_name'];
                if ($this->dbi->getLowerCaseNames() === 1) {
                    $newName = strtolower($newName);
                }

                Current::$table = $newName;

                $new = Util::backquote($targetDb) . '.'
                    . Util::backquote($newName);
                $message->addParam($new);
            }
        } else {
            /**
             * No new name for the table!
             */
            $message = Message::error(__('The table name is empty!'));
        }

        return $message;
    }
}
