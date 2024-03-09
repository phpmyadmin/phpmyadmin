<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ConnectionException;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DbalInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Dbal\Statement;
use PhpMyAdmin\Dbal\Warning;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Query\Cache;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Routing\Routing;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Utils\SessionCache;
use stdClass;

use function __;
use function array_column;
use function array_combine;
use function array_diff;
use function array_keys;
use function array_map;
use function array_multisort;
use function array_reverse;
use function array_shift;
use function array_slice;
use function basename;
use function closelog;
use function defined;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function mb_strtolower;
use function microtime;
use function openlog;
use function reset;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stripos;
use function strnatcasecmp;
use function strtolower;
use function strtoupper;
use function strtr;
use function substr;
use function syslog;
use function trigger_error;
use function uasort;
use function uksort;
use function usort;

use const E_USER_WARNING;
use const LOG_INFO;
use const LOG_NDELAY;
use const LOG_PID;
use const LOG_USER;
use const SORT_ASC;
use const SORT_DESC;

/**
 * Main interface for database interactions
 */
class DatabaseInterface implements DbalInterface
{
    public static self|null $instance = null;

    /**
     * Force STORE_RESULT method, ignored by classic MySQL.
     */
    public const QUERY_BUFFERED = 0;

    /**
     * Do not read all rows immediately.
     */
    public const QUERY_UNBUFFERED = 2;

    /**
     * Get session variable.
     */
    public const GETVAR_SESSION = 1;

    /**
     * Get global variable.
     */
    public const GETVAR_GLOBAL = 2;

    /**
     * Opened database connections.
     *
     * @var array<int, Connection>
     * @psalm-var array<value-of<ConnectionType>, Connection>
     */
    private array $connections = [];

    /** @var array<int, string>|null */
    private array|null $currentUserAndHost = null;

    /** @var array<int, array<int, string>>|null Current role and host cache */
    private array|null $currentRoleAndHost = null;

    /**
     * @var int|null lower_case_table_names value cache
     * @psalm-var 0|1|2|null
     */
    private int|null $lowerCaseTableNames = null;

    /** @var bool Whether connection is MariaDB */
    private bool $isMariaDb = false;
    /** @var bool Whether connection is Percona */
    private bool $isPercona = false;
    /** @var int Server version as number */
    private int $versionInt = 55000;
    /** @var string Server version */
    private string $versionString = '5.50.0';
    /** @var string Server version comment */
    private string $versionComment = '';

    /** @var Types MySQL types data */
    public Types $types;

    private Cache $cache;

    public float $lastQueryExecutionTime = 0;

    private ListDatabase|null $databaseList = null;
    private readonly Config $config;

    /** @param DbiExtension $extension Object to be used for database queries */
    public function __construct(private DbiExtension $extension)
    {
        if (defined('TESTSUITE')) {
            $this->connections[ConnectionType::User->value] = new Connection(new stdClass());
            $this->connections[ConnectionType::ControlUser->value] = new Connection(new stdClass());
        }

        $this->cache = new Cache();
        $this->types = new Types($this);
        $this->config = Config::getInstance();
    }

    /** @deprecated Use dependency injection instead. */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(new DbiMysqli());
        }

        return self::$instance;
    }

    /**
     * runs a query
     *
     * @param string $query             SQL query to execute
     * @param int    $options           optional query options
     * @param bool   $cacheAffectedRows whether to cache affected rows
     */
    public function query(
        string $query,
        ConnectionType $connectionType = ConnectionType::User,
        int $options = self::QUERY_BUFFERED,
        bool $cacheAffectedRows = true,
    ): ResultInterface {
        $result = $this->tryQuery($query, $connectionType, $options, $cacheAffectedRows);

        if (! $result) {
            // The following statement will exit
            Generator::mysqlDie($this->getError($connectionType), $query);

            ResponseRenderer::getInstance()->callExit();
        }

        return $result;
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * runs a query and returns the result
     *
     * @param string $query             query to run
     * @param int    $options           if DatabaseInterface::QUERY_UNBUFFERED
     *                                  is provided, it will instruct the extension
     *                                  to use unbuffered mode
     * @param bool   $cacheAffectedRows whether to cache affected row
     */
    public function tryQuery(
        string $query,
        ConnectionType $connectionType = ConnectionType::User,
        int $options = self::QUERY_BUFFERED,
        bool $cacheAffectedRows = true,
    ): ResultInterface|false {
        $debug = isset($this->config->settings['DBG']) && $this->config->settings['DBG']['sql'];
        if (! isset($this->connections[$connectionType->value])) {
            return false;
        }

        $time = microtime(true);

        $result = $this->extension->realQuery($query, $this->connections[$connectionType->value], $options);

        if ($connectionType === ConnectionType::User) {
            $this->lastQueryExecutionTime = microtime(true) - $time;
        }

        if ($cacheAffectedRows) {
            $GLOBALS['cached_affected_rows'] = $this->affectedRows($connectionType, false);
        }

        if ($debug) {
            $errorMessage = $this->getError($connectionType);
            Utilities::debugLogQueryIntoSession(
                $query,
                $errorMessage !== '' ? $errorMessage : null,
                $result,
                $this->lastQueryExecutionTime,
            );
            if ($this->config->settings['DBG']['sqllog']) {
                openlog('phpMyAdmin', LOG_NDELAY | LOG_PID, LOG_USER);

                syslog(
                    LOG_INFO,
                    sprintf(
                        'SQL[%s?route=%s]: %0.3f(W:%d,C:%s,L:0x%02X) > %s',
                        basename($_SERVER['SCRIPT_NAME']),
                        Routing::$route,
                        $this->lastQueryExecutionTime,
                        $this->getWarningCount($connectionType),
                        $cacheAffectedRows ? 'y' : 'n',
                        $connectionType->value,
                        $query,
                    ),
                );
                closelog();
            }
        }

        if ($result !== false && Tracker::isActive()) {
            Tracker::handleQuery($query);
        }

        return $result;
    }

    /**
     * Send multiple SQL queries to the database server and execute the first one
     *
     * @param string $multiQuery multi query statement to execute
     */
    public function tryMultiQuery(
        string $multiQuery = '',
        ConnectionType $connectionType = ConnectionType::User,
    ): bool {
        if (! isset($this->connections[$connectionType->value])) {
            return false;
        }

        return $this->extension->realMultiQuery($this->connections[$connectionType->value], $multiQuery);
    }

    /**
     * Executes a query as controluser.
     * The result is always buffered and never cached
     *
     * @param string $sql the query to execute
     *
     * @return ResultInterface the result set
     */
    public function queryAsControlUser(string $sql): ResultInterface
    {
        // Avoid caching of the number of rows affected; for example, this function
        // is called for tracking purposes but we want to display the correct number
        // of rows affected by the original query, not by the query generated for
        // tracking.
        return $this->query($sql, ConnectionType::ControlUser, self::QUERY_BUFFERED, false);
    }

    /**
     * Executes a query as controluser.
     * The result is always buffered and never cached
     *
     * @param string $sql the query to execute
     *
     * @return ResultInterface|false the result set, or false if the query failed
     */
    public function tryQueryAsControlUser(string $sql): ResultInterface|false
    {
        // Avoid caching of the number of rows affected; for example, this function
        // is called for tracking purposes but we want to display the correct number
        // of rows affected by the original query, not by the query generated for
        // tracking.
        return $this->tryQuery($sql, ConnectionType::ControlUser, self::QUERY_BUFFERED, false);
    }

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     *
     * @return array<int, string>   tables names
     */
    public function getTables(string $database, ConnectionType $connectionType = ConnectionType::User): array
    {
        if ($database === '') {
            return [];
        }

        /** @var array<int, string> $tables */
        $tables = $this->fetchResult(
            'SHOW TABLES FROM ' . Util::backquote($database) . ';',
            null,
            0,
            $connectionType,
        );
        if ($this->config->settings['NaturalOrder']) {
            usort($tables, strnatcasecmp(...));
        }

        return $tables;
    }

    /**
     * returns array of all tables in given db or dbs
     * this function expects unquoted names:
     * RIGHT: my_database
     * WRONG: `my_database`
     * WRONG: my\_database
     * if $tbl_is_group is true, $table is used as filter for table names
     *
     * <code>
     * $dbi->getTablesFull('my_database');
     * $dbi->getTablesFull('my_database', 'my_table'));
     * $dbi->getTablesFull('my_database', 'my_tables_', true));
     * </code>
     *
     * @param string         $database     database
     * @param string|mixed[] $table        table name(s)
     * @param bool           $tableIsGroup $table is a table group
     * @param int            $limitOffset  zero-based offset for the count
     * @param bool|int       $limitCount   number of tables to return
     * @param string         $sortBy       table attribute to sort by
     * @param string         $sortOrder    direction to sort (ASC or DESC)
     * @param string|null    $tableType    whether table or view
     *
     * @return (string|int|null)[][]           list of tables in given db(s)
     *
     * @todo    move into Table
     */
    public function getTablesFull(
        string $database,
        string|array $table = '',
        bool $tableIsGroup = false,
        int $limitOffset = 0,
        bool|int $limitCount = false,
        string $sortBy = 'Name',
        string $sortOrder = 'ASC',
        string|null $tableType = null,
        ConnectionType $connectionType = ConnectionType::User,
    ): array {
        if ($limitCount === true) {
            $limitCount = $this->config->settings['MaxTableList'];
        }

        $tables = [];
        $pagingApplied = false;

        if ($limitCount && is_array($table) && $sortBy === 'Name') {
            if ($sortOrder === 'DESC') {
                $table = array_reverse($table);
            }

            $table = array_slice($table, $limitOffset, $limitCount);
            $pagingApplied = true;
        }

        if (! $this->config->selectedServer['DisableIS']) {
            $sqlWhereTable = '';
            if ($table !== [] && $table !== '') {
                if (is_array($table)) {
                    $sqlWhereTable = QueryGenerator::getTableNameConditionForMultiple(
                        array_map($this->quoteString(...), $table),
                    );
                } else {
                    $sqlWhereTable = QueryGenerator::getTableNameCondition(
                        $this->quoteString($tableIsGroup ? $this->escapeMysqlWildcards($table) : $table),
                        $tableIsGroup,
                    );
                }
            }

            $sqlWhereTable .= QueryGenerator::getTableTypeCondition($tableType);

            // for PMA bc:
            // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
            //
            // on non-Windows servers,
            // added BINARY in the WHERE clause to force a case sensitive
            // comparison (if we are looking for the db Aa we don't want
            // to find the db aa)

            $sql = QueryGenerator::getSqlForTablesFull($this->quoteString($database), $sqlWhereTable);

            // Sort the tables
            $sql .= ' ORDER BY ' . $sortBy . ' ' . $sortOrder;

            if ($limitCount && ! $pagingApplied) {
                $sql .= ' LIMIT ' . $limitCount . ' OFFSET ' . $limitOffset;
            }

            /** @var (string|int|null)[][][] $tables */
            $tables = $this->fetchResult(
                $sql,
                ['TABLE_SCHEMA', 'TABLE_NAME'],
                null,
                $connectionType,
            );

            // here, we check for Mroonga engine and compute the good data_length and index_length
            // in the StructureController only we need to sum the two values as the other engines
            foreach ($tables as $oneDatabaseName => $oneDatabaseTables) {
                foreach ($oneDatabaseTables as $oneTableName => $oneTableData) {
                    if ($oneTableData['Engine'] !== 'Mroonga') {
                        continue;
                    }

                    if (! StorageEngine::hasMroongaEngine()) {
                        continue;
                    }

                    [
                        $tables[$oneDatabaseName][$oneTableName]['Data_length'],
                        $tables[$oneDatabaseName][$oneTableName]['Index_length'],
                    ] = StorageEngine::getMroongaLengths((string) $oneDatabaseName, (string) $oneTableName);
                }
            }

            if ($sortBy === 'Name' && $this->config->settings['NaturalOrder']) {
                // here, the array's first key is by schema name
                foreach ($tables as $oneDatabaseName => $oneDatabaseTables) {
                    uksort($oneDatabaseTables, strnatcasecmp(...));

                    if ($sortOrder === 'DESC') {
                        $oneDatabaseTables = array_reverse($oneDatabaseTables);
                    }

                    $tables[$oneDatabaseName] = $oneDatabaseTables;
                }
            } elseif ($sortBy === 'Data_length') {
                // Size = Data_length + Index_length
                foreach ($tables as $oneDatabaseName => $oneDatabaseTables) {
                    uasort(
                        $oneDatabaseTables,
                        static function (array $a, array $b): int {
                            $aLength = $a['Data_length'] + $a['Index_length'];
                            $bLength = $b['Data_length'] + $b['Index_length'];

                            return $aLength <=> $bLength;
                        },
                    );

                    if ($sortOrder === 'DESC') {
                        $oneDatabaseTables = array_reverse($oneDatabaseTables);
                    }

                    $tables[$oneDatabaseName] = $oneDatabaseTables;
                }
            }

            // on windows with lower_case_table_names = 1
            // MySQL returns
            // with SHOW DATABASES or information_schema.SCHEMATA: `Test`
            // but information_schema.TABLES gives `test`
            // see https://github.com/phpmyadmin/phpmyadmin/issues/8402
            $tables = $tables[$database]
                ?? $tables[mb_strtolower($database)]
                ?? [];
        }

        // If permissions are wrong on even one database directory,
        // information_schema does not return any table info for any database
        // this is why we fall back to SHOW TABLE STATUS even for MySQL >= 50002
        if ($tables === []) {
            $sql = 'SHOW TABLE STATUS FROM ' . Util::backquote($database);
            if (($table !== '' && $table !== []) || $tableIsGroup || ($tableType !== null && $tableType !== '')) {
                $sql .= ' WHERE';
                $needAnd = false;
                if (($table !== '' && $table !== []) || $tableIsGroup) {
                    if (is_array($table)) {
                        $sql .= ' `Name` IN ('
                            . implode(
                                ', ',
                                array_map(
                                    fn (string $string): string => $this->quoteString($string, $connectionType),
                                    $table,
                                ),
                            ) . ')';
                    } else {
                        $sql .= ' `Name` LIKE '
                            . $this->quoteString($this->escapeMysqlWildcards($table) . '%', $connectionType);
                    }

                    $needAnd = true;
                }

                if ($tableType !== null && $tableType !== '') {
                    if ($needAnd) {
                        $sql .= ' AND';
                    }

                    if ($tableType === 'view') {
                        $sql .= " `Comment` = 'VIEW'";
                    } elseif ($tableType === 'table') {
                        $sql .= " `Comment` != 'VIEW'";
                    }
                }
            }

            /** @var (string|int|null)[][] $eachTables */
            $eachTables = $this->fetchResult($sql, 'Name', null, $connectionType);

            // here, we check for Mroonga engine and compute the good data_length and index_length
            // in the StructureController only we need to sum the two values as the other engines
            foreach ($eachTables as $tableName => $tableData) {
                if ($tableData['Engine'] !== 'Mroonga') {
                    continue;
                }

                if (! StorageEngine::hasMroongaEngine()) {
                    continue;
                }

                [
                    $eachTables[$tableName]['Data_length'],
                    $eachTables[$tableName]['Index_length'],
                ] = StorageEngine::getMroongaLengths($database, (string) $tableName);
            }

            // Sort naturally if the config allows it and we're sorting
            // the Name column.
            if ($sortBy === 'Name' && $this->config->settings['NaturalOrder']) {
                uksort($eachTables, strnatcasecmp(...));

                if ($sortOrder === 'DESC') {
                    $eachTables = array_reverse($eachTables);
                }
            } else {
                // Prepare to sort by creating array of the selected sort
                // value to pass to array_multisort

                // Size = Data_length + Index_length
                $sortValues = [];
                if ($sortBy === 'Data_length') {
                    foreach ($eachTables as $tableName => $tableData) {
                        $sortValues[$tableName] = strtolower(
                            (string) ($tableData['Data_length']
                            + $tableData['Index_length']),
                        );
                    }
                } else {
                    foreach ($eachTables as $tableName => $tableData) {
                        $sortValues[$tableName] = strtolower($tableData[$sortBy] ?? '');
                    }
                }

                if ($sortValues !== []) {
                    // See https://stackoverflow.com/a/32461188 for the explanation of below hack
                    $keys = array_keys($eachTables);
                    if ($sortOrder === 'DESC') {
                        array_multisort($sortValues, SORT_DESC, $eachTables, $keys);
                    } else {
                        array_multisort($sortValues, SORT_ASC, $eachTables, $keys);
                    }

                    $eachTables = array_combine($keys, $eachTables);
                }

                // cleanup the temporary sort array
                unset($sortValues);
            }

            if ($limitCount && ! $pagingApplied) {
                $eachTables = array_slice($eachTables, $limitOffset, $limitCount, true);
            }

            $tables = Compatibility::getISCompatForGetTablesFull($eachTables, $database);
        }

        if ($tables !== []) {
            // cache table data, so Table does not require to issue SHOW TABLE STATUS again
            $this->cache->cacheTableData($database, $tables);
        }

        return $tables;
    }

    /**
     * returns array with databases containing extended infos about them
     *
     * @param string|null $database    database
     * @param bool        $forceStats  retrieve stats also for MySQL < 5
     * @param string      $sortBy      column to order by
     * @param string      $sortOrder   ASC or DESC
     * @param int         $limitOffset starting offset for LIMIT
     * @param bool|int    $limitCount  row count for LIMIT or true for $cfg['MaxDbList']
     *
     * @return mixed[]
     *
     * @todo    move into ListDatabase?
     */
    public function getDatabasesFull(
        string|null $database = null,
        bool $forceStats = false,
        ConnectionType $connectionType = ConnectionType::User,
        string $sortBy = 'SCHEMA_NAME',
        string $sortOrder = 'ASC',
        int $limitOffset = 0,
        bool|int $limitCount = false,
    ): array {
        $sortOrder = strtoupper($sortOrder);

        if ($limitCount === true) {
            $limitCount = $this->config->settings['MaxDbList'];
        }

        $applyLimitAndOrderManual = true;

        if (! $this->config->selectedServer['DisableIS']) {
            /**
             * if NaturalOrder config is enabled, we cannot use LIMIT
             * cause MySQL does not support natural ordering,
             * we have to do it afterward
             */
            $limit = '';
            if (! $this->config->settings['NaturalOrder']) {
                if ($limitCount) {
                    $limit = ' LIMIT ' . $limitCount . ' OFFSET ' . $limitOffset;
                }

                $applyLimitAndOrderManual = false;
            }

            // get table information from information_schema
            $sqlWhereSchema = '';
            if ($database !== null) {
                $sqlWhereSchema = 'WHERE `SCHEMA_NAME` LIKE ' . $this->quoteString($database, $connectionType);
            }

            $sql = QueryGenerator::getInformationSchemaDatabasesFullRequest(
                $forceStats,
                $sqlWhereSchema,
                $sortBy,
                $sortOrder,
                $limit,
            );

            $databases = $this->fetchResult($sql, 'SCHEMA_NAME', null, $connectionType);

            $mysqlError = $this->getError($connectionType);
            if ($databases === [] && isset($GLOBALS['errno'])) {
                Generator::mysqlDie($mysqlError, $sql);
            }

            // display only databases also in official database list
            // f.e. to apply hide_db and only_db
            $drops = array_diff(
                array_keys($databases),
                (array) $this->getDatabaseList(),
            );
            foreach ($drops as $drop) {
                unset($databases[$drop]);
            }
        } else {
            $databases = [];
            foreach ($this->getDatabaseList() as $databaseName) {
                // Compatibility with INFORMATION_SCHEMA output
                $databases[$databaseName]['SCHEMA_NAME'] = $databaseName;

                $databases[$databaseName]['DEFAULT_COLLATION_NAME'] = $this->getDbCollation($databaseName);

                if (! $forceStats) {
                    continue;
                }

                // get additional info about tables
                $databases[$databaseName]['SCHEMA_TABLES'] = 0;
                $databases[$databaseName]['SCHEMA_TABLE_ROWS'] = 0;
                $databases[$databaseName]['SCHEMA_DATA_LENGTH'] = 0;
                $databases[$databaseName]['SCHEMA_MAX_DATA_LENGTH'] = 0;
                $databases[$databaseName]['SCHEMA_INDEX_LENGTH'] = 0;
                $databases[$databaseName]['SCHEMA_LENGTH'] = 0;
                $databases[$databaseName]['SCHEMA_DATA_FREE'] = 0;

                $res = $this->query(
                    'SHOW TABLE STATUS FROM '
                    . Util::backquote($databaseName) . ';',
                );

                while ($row = $res->fetchAssoc()) {
                    $databases[$databaseName]['SCHEMA_TABLES']++;
                    $databases[$databaseName]['SCHEMA_TABLE_ROWS'] += $row['Rows'];
                    $databases[$databaseName]['SCHEMA_DATA_LENGTH'] += $row['Data_length'];
                    $databases[$databaseName]['SCHEMA_MAX_DATA_LENGTH'] += $row['Max_data_length'];
                    $databases[$databaseName]['SCHEMA_INDEX_LENGTH'] += $row['Index_length'];

                    // for InnoDB, this does not contain the number of
                    // overhead bytes but the total free space
                    if ($row['Engine'] !== 'InnoDB') {
                        $databases[$databaseName]['SCHEMA_DATA_FREE'] += $row['Data_free'];
                    }

                    $databases[$databaseName]['SCHEMA_LENGTH'] += $row['Data_length'] + $row['Index_length'];
                }

                unset($res);
            }
        }

        /**
         * apply limit and order manually now
         * (caused by older MySQL < 5 or NaturalOrder config)
         */
        if ($applyLimitAndOrderManual) {
            usort(
                $databases,
                static fn ($a, $b): int => Utilities::usortComparisonCallback($a, $b, $sortBy, $sortOrder),
            );

            /**
             * now apply limit
             */
            if ($limitCount) {
                $databases = array_slice($databases, $limitOffset, $limitCount);
            }
        }

        return $databases;
    }

    /**
     * returns detailed array with all columns for given table in database,
     * or all tables/databases
     *
     * @param string|null $database name of database
     * @param string|null $table    name of table to retrieve columns from
     * @param string|null $column   name of specific column
     *
     * @return mixed[]
     */
    public function getColumnsFull(
        string|null $database = null,
        string|null $table = null,
        string|null $column = null,
        ConnectionType $connectionType = ConnectionType::User,
    ): array {
        if (! $this->config->selectedServer['DisableIS']) {
            $sql = QueryGenerator::getInformationSchemaColumnsFullRequest(
                $database !== null ? $this->quoteString($database, $connectionType) : null,
                $table !== null ? $this->quoteString($table, $connectionType) : null,
                $column !== null ? $this->quoteString($column, $connectionType) : null,
            );
            $arrayKeys = QueryGenerator::getInformationSchemaColumns($database, $table, $column);

            return $this->fetchResult($sql, $arrayKeys, null, $connectionType);
        }

        $columns = [];
        if ($database === null) {
            foreach ($this->getDatabaseList() as $database) {
                $columns[$database] = $this->getColumnsFull($database, null, null, $connectionType);
            }

            return $columns;
        }

        if ($table === null) {
            $tables = $this->getTables($database);
            foreach ($tables as $table) {
                $columns[$table] = $this->getColumnsFull($database, $table, null, $connectionType);
            }

            return $columns;
        }

        $sql = 'SHOW FULL COLUMNS FROM '
            . Util::backquote($database) . '.' . Util::backquote($table);
        if ($column !== null) {
            $sql .= ' LIKE ' . $this->quoteString($column, $connectionType);
        }

        $columns = $this->fetchResult($sql, 'Field', null, $connectionType);

        $columns = Compatibility::getISCompatForGetColumnsFull($columns, $database, $table);

        if ($column !== null) {
            return reset($columns);
        }

        return $columns;
    }

    /**
     * Returns description of a $column in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param string $column   name of column
     * @param T      $full     whether to return full info or only column names
     *
     * @psalm-return (T is true ? ColumnFull : Column)|null
     *
     * @template T of bool
     */
    public function getColumn(
        string $database,
        string $table,
        string $column,
        bool $full = false,
        ConnectionType $connectionType = ConnectionType::User,
    ): ColumnFull|Column|null {
        $sql = QueryGenerator::getColumnsSql(
            $database,
            $table,
            $this->quoteString($this->escapeMysqlWildcards($column)),
            $full,
        );
        /** @var (string|null)[][] $fields */
        $fields = $this->fetchResult($sql, 'Field', null, $connectionType);

        /**
         * @var array{
         *  Field: string,
         *  Type: string,
         *  Collation: string|null,
         *  Null:'YES'|'NO',
         *  Key: string,
         *  Default: string|null,
         *  Extra: string,
         *  Privileges: string,
         *  Comment: string
         * }[] $columns
         */
        $columns = $this->attachIndexInfoToColumns($database, $table, $fields);

        $columns = $this->convertToColumns($columns, $full);

        return array_shift($columns);
    }

    /**
     * Returns descriptions of columns in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param T      $full     whether to return full info or only column names
     *
     * @return ColumnFull[]|Column[]
     * @psalm-return (T is true ? ColumnFull[] : Column[])
     *
     * @template T of bool
     */
    public function getColumns(
        string $database,
        string $table,
        bool $full = false,
        ConnectionType $connectionType = ConnectionType::User,
    ): array {
        $sql = QueryGenerator::getColumnsSql($database, $table, null, $full);
        /** @var (string|null)[][] $fields */
        $fields = $this->fetchResult($sql, 'Field', null, $connectionType);

        /**
         * @var array{
         *  Field: string,
         *  Type: string,
         *  Collation: string|null,
         *  Null:'YES'|'NO',
         *  Key: string,
         *  Default: string|null,
         *  Extra: string,
         *  Privileges: string,
         *  Comment: string
         * }[] $columns
         */
        $columns = $this->attachIndexInfoToColumns($database, $table, $fields);

        return $this->convertToColumns($columns, $full);
    }

    /**
     * Attach index information to the column definition
     *
     * @param string            $database name of database
     * @param string            $table    name of table to retrieve columns from
     * @param (string|null)[][] $fields   column array indexed by their names
     *
     * @return (string|null)[][] Column defintions with index information
     */
    private function attachIndexInfoToColumns(
        string $database,
        string $table,
        array $fields,
    ): array {
        if ($fields === []) {
            return [];
        }

        // Check if column is a part of multiple-column index and set its 'Key'.
        $indexes = Index::getFromTable($this, $table, $database);
        foreach ($fields as $field => $fieldData) {
            if (! empty($fieldData['Key'])) {
                continue;
            }

            foreach ($indexes as $index) {
                if (! $index->hasColumn((string) $field)) {
                    continue;
                }

                $indexColumns = $index->getColumns();
                if ($indexColumns[$field]->getSeqInIndex() <= 1) {
                    continue;
                }

                $fields[$field]['Key'] = $index->isUnique() ? 'UNI' : 'MUL';
            }
        }

        return $fields;
    }

    /**
     * @psalm-param array{
     *  Field: string,
     *  Type: string,
     *  Collation: string|null,
     *  Null:'YES'|'NO',
     *  Key: string,
     *  Default: string|null,
     *  Extra: string,
     *  Privileges: string,
     *  Comment: string
     * }[] $fields   column array indexed by their names
     *
     * @return (ColumnFull|Column)[]
     */
    private function convertToColumns(array $fields, bool $full = false): array
    {
        $columns = [];
        foreach ($fields as $field => $column) {
            $columns[$field] = $full ? new ColumnFull(
                $column['Field'],
                $column['Type'],
                $column['Collation'],
                $column['Null'] === 'YES',
                $column['Key'],
                $column['Default'],
                $column['Extra'],
                $column['Privileges'],
                $column['Comment'],
            ) : new Column(
                $column['Field'],
                $column['Type'],
                $column['Null'] === 'YES',
                $column['Key'],
                $column['Default'],
                $column['Extra'],
            );
        }

        return $columns;
    }

    /**
     * Returns all column names in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     *
     * @return string[]
     */
    public function getColumnNames(
        string $database,
        string $table,
        ConnectionType $connectionType = ConnectionType::User,
    ): array {
        $sql = QueryGenerator::getColumnsSql($database, $table);

        // We only need the 'Field' column which contains the table's column names
        return $this->fetchResult($sql, null, 'Field', $connectionType);
    }

    /**
     * Returns indexes of a table
     *
     * @param string $database name of database
     * @param string $table    name of the table whose indexes are to be retrieved
     *
     * @return array<int, array<string, string|null>>
     * @psalm-return array<int, array{
     *   Table: string,
     *   Non_unique: '0'|'1',
     *   Key_name: string,
     *   Seq_in_index: string,
     *   Column_name: string|null,
     *   Collation: 'A'|'D'|null,
     *   Cardinality: string,
     *   Sub_part: string|null,
     *   Packed: string|null,
     *   Null: string|null,
     *   Index_type: 'BTREE'|'FULLTEXT'|'HASH'|'RTREE',
     *   Comment: string,
     *   Index_comment: string,
     *   Ignored?: string,
     *   Visible?: string,
     *   Expression?: string|null
     * }>
     */
    public function getTableIndexes(
        string $database,
        string $table,
        ConnectionType $connectionType = ConnectionType::User,
    ): array {
        $sql = QueryGenerator::getTableIndexesSql($database, $table);

        return $this->fetchResult($sql, null, null, $connectionType);
    }

    /**
     * returns value of given mysql server variable
     *
     * @param string $var  mysql server variable name
     * @param int    $type DatabaseInterface::GETVAR_SESSION | DatabaseInterface::GETVAR_GLOBAL
     *
     * @return false|string|null value for mysql server variable
     */
    public function getVariable(
        string $var,
        int $type = self::GETVAR_SESSION,
        ConnectionType $connectionType = ConnectionType::User,
    ): false|string|null {
        $modifier = match ($type) {
            self::GETVAR_SESSION => ' SESSION',
            self::GETVAR_GLOBAL => ' GLOBAL',
            default => '',
        };

        return $this->fetchValue('SHOW' . $modifier . ' VARIABLES LIKE \'' . $var . '\';', 1, $connectionType);
    }

    /**
     * Sets new value for a variable if it is different from the current value
     *
     * @param string $var   variable name
     * @param string $value value to set
     */
    public function setVariable(
        string $var,
        string $value,
        ConnectionType $connectionType = ConnectionType::User,
    ): bool {
        $currentValue = $this->getVariable($var, self::GETVAR_SESSION, $connectionType);
        if ($currentValue == $value) {
            return true;
        }

        return (bool) $this->query('SET ' . $var . ' = ' . $value . ';', $connectionType);
    }

    public function getDefaultCharset(): string
    {
        return $this->versionInt > 50503 ? 'utf8mb4' : 'utf8';
    }

    public function getDefaultCollation(): string
    {
        return $this->versionInt > 50503 ? 'utf8mb4_general_ci' : 'utf8_general_ci';
    }

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnect(Server $currentServer): void
    {
        $version = $this->fetchSingleRow('SELECT @@version, @@version_comment');

        if (is_array($version)) {
            $this->setVersion($version);
        }

        $this->query(
            sprintf('SET NAMES \'%s\' COLLATE \'%s\';', $this->getDefaultCharset(), $this->getDefaultCollation()),
        );

        /* Locale for messages */
        $locale = LanguageManager::getInstance()->getCurrentLanguage()->getMySQLLocale();
        if ($locale !== '') {
            $this->query("SET lc_messages = '" . $locale . "';");
        }

        // Set timezone for the session, if required.
        if ($currentServer->sessionTimeZone !== '') {
            $sqlQueryTz = 'SET ' . Util::backquote('time_zone') . ' = '
                . $this->quoteString($currentServer->sessionTimeZone);

            if (! $this->tryQuery($sqlQueryTz)) {
                $errorMessageTz = sprintf(
                    __(
                        'Unable to use timezone "%1$s" for server %2$d. '
                        . 'Please check your configuration setting for '
                        . '[em]$cfg[\'Servers\'][%3$d][\'SessionTimeZone\'][/em]. '
                        . 'phpMyAdmin is currently using the default time zone '
                        . 'of the database server.',
                    ),
                    $currentServer->sessionTimeZone,
                    Current::$server,
                    Current::$server,
                );

                trigger_error($errorMessageTz, E_USER_WARNING);
            }
        }

        /* Loads closest context to this version. */
        Context::loadClosest(($this->isMariaDb ? 'MariaDb' : 'MySql') . $this->versionInt);

        $this->databaseList = null;
    }

    /**
     * Sets collation connection for user link
     *
     * @param string $collation collation to set
     */
    public function setCollation(string $collation): void
    {
        $charset = $this->getDefaultCharset();
        /* Automatically adjust collation if not supported by server */
        if ($charset === 'utf8' && str_starts_with($collation, 'utf8mb4_')) {
            $collation = 'utf8_' . substr($collation, 8);
        }

        $result = $this->tryQuery(
            'SET collation_connection = '
            . $this->quoteString($collation)
            . ';',
        );

        if ($result === false) {
            trigger_error(
                __('Failed to set configured collation connection!'),
                E_USER_WARNING,
            );

            return;
        }
    }

    /**
     * returns a single value from the given result or query,
     * if the query or the result has more than one row or field
     * the first field of the first row is returned
     *
     * <code>
     * $sql = 'SELECT `name` FROM `user` WHERE `id` = 123';
     * $user_name = $dbi->fetchValue($sql);
     * // produces
     * // $user_name = 'John Doe'
     * </code>
     *
     * @param string     $query The query to execute
     * @param int|string $field field to fetch the value from, starting at 0, with 0 being default
     *
     * @return string|false|null value of first field in first row from result or false if not found
     */
    public function fetchValue(
        string $query,
        int|string $field = 0,
        ConnectionType $connectionType = ConnectionType::User,
    ): string|false|null {
        $result = $this->tryQuery($query, $connectionType, self::QUERY_BUFFERED, false);
        if ($result === false) {
            return false;
        }

        return $result->fetchValue($field);
    }

    /**
     * Returns only the first row from the result or null if result is empty.
     *
     * <code>
     * $sql = 'SELECT * FROM `user` WHERE `id` = 123';
     * $user = $dbi->fetchSingleRow($sql);
     * // produces
     * // $user = array('id' => 123, 'name' => 'John Doe')
     * </code>
     *
     * @param string $query The query to execute
     * @param string $type  NUM|ASSOC|BOTH returned array should either numeric associative or both
     * @psalm-param DatabaseInterface::FETCH_NUM|DatabaseInterface::FETCH_ASSOC $type
     *
     * @return array<string|null>|null
     */
    public function fetchSingleRow(
        string $query,
        string $type = DbalInterface::FETCH_ASSOC,
        ConnectionType $connectionType = ConnectionType::User,
    ): array|null {
        $result = $this->tryQuery($query, $connectionType, self::QUERY_BUFFERED, false);
        if ($result === false) {
            return null;
        }

        return $this->fetchByMode($result, $type) ?: null;
    }

    /**
     * Returns row or element of a row
     *
     * @param mixed[]|string  $row   Row to process
     * @param string|int|null $value Which column to return
     */
    private function fetchValueOrValueByIndex(array|string $row, string|int|null $value): mixed
    {
        return $value === null ? $row : $row[$value];
    }

    /**
     * returns array of rows with numeric or associative keys
     *
     * @param ResultInterface $result result set identifier
     * @param string          $mode   either self::FETCH_NUM, self::FETCH_ASSOC or self::FETCH_BOTH
     * @psalm-param self::FETCH_NUM|self::FETCH_ASSOC $mode
     *
     * @return array<string|null>
     */
    private function fetchByMode(ResultInterface $result, string $mode): array
    {
        return $mode === self::FETCH_NUM ? $result->fetchRow() : $result->fetchAssoc();
    }

    /**
     * returns all rows in the resultset in one array
     *
     * <code>
     * $sql = 'SELECT * FROM `user`';
     * $users = $dbi->fetchResult($sql);
     * // produces
     * // $users[] = array('id' => 123, 'name' => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $dbi->fetchResult($sql, 'id');
     * // produces
     * // $users['123'] = array('id' => 123, 'name' => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $dbi->fetchResult($sql, 0);
     * // produces
     * // $users['123'] = array(0 => 123, 1 => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $dbi->fetchResult($sql, 'id', 'name');
     * // or
     * $users = $dbi->fetchResult($sql, 0, 1);
     * // produces
     * // $users['123'] = 'John Doe'
     *
     * $sql = 'SELECT `name` FROM `user`';
     * $users = $dbi->fetchResult($sql);
     * // produces
     * // $users[] = 'John Doe'
     *
     * $sql = 'SELECT `group`, `name` FROM `user`'
     * $users = $dbi->fetchResult($sql, array('group', null), 'name');
     * // produces
     * // $users['admin'][] = 'John Doe'
     *
     * $sql = 'SELECT `group`, `name` FROM `user`'
     * $users = $dbi->fetchResult($sql, array('group', 'name'), 'id');
     * // produces
     * // $users['admin']['John Doe'] = '123'
     * </code>
     *
     * @param string                  $query query to execute
     * @param string|int|mixed[]|null $key   field-name or offset
     *                                     used as key for array
     *                                     or array of those
     * @param string|int|null         $value value-name or offset used as value for array
     *
     * @return mixed[] resultrows or values indexed by $key
     */
    public function fetchResult(
        string $query,
        string|int|array|null $key = null,
        string|int|null $value = null,
        ConnectionType $connectionType = ConnectionType::User,
    ): array {
        $resultRows = [];

        $result = $this->tryQuery($query, $connectionType, self::QUERY_BUFFERED, false);

        // return empty array if result is empty or false
        if ($result === false) {
            return [];
        }

        if ($key === null) {
            // no nested array if only one field is in result
            if ($value === 0 || $result->numFields() === 1) {
                return $result->fetchAllColumn();
            }

            return $value === null ? $result->fetchAllAssoc() : array_column($result->fetchAllAssoc(), $value);
        }

        if (is_array($key)) {
            while ($row = $result->fetchAssoc()) {
                $resultTarget =& $resultRows;
                foreach ($key as $keyIndex) {
                    if ($keyIndex === null) {
                        $resultTarget =& $resultTarget[];
                        continue;
                    }

                    if (! isset($resultTarget[$row[$keyIndex]])) {
                        $resultTarget[$row[$keyIndex]] = [];
                    }

                    $resultTarget =& $resultTarget[$row[$keyIndex]];
                }

                $resultTarget = $this->fetchValueOrValueByIndex($row, $value);
            }

            return $resultRows;
        }

        if ($key === 0 && $value === 1) {
            return $result->fetchAllKeyPair();
        }

        // if $key is an integer use non associative mysql fetch function
        $fetchFunction = is_int($key) ? self::FETCH_NUM : self::FETCH_ASSOC;

        while ($row = $this->fetchByMode($result, $fetchFunction)) {
            $resultRows[$row[$key]] = $this->fetchValueOrValueByIndex($row, $value);
        }

        return $resultRows;
    }

    /**
     * Get supported SQL compatibility modes
     *
     * @return string[] supported SQL compatibility modes
     */
    public function getCompatibilities(): array
    {
        return [
            'NONE',
            'ANSI',
            'DB2',
            'MAXDB',
            'MYSQL323',
            'MYSQL40',
            'MSSQL',
            'ORACLE',
            // removed; in MySQL 5.0.33, this produces exports that
            // can't be read by POSTGRESQL (see our bug #1596328)
            // 'POSTGRESQL',
            'TRADITIONAL',
        ];
    }

    /**
     * returns warnings for last query
     *
     * @return Warning[] warnings
     */
    public function getWarnings(ConnectionType $connectionType = ConnectionType::User): array
    {
        $result = $this->tryQuery('SHOW WARNINGS', $connectionType, 0, false);
        if ($result === false) {
            return [];
        }

        $warnings = [];
        while ($row = $result->fetchAssoc()) {
            $warnings[] = Warning::fromArray($row);
        }

        return $warnings;
    }

    /**
     * gets the current user with host
     *
     * @return string the current user i.e. user@host
     */
    public function getCurrentUser(): string
    {
        if (SessionCache::has('mysql_cur_user')) {
            return SessionCache::get('mysql_cur_user');
        }

        $user = $this->fetchValue('SELECT CURRENT_USER();');
        if ($user !== false) {
            SessionCache::set('mysql_cur_user', $user);

            return $user;
        }

        return '@';
    }

    /**
     * gets the current role with host. Role maybe multiple separated by comma
     * Support start from MySQL 8.x / MariaDB 10.0.5
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/roles.html
     * @see https://dev.mysql.com/doc/refman/8.0/en/information-functions.html#function_current-role
     * @see https://mariadb.com/kb/en/mariadb-1005-release-notes/#newly-implemented-features
     * @see https://mariadb.com/kb/en/roles_overview/
     *
     * @return array<int, array<int, string>> the current roles i.e. array of role@host
     */
    public function getCurrentRoles(): array
    {
        if (($this->isMariaDB() && $this->getVersion() < 100500) || $this->getVersion() < 80000) {
            return [];
        }

        if (SessionCache::has('mysql_cur_role')) {
            return SessionCache::get('mysql_cur_role');
        }

        $role = $this->fetchValue('SELECT CURRENT_ROLE();');
        if ($role === false || $role === null || $role === 'NONE') {
            return [];
        }

        $role = array_map('trim', explode(',', str_replace('`', '', $role)));
        SessionCache::set('mysql_cur_role', $role);

        return $role;
    }

    public function isSuperUser(): bool
    {
        if (SessionCache::has('is_superuser')) {
            return (bool) SessionCache::get('is_superuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $isSuperUser = (bool) $this->fetchValue('SELECT 1 FROM mysql.user LIMIT 1');

        SessionCache::set('is_superuser', $isSuperUser);

        return $isSuperUser;
    }

    public function isGrantUser(): bool
    {
        if (SessionCache::has('is_grantuser')) {
            return (bool) SessionCache::get('is_grantuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $hasGrantPrivilege = false;

        if ($this->config->selectedServer['DisableIS']) {
            $grants = $this->getCurrentUserGrants();

            foreach ($grants as $grant) {
                if (str_contains($grant, 'WITH GRANT OPTION')) {
                    $hasGrantPrivilege = true;
                    break;
                }
            }

            SessionCache::set('is_grantuser', $hasGrantPrivilege);

            return $hasGrantPrivilege;
        }

        [$user, $host] = $this->getCurrentUserAndHost();
        $query = QueryGenerator::getInformationSchemaDataForGranteeRequest($user, $host);
        $hasGrantPrivilege = (bool) $this->fetchValue($query);

        if (! $hasGrantPrivilege) {
            foreach ($this->getCurrentRolesAndHost() as [$role, $roleHost]) {
                $query = QueryGenerator::getInformationSchemaDataForGranteeRequest($role, $roleHost ?? '');
                $hasGrantPrivilege = (bool) $this->fetchValue($query);

                if ($hasGrantPrivilege) {
                    break;
                }
            }
        }

        SessionCache::set('is_grantuser', $hasGrantPrivilege);

        return $hasGrantPrivilege;
    }

    public function isCreateUser(): bool
    {
        if (SessionCache::has('is_createuser')) {
            return (bool) SessionCache::get('is_createuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $hasCreatePrivilege = false;

        if ($this->config->selectedServer['DisableIS']) {
            $grants = $this->getCurrentUserGrants();

            foreach ($grants as $grant) {
                if (str_contains($grant, 'ALL PRIVILEGES ON *.*') || str_contains($grant, 'CREATE USER')) {
                    $hasCreatePrivilege = true;
                    break;
                }
            }

            SessionCache::set('is_createuser', $hasCreatePrivilege);

            return $hasCreatePrivilege;
        }

        [$user, $host] = $this->getCurrentUserAndHost();
        $query = QueryGenerator::getInformationSchemaDataForCreateRequest($user, $host);
        $hasCreatePrivilege = (bool) $this->fetchValue($query);

        if (! $hasCreatePrivilege) {
            foreach ($this->getCurrentRolesAndHost() as [$role, $roleHost]) {
                $query = QueryGenerator::getInformationSchemaDataForCreateRequest($role, $roleHost ?? '');
                $hasCreatePrivilege = (bool) $this->fetchValue($query);

                if ($hasCreatePrivilege) {
                    break;
                }
            }
        }

        SessionCache::set('is_createuser', $hasCreatePrivilege);

        return $hasCreatePrivilege;
    }

    public function isConnected(): bool
    {
        return isset($this->connections[ConnectionType::User->value]);
    }

    /** @return string[] */
    private function getCurrentUserGrants(): array
    {
        /** @var string[] $grants */
        $grants = $this->fetchResult('SHOW GRANTS FOR CURRENT_USER();');

        return $grants;
    }

    /**
     * Get the current user and host
     *
     * @return array<int, string> array of username and hostname
     */
    public function getCurrentUserAndHost(): array
    {
        if ($this->currentUserAndHost === null) {
            $user = $this->getCurrentUser();
            $this->currentUserAndHost = explode('@', $user);
        }

        return $this->currentUserAndHost;
    }

    /**
     * Get the current role and host.
     *
     * @return array<int, array<int, string>> array of role and hostname
     */
    public function getCurrentRolesAndHost(): array
    {
        if ($this->currentRoleAndHost === null) {
            $roles = $this->getCurrentRoles();

            $this->currentRoleAndHost = array_map(static function (string $role) {
                return explode('@', $role);
            }, $roles);
        }

        return $this->currentRoleAndHost;
    }

    /**
     * Returns value for lower_case_table_names variable
     *
     * @see https://mariadb.com/kb/en/server-system-variables/#lower_case_table_names
     * @see https://dev.mysql.com/doc/refman/en/server-system-variables.html#sysvar_lower_case_table_names
     *
     * @psalm-return 0|1|2
     */
    public function getLowerCaseNames(): int
    {
        if ($this->lowerCaseTableNames === null) {
            $value = (int) $this->fetchValue('SELECT @@lower_case_table_names');
            $this->lowerCaseTableNames = $value >= 0 && $value <= 2 ? $value : 0;
        }

        return $this->lowerCaseTableNames;
    }

    /**
     * Connects to the database server.
     *
     * @param ConnectionType|null $target How to store connection link, defaults to $connectionType
     */
    public function connect(
        Server $currentServer,
        ConnectionType $connectionType,
        ConnectionType|null $target = null,
    ): Connection|null {
        $server = Config::getConnectionParams($currentServer, $connectionType);

        $target ??= $connectionType;

        // Do not show location and backtrace for connection errors
        $errorHandler = ErrorHandler::getInstance();
        $errorHandler->setHideLocation(true);
        try {
            $result = $this->extension->connect($server);
        } catch (ConnectionException $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);

            return null;
        }

        $errorHandler->setHideLocation(false);

        if ($result !== null) {
            $this->connections[$target->value] = $result;
            /* Run post connect for user connections */
            if ($target === ConnectionType::User) {
                $this->postConnect($currentServer);
            }

            return $result;
        }

        if ($connectionType === ConnectionType::ControlUser) {
            trigger_error(
                __(
                    'Connection for controluser as defined in your configuration failed.',
                ),
                E_USER_WARNING,
            );
        }

        return null;
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $dbname database name to select
     */
    public function selectDb(string|DatabaseName $dbname, ConnectionType $connectionType = ConnectionType::User): bool
    {
        if (! isset($this->connections[$connectionType->value])) {
            return false;
        }

        return $this->extension->selectDb($dbname, $this->connections[$connectionType->value]);
    }

    /**
     * Prepare next result from multi_query
     */
    public function nextResult(ConnectionType $connectionType = ConnectionType::User): ResultInterface|false
    {
        if (! isset($this->connections[$connectionType->value])) {
            return false;
        }

        // TODO: Figure out if we really need to check the return value of this function.
        if (! $this->extension->nextResult($this->connections[$connectionType->value])) {
            return false;
        }

        return $this->extension->storeResult($this->connections[$connectionType->value]);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @return string|bool type of connection used
     */
    public function getHostInfo(ConnectionType $connectionType = ConnectionType::User): string|bool
    {
        if (! isset($this->connections[$connectionType->value])) {
            return false;
        }

        return $this->extension->getHostInfo($this->connections[$connectionType->value]);
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @return int|bool version of the MySQL protocol used
     */
    public function getProtoInfo(ConnectionType $connectionType = ConnectionType::User): int|bool
    {
        if (! isset($this->connections[$connectionType->value])) {
            return false;
        }

        return $this->extension->getProtoInfo($this->connections[$connectionType->value]);
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo(): string
    {
        return $this->extension->getClientInfo();
    }

    /**
     * Returns last error message or an empty string if no errors occurred.
     */
    public function getError(ConnectionType $connectionType = ConnectionType::User): string
    {
        if (! isset($this->connections[$connectionType->value])) {
            return '';
        }

        return $this->extension->getError($this->connections[$connectionType->value]);
    }

    /**
     * returns the number of rows returned by last query
     * used with tryQuery as it accepts false
     *
     * @param string $query query to run
     *
     * @psalm-return int|numeric-string
     */
    public function queryAndGetNumRows(string $query): string|int
    {
        $result = $this->tryQuery($query);

        if (! $result) {
            return 0;
        }

        return $result->numRows();
    }

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     */
    public function insertId(ConnectionType $connectionType = ConnectionType::User): int
    {
        // If the primary key is BIGINT we get an incorrect result
        // (sometimes negative, sometimes positive)
        // and in the present function we don't know if the PK is BIGINT
        // so better play safe and use LAST_INSERT_ID()
        //
        // When no controluser is defined, using mysqli_insert_id($link)
        // does not always return the last insert id due to a mixup with
        // the tracking mechanism, but this works:
        return (int) $this->fetchValue('SELECT LAST_INSERT_ID();', 0, $connectionType);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param bool $getFromCache whether to retrieve from cache
     *
     * @psalm-return int|numeric-string
     */
    public function affectedRows(
        ConnectionType $connectionType = ConnectionType::User,
        bool $getFromCache = true,
    ): int|string {
        if (! isset($this->connections[$connectionType->value])) {
            return -1;
        }

        if ($getFromCache) {
            return $GLOBALS['cached_affected_rows'];
        }

        return $this->extension->affectedRows($this->connections[$connectionType->value]);
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param ResultInterface $result result set identifier
     *
     * @return FieldMetadata[] meta info for fields in $result
     */
    public function getFieldsMeta(ResultInterface $result): array
    {
        $fields = $result->getFieldsMeta();

        if ($this->getLowerCaseNames() === 2) {
            /**
             * Fixup orgtable for lower_case_table_names = 2
             *
             * In this setup MySQL server reports table name lower case
             * but we still need to operate on original case to properly
             * match existing strings
             */
            foreach ($fields as $value) {
                if (
                    $value->orgtable === '' ||
                        mb_strtolower($value->orgtable) !== mb_strtolower($value->table)
                ) {
                    continue;
                }

                $value->orgtable = $value->table;
            }
        }

        return $fields;
    }

    /**
     * Returns properly quoted string for use in MySQL queries.
     *
     * @param string $str string to be quoted
     *
     * @psalm-return non-empty-string
     *
     * @psalm-taint-escape sql
     */
    public function quoteString(string $str, ConnectionType $connectionType = ConnectionType::User): string
    {
        return "'" . $this->extension->escapeString($this->connections[$connectionType->value], $str) . "'";
    }

    /**
     * Returns properly escaped string for use in MySQL LIKE clauses.
     * This method escapes only _, %, and /. It does not escape quotes or any other characters.
     *
     * @param string $str string to be escaped
     *
     * @return string a MySQL escaped LIKE string
     */
    public function escapeMysqlWildcards(string $str): string
    {
        return strtr($str, ['\\' => '\\\\', '_' => '\\_', '%' => '\\%']);
    }

    /**
     * Checks if this database server is running on Amazon RDS.
     */
    public function isAmazonRds(): bool
    {
        if (SessionCache::has('is_amazon_rds')) {
            return (bool) SessionCache::get('is_amazon_rds');
        }

        $sql = 'SELECT @@basedir';
        $result = (string) $this->fetchValue($sql);
        $rds = str_starts_with($result, '/rdsdbbin/');
        SessionCache::set('is_amazon_rds', $rds);

        return $rds;
    }

    /**
     * Gets SQL for killing a process.
     *
     * @param int $process Process ID
     */
    public function getKillQuery(int $process): string
    {
        if ($this->isAmazonRds()) {
            return 'CALL mysql.rds_kill(' . $process . ');';
        }

        return 'KILL ' . $process . ';';
    }

    /**
     * Get the phpmyadmin database manager
     */
    public function getSystemDatabase(): SystemDatabase
    {
        return new SystemDatabase($this);
    }

    /**
     * Get a table with database name and table name
     *
     * @param string $dbName    DB name
     * @param string $tableName Table name
     */
    public function getTable(string $dbName, string $tableName): Table
    {
        return new Table($tableName, $dbName, $this);
    }

    /**
     * returns collation of given db
     *
     * @param string $db name of db
     *
     * @return string  collation of $db
     */
    public function getDbCollation(string $db): string
    {
        if (! $this->config->selectedServer['DisableIS']) {
            // this is slow with thousands of databases
            $sql = 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA'
                . ' WHERE SCHEMA_NAME = ' . $this->quoteString($db)
                . ' LIMIT 1';

            return (string) $this->fetchValue($sql);
        }

        $this->selectDb($db);
        $return = (string) $this->fetchValue('SELECT @@collation_database');
        if ($db !== Current::$database) {
            $this->selectDb(Current::$database);
        }

        return $return;
    }

    /**
     * returns default server collation from show variables
     */
    public function getServerCollation(): string
    {
        return (string) $this->fetchValue('SELECT @@collation_server');
    }

    /**
     * Server version as number
     *
     * @example 80011
     */
    public function getVersion(): int
    {
        return $this->versionInt;
    }

    /**
     * Server version
     */
    public function getVersionString(): string
    {
        return $this->versionString;
    }

    /**
     * Server version comment
     */
    public function getVersionComment(): string
    {
        return $this->versionComment;
    }

    /**
     * Whether connection is MariaDB
     */
    public function isMariaDB(): bool
    {
        return $this->isMariaDb;
    }

    /**
     * Whether connection is PerconaDB
     */
    public function isPercona(): bool
    {
        return $this->isPercona;
    }

    /**
     * Set version
     *
     * @param array $version Database version information
     * @phpstan-param array<array-key, mixed> $version
     */
    public function setVersion(array $version): void
    {
        $this->versionString = $version['@@version'] ?? '';
        $this->versionInt = Utilities::versionToInt($this->versionString);
        $this->versionComment = $version['@@version_comment'] ?? '';

        $this->isMariaDb = stripos($this->versionString, 'mariadb') !== false;
        $this->isPercona = stripos($this->versionComment, 'percona') !== false;
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The query, as a string.
     */
    public function prepare(string $query, ConnectionType $connectionType = ConnectionType::User): Statement|null
    {
        return $this->extension->prepare($this->connections[$connectionType->value], $query);
    }

    public function getDatabaseList(): ListDatabase
    {
        if ($this->databaseList === null) {
            $this->databaseList = new ListDatabase($this, $this->config, new UserPrivilegesFactory($this));
        }

        return $this->databaseList;
    }

    /**
     * Returns the number of warnings from the last query.
     */
    private function getWarningCount(ConnectionType $connectionType): int
    {
        if (! isset($this->connections[$connectionType->value])) {
            return 0;
        }

        return $this->extension->getWarningCount($this->connections[$connectionType->value]);
    }
}
