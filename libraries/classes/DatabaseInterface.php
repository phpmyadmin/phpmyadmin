<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\DbalInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Dbal\Statement;
use PhpMyAdmin\Dbal\Warning;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Query\Cache;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Utils\SessionCache;
use stdClass;

use function __;
use function array_column;
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
use function str_starts_with;
use function stripos;
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
 *
 * @psalm-import-type ConnectionType from Connection
 */
class DatabaseInterface implements DbalInterface
{
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

    private DbiExtension $extension;

    /**
     * Opened database connections.
     *
     * @var array<int, Connection>
     * @psalm-var array<ConnectionType, Connection>
     */
    private array $connections = [];

    /** @var array<int, string>|null */
    private array|null $currentUserAndHost = null;

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

    /** @param DbiExtension $ext Object to be used for database queries */
    public function __construct(DbiExtension $ext)
    {
        $this->extension = $ext;
        if (defined('TESTSUITE')) {
            $this->connections[Connection::TYPE_USER] = new Connection(new stdClass());
            $this->connections[Connection::TYPE_CONTROL] = new Connection(new stdClass());
        }

        $this->cache = new Cache();
        $this->types = new Types($this);
    }

    /**
     * runs a query
     *
     * @param string $query             SQL query to execute
     * @param int    $options           optional query options
     * @param bool   $cacheAffectedRows whether to cache affected rows
     * @psalm-param ConnectionType $connectionType
     */
    public function query(
        string $query,
        int $connectionType = Connection::TYPE_USER,
        int $options = self::QUERY_BUFFERED,
        bool $cacheAffectedRows = true,
    ): ResultInterface {
        $result = $this->tryQuery($query, $connectionType, $options, $cacheAffectedRows);

        if (! $result) {
            // The following statement will exit
            Generator::mysqlDie($this->getError($connectionType), $query);

            exit;
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
     * @psalm-param ConnectionType $connectionType
     */
    public function tryQuery(
        string $query,
        int $connectionType = Connection::TYPE_USER,
        int $options = self::QUERY_BUFFERED,
        bool $cacheAffectedRows = true,
    ): ResultInterface|false {
        $debug = isset($GLOBALS['cfg']['DBG']) && $GLOBALS['cfg']['DBG']['sql'];
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        $time = microtime(true);

        $result = $this->extension->realQuery($query, $this->connections[$connectionType], $options);

        if ($connectionType === Connection::TYPE_USER) {
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
            if ($GLOBALS['cfg']['DBG']['sqllog']) {
                openlog('phpMyAdmin', LOG_NDELAY | LOG_PID, LOG_USER);

                syslog(
                    LOG_INFO,
                    sprintf(
                        'SQL[%s?route=%s]: %0.3f(W:%d,C:%s,L:0x%02X) > %s',
                        basename($_SERVER['SCRIPT_NAME']),
                        Common::getRequest()->getRoute(),
                        $this->lastQueryExecutionTime,
                        $this->getWarningCount($connectionType),
                        $cacheAffectedRows ? 'y' : 'n',
                        $connectionType,
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
     * @psalm-param ConnectionType $connectionType
     */
    public function tryMultiQuery(
        string $multiQuery = '',
        int $connectionType = Connection::TYPE_USER,
    ): bool {
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        return $this->extension->realMultiQuery($this->connections[$connectionType], $multiQuery);
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
        return $this->query($sql, Connection::TYPE_CONTROL, self::QUERY_BUFFERED, false);
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
        return $this->tryQuery($sql, Connection::TYPE_CONTROL, self::QUERY_BUFFERED, false);
    }

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     * @psalm-param ConnectionType $connectionType
     *
     * @return array<int, string>   tables names
     */
    public function getTables(string $database, int $connectionType = Connection::TYPE_USER): array
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
        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($tables, 'strnatcasecmp');
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
     * @psalm-param ConnectionType $connectionType
     *
     * @return mixed[]           list of tables in given db(s)
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
        int $connectionType = Connection::TYPE_USER,
    ): array {
        if ($limitCount === true) {
            $limitCount = $GLOBALS['cfg']['MaxTableList'];
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

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
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

            /** @var mixed[][][] $tables */
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

            if ($sortBy === 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                // here, the array's first key is by schema name
                foreach ($tables as $oneDatabaseName => $oneDatabaseTables) {
                    uksort($oneDatabaseTables, 'strnatcasecmp');

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
                        /**
                         * @param array $a
                         * @param array $b
                         */
                        static function ($a, $b) {
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
            if (($table !== '' && $table !== []) || $tableIsGroup || $tableType) {
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

                if ($tableType) {
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
            if ($sortBy === 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                uksort($eachTables, 'strnatcasecmp');

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

                if ($sortValues) {
                    if ($sortOrder === 'DESC') {
                        array_multisort($sortValues, SORT_DESC, $eachTables);
                    } else {
                        array_multisort($sortValues, SORT_ASC, $eachTables);
                    }
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
     * Get VIEWs in a particular database
     *
     * @param string $db Database name to look in
     *
     * @return Table[] Set of VIEWs inside the database
     */
    public function getVirtualTables(string $db): array
    {
        /** @var string[] $tablesFull */
        $tablesFull = array_column($this->getTablesFull($db), 'TABLE_NAME');
        $views = [];

        foreach ($tablesFull as $table) {
            $table = $this->getTable($db, $table);
            if (! $table->isView()) {
                continue;
            }

            $views[] = $table;
        }

        return $views;
    }

    /**
     * returns array with databases containing extended infos about them
     *
     * @param string|null $database    database
     * @param bool        $forceStats  retrieve stats also for MySQL < 5
     * @param string      $sortBy      column to order by
     * @param string      $sortOrder   ASC or DESC
     * @param int         $limitOffset starting offset for LIMIT
     * @param bool|int    $limitCount  row count for LIMIT or true for $GLOBALS['cfg']['MaxDbList']
     * @psalm-param ConnectionType $connectionType
     *
     * @return mixed[]
     *
     * @todo    move into ListDatabase?
     */
    public function getDatabasesFull(
        string|null $database = null,
        bool $forceStats = false,
        int $connectionType = Connection::TYPE_USER,
        string $sortBy = 'SCHEMA_NAME',
        string $sortOrder = 'ASC',
        int $limitOffset = 0,
        bool|int $limitCount = false,
    ): array {
        $sortOrder = strtoupper($sortOrder);

        if ($limitCount === true) {
            $limitCount = $GLOBALS['cfg']['MaxDbList'];
        }

        $applyLimitAndOrderManual = true;

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            /**
             * if $GLOBALS['cfg']['NaturalOrder'] is enabled, we cannot use LIMIT
             * cause MySQL does not support natural ordering,
             * we have to do it afterward
             */
            $limit = '';
            if (! $GLOBALS['cfg']['NaturalOrder']) {
                if ($limitCount) {
                    $limit = ' LIMIT ' . $limitCount . ' OFFSET ' . $limitOffset;
                }

                $applyLimitAndOrderManual = false;
            }

            // get table information from information_schema
            $sqlWhereSchema = '';
            if ($database !== null) {
                $sqlWhereSchema = 'WHERE `SCHEMA_NAME` LIKE \''
                    . $this->escapeString($database, $connectionType) . '\'';
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
         * (caused by older MySQL < 5 or $GLOBALS['cfg']['NaturalOrder'])
         */
        if ($applyLimitAndOrderManual) {
            usort(
                $databases,
                static fn ($a, $b) => Utilities::usortComparisonCallback($a, $b, $sortBy, $sortOrder)
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
     * @psalm-param ConnectionType $connectionType
     *
     * @return mixed[]
     */
    public function getColumnsFull(
        string|null $database = null,
        string|null $table = null,
        string|null $column = null,
        int $connectionType = Connection::TYPE_USER,
    ): array {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
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
            $sql .= " LIKE '" . $this->escapeString($column, $connectionType) . "'";
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
     * @param bool   $full     whether to return full info or only column names
     * @psalm-param ConnectionType $connectionType
     *
     * @return mixed[] flat array description
     */
    public function getColumn(
        string $database,
        string $table,
        string $column,
        bool $full = false,
        int $connectionType = Connection::TYPE_USER,
    ): array {
        $sql = QueryGenerator::getColumnsSql(
            $database,
            $table,
            $this->escapeString($this->escapeMysqlWildcards($column)),
            $full,
        );
        /** @var array<string, array> $fields */
        $fields = $this->fetchResult($sql, 'Field', null, $connectionType);

        $columns = $this->attachIndexInfoToColumns($database, $table, $fields);

        return array_shift($columns) ?? [];
    }

    /**
     * Returns descriptions of columns in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param bool   $full     whether to return full info or only column names
     * @psalm-param ConnectionType $connectionType
     *
     * @return mixed[][] array indexed by column names
     */
    public function getColumns(
        string $database,
        string $table,
        bool $full = false,
        int $connectionType = Connection::TYPE_USER,
    ): array {
        $sql = QueryGenerator::getColumnsSql($database, $table, null, $full);
        /** @var array[] $fields */
        $fields = $this->fetchResult($sql, 'Field', null, $connectionType);

        return $this->attachIndexInfoToColumns($database, $table, $fields);
    }

    /**
     * Attach index information to the column definition
     *
     * @param string    $database name of database
     * @param string    $table    name of table to retrieve columns from
     * @param mixed[][] $fields   column array indexed by their names
     *
     * @return mixed[][] Column defintions with index information
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

                if ($index->isUnique()) {
                    $fields[$field]['Key'] = 'UNI';
                } else {
                    $fields[$field]['Key'] = 'MUL';
                }
            }
        }

        return $fields;
    }

    /**
     * Returns all column names in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @psalm-param ConnectionType $connectionType
     *
     * @return string[]
     */
    public function getColumnNames(
        string $database,
        string $table,
        int $connectionType = Connection::TYPE_USER,
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
     * @psalm-param ConnectionType $connectionType
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
        int $connectionType = Connection::TYPE_USER,
    ): array {
        $sql = QueryGenerator::getTableIndexesSql($database, $table);

        return $this->fetchResult($sql, null, null, $connectionType);
    }

    /**
     * returns value of given mysql server variable
     *
     * @param string $var  mysql server variable name
     * @param int    $type DatabaseInterface::GETVAR_SESSION | DatabaseInterface::GETVAR_GLOBAL
     * @psalm-param ConnectionType $connectionType
     *
     * @return false|string|null value for mysql server variable
     */
    public function getVariable(
        string $var,
        int $type = self::GETVAR_SESSION,
        int $connectionType = Connection::TYPE_USER,
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
     * @psalm-param ConnectionType $connectionType
     */
    public function setVariable(
        string $var,
        string $value,
        int $connectionType = Connection::TYPE_USER,
    ): bool {
        $currentValue = $this->getVariable($var, self::GETVAR_SESSION, $connectionType);
        if ($currentValue == $value) {
            return true;
        }

        return (bool) $this->query('SET ' . $var . ' = ' . $value . ';', $connectionType);
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

        if ($this->versionInt > 50503) {
            $defaultCharset = 'utf8mb4';
            $defaultCollation = 'utf8mb4_general_ci';
        } else {
            $defaultCharset = 'utf8';
            $defaultCollation = 'utf8_general_ci';
        }

        $GLOBALS['collation_connection'] = $defaultCollation;
        $GLOBALS['charset_connection'] = $defaultCharset;
        $this->query(sprintf('SET NAMES \'%s\' COLLATE \'%s\';', $defaultCharset, $defaultCollation));

        /* Locale for messages */
        $locale = LanguageManager::getInstance()->getCurrentLanguage()->getMySQLLocale();
        if ($locale) {
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
                    $GLOBALS['server'],
                    $GLOBALS['server'],
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
        $charset = $GLOBALS['charset_connection'];
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

        $GLOBALS['collation_connection'] = $collation;
    }

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnectControl(Relation $relation): void
    {
        // If Zero configuration mode enabled, check PMA tables in current db.
        if (! $GLOBALS['cfg']['ZeroConf']) {
            return;
        }

        $this->databaseList = null;

        $relation->initRelationParamsCache();
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
     * @psalm-param ConnectionType $connectionType
     *
     * @return string|false|null value of first field in first row from result or false if not found
     */
    public function fetchValue(
        string $query,
        int|string $field = 0,
        int $connectionType = Connection::TYPE_USER,
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
     * @psalm-param ConnectionType $connectionType
     */
    public function fetchSingleRow(
        string $query,
        string $type = DbalInterface::FETCH_ASSOC,
        int $connectionType = Connection::TYPE_USER,
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
     * @return mixed[]
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
     * @psalm-param ConnectionType $connectionType
     *
     * @return mixed[] resultrows or values indexed by $key
     */
    public function fetchResult(
        string $query,
        string|int|array|null $key = null,
        string|int|null $value = null,
        int $connectionType = Connection::TYPE_USER,
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
     * @return mixed[] supported SQL compatibility modes
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
     * @psalm-param ConnectionType $connectionType
     *
     * @return Warning[] warnings
     */
    public function getWarnings(int $connectionType = Connection::TYPE_USER): array
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

    public function isSuperUser(): bool
    {
        if (SessionCache::has('is_superuser')) {
            return (bool) SessionCache::get('is_superuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $result = $this->tryQuery('SELECT 1 FROM mysql.user LIMIT 1');
        $isSuperUser = false;

        if ($result) {
            $isSuperUser = (bool) $result->numRows();
        }

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

        if ($GLOBALS['cfg']['Server']['DisableIS']) {
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
        $result = $this->tryQuery($query);

        if ($result) {
            $hasGrantPrivilege = (bool) $result->numRows();
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

        if ($GLOBALS['cfg']['Server']['DisableIS']) {
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
        $result = $this->tryQuery($query);

        if ($result) {
            $hasCreatePrivilege = (bool) $result->numRows();
        }

        SessionCache::set('is_createuser', $hasCreatePrivilege);

        return $hasCreatePrivilege;
    }

    public function isConnected(): bool
    {
        return isset($this->connections[Connection::TYPE_USER]);
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
     * @param int|null $target How to store connection link, defaults to $mode
     * @psalm-param ConnectionType $connectionType
     * @psalm-param ConnectionType|null $target
     */
    public function connect(Server $currentServer, int $connectionType, int|null $target = null): Connection|null
    {
        $server = Config::getConnectionParams($currentServer, $connectionType);

        $target ??= $connectionType;

        // Do not show location and backtrace for connection errors
        $GLOBALS['errorHandler']->setHideLocation(true);
        $result = $this->extension->connect($server);
        $GLOBALS['errorHandler']->setHideLocation(false);

        if ($result !== null) {
            $this->connections[$target] = $result;
            /* Run post connect for user connections */
            if ($target === Connection::TYPE_USER) {
                $this->postConnect($currentServer);
            }

            return $result;
        }

        if ($connectionType === Connection::TYPE_CONTROL) {
            trigger_error(
                __(
                    'Connection for controluser as defined in your configuration failed.',
                ),
                E_USER_WARNING,
            );

            return null;
        }

        if ($connectionType === Connection::TYPE_AUXILIARY) {
            // Do not go back to main login if connection failed
            // (currently used only in unit testing)
            return null;
        }

        return null;
    }

    /**
     * selects given database
     *
     * @param string|DatabaseName $dbname database name to select
     * @psalm-param ConnectionType $connectionType
     */
    public function selectDb(string|DatabaseName $dbname, int $connectionType = Connection::TYPE_USER): bool
    {
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        return $this->extension->selectDb($dbname, $this->connections[$connectionType]);
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @psalm-param ConnectionType $connectionType
     */
    public function moreResults(int $connectionType = Connection::TYPE_USER): bool
    {
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        return $this->extension->moreResults($this->connections[$connectionType]);
    }

    /**
     * Prepare next result from multi_query
     *
     * @psalm-param ConnectionType $connectionType
     */
    public function nextResult(int $connectionType = Connection::TYPE_USER): bool
    {
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        return $this->extension->nextResult($this->connections[$connectionType]);
    }

    /**
     * Store the result returned from multi query
     *
     * @psalm-param ConnectionType $connectionType
     *
     * @return ResultInterface|false false when empty results / result set when not empty
     */
    public function storeResult(int $connectionType = Connection::TYPE_USER): ResultInterface|false
    {
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        return $this->extension->storeResult($this->connections[$connectionType]);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @psalm-param ConnectionType $connectionType
     *
     * @return string|bool type of connection used
     */
    public function getHostInfo(int $connectionType = Connection::TYPE_USER): string|bool
    {
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        return $this->extension->getHostInfo($this->connections[$connectionType]);
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @psalm-param ConnectionType $connectionType
     *
     * @return int|bool version of the MySQL protocol used
     */
    public function getProtoInfo(int $connectionType = Connection::TYPE_USER): int|bool
    {
        if (! isset($this->connections[$connectionType])) {
            return false;
        }

        return $this->extension->getProtoInfo($this->connections[$connectionType]);
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
     *
     * @psalm-param ConnectionType $connectionType
     */
    public function getError(int $connectionType = Connection::TYPE_USER): string
    {
        if (! isset($this->connections[$connectionType])) {
            return '';
        }

        return $this->extension->getError($this->connections[$connectionType]);
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
     *
     * @psalm-param ConnectionType $connectionType
     */
    public function insertId(int $connectionType = Connection::TYPE_USER): int
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
     * @psalm-param ConnectionType $connectionType
     *
     * @psalm-return int|numeric-string
     */
    public function affectedRows(
        int $connectionType = Connection::TYPE_USER,
        bool $getFromCache = true,
    ): int|string {
        if (! isset($this->connections[$connectionType])) {
            return -1;
        }

        if ($getFromCache) {
            return $GLOBALS['cached_affected_rows'];
        }

        return $this->extension->affectedRows($this->connections[$connectionType]);
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
     * @psalm-param ConnectionType $connectionType
     *
     * @psalm-return non-empty-string
     *
     * @psalm-taint-escape sql
     */
    public function quoteString(string $str, int $connectionType = Connection::TYPE_USER): string
    {
        return "'" . $this->extension->escapeString($this->connections[$connectionType], $str) . "'";
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @deprecated Use {@see quoteString()} instead.
     *
     * @param string $str string to be escaped
     * @psalm-param ConnectionType $connectionType
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(string $str, int $connectionType = Connection::TYPE_USER): string
    {
        if (isset($this->connections[$connectionType])) {
            return $this->extension->escapeString($this->connections[$connectionType], $str);
        }

        return $str;
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
        if (Utilities::isSystemSchema($db)) {
            // We don't have to check the collation of the virtual
            // information_schema database: We know it!
            return 'utf8_general_ci';
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            // this is slow with thousands of databases
            $sql = 'SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA'
                . ' WHERE SCHEMA_NAME = ' . $this->quoteString($db)
                . ' LIMIT 1';

            return (string) $this->fetchValue($sql);
        }

        $this->selectDb($db);
        $return = (string) $this->fetchValue('SELECT @@collation_database');
        if ($db !== $GLOBALS['db']) {
            $this->selectDb($GLOBALS['db']);
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
     * Load correct database driver
     *
     * @param DbiExtension|null $extension Force the use of an alternative extension
     */
    public static function load(DbiExtension|null $extension = null): self
    {
        if ($extension !== null) {
            return new self($extension);
        }

        return new self(new DbiMysqli());
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The query, as a string.
     * @psalm-param ConnectionType $connectionType
     */
    public function prepare(string $query, int $connectionType = Connection::TYPE_USER): Statement|null
    {
        return $this->extension->prepare($this->connections[$connectionType], $query);
    }

    public function getDatabaseList(): ListDatabase
    {
        if ($this->databaseList === null) {
            $this->databaseList = new ListDatabase();
        }

        return $this->databaseList;
    }

    /**
     * Returns the number of warnings from the last query.
     *
     * @psalm-param ConnectionType $connectionType
     */
    private function getWarningCount(int $connectionType): int
    {
        if (! isset($this->connections[$connectionType])) {
            return 0;
        }

        return $this->extension->getWarningCount($this->connections[$connectionType]);
    }
}
