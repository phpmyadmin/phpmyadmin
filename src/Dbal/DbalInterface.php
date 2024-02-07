<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use PhpMyAdmin\Column;
use PhpMyAdmin\ColumnFull;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Table\Table;

/**
 * Main interface for database interactions
 */
interface DbalInterface
{
    public const FETCH_NUM = 'NUM';
    public const FETCH_ASSOC = 'ASSOC';

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
        int $options = 0,
        bool $cacheAffectedRows = true,
    ): ResultInterface;

    /**
     * runs a query and returns the result
     *
     * @param string $query             query to run
     * @param int    $options           query options
     * @param bool   $cacheAffectedRows whether to cache affected row
     */
    public function tryQuery(
        string $query,
        ConnectionType $connectionType = ConnectionType::User,
        int $options = 0,
        bool $cacheAffectedRows = true,
    ): mixed;

    /**
     * Send multiple SQL queries to the database server and execute the first one
     *
     * @param string $multiQuery multi query statement to execute
     */
    public function tryMultiQuery(
        string $multiQuery = '',
        ConnectionType $connectionType = ConnectionType::User,
    ): bool;

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     *
     * @return array<int, string>   tables names
     */
    public function getTables(string $database, ConnectionType $connectionType = ConnectionType::User): array;

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
        ConnectionType $connectionType = ConnectionType::User,
    ): array;

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
    ): array;

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
    ): array;

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
    ): ColumnFull|Column|null;

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
    ): array;

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
    ): array;

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
    ): array;

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
        int $type = DatabaseInterface::GETVAR_SESSION,
        ConnectionType $connectionType = ConnectionType::User,
    ): false|string|null;

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
    ): bool;

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnect(Server $currentServer): void;

    /**
     * Sets collation connection for user link
     *
     * @param string $collation collation to set
     */
    public function setCollation(string $collation): void;

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
     * @return string|false|null value of first field in first row from result
     *               or false if not found
     */
    public function fetchValue(
        string $query,
        int|string $field = 0,
        ConnectionType $connectionType = ConnectionType::User,
    ): string|false|null;

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
     * @param string $type  NUM|ASSOC returned array should either numeric associative or both
     * @psalm-param self::FETCH_NUM|self::FETCH_ASSOC $type
     *
     * @return array<string|null>|null
     */
    public function fetchSingleRow(
        string $query,
        string $type = DbalInterface::FETCH_ASSOC,
        ConnectionType $connectionType = ConnectionType::User,
    ): array|null;

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
     * @param string|int|mixed[]|null $key   field-name or offset used as key for array or array of those
     * @param string|int|null         $value value-name or offset used as value for array
     *
     * @return mixed[] resultrows or values indexed by $key
     */
    public function fetchResult(
        string $query,
        string|int|array|null $key = null,
        string|int|null $value = null,
        ConnectionType $connectionType = ConnectionType::User,
    ): array;

    /**
     * Get supported SQL compatibility modes
     *
     * @return string[] supported SQL compatibility modes
     */
    public function getCompatibilities(): array;

    /**
     * returns warnings for last query
     *
     * @return Warning[] warnings
     */
    public function getWarnings(ConnectionType $connectionType = ConnectionType::User): array;

    /**
     * gets the current user with host
     *
     * @return string the current user i.e. user@host
     */
    public function getCurrentUser(): string;

    /**
     * Checks if current user is superuser
     */
    public function isSuperUser(): bool;

    public function isGrantUser(): bool;

    public function isCreateUser(): bool;

    public function isConnected(): bool;

    /**
     * Get the current user and host
     *
     * @return array<int, string> array of username and hostname
     */
    public function getCurrentUserAndHost(): array;

    /**
     * Returns value for lower_case_table_names variable
     *
     * @see https://mariadb.com/kb/en/server-system-variables/#lower_case_table_names
     * @see https://dev.mysql.com/doc/refman/en/server-system-variables.html#sysvar_lower_case_table_names
     *
     * @psalm-return 0|1|2
     */
    public function getLowerCaseNames(): int;

    /**
     * Connects to the database server.
     *
     * @param ConnectionType|null $target How to store connection link, defaults to $connectionType
     */
    public function connect(
        Server $currentServer,
        ConnectionType $connectionType,
        ConnectionType|null $target = null,
    ): Connection|null;

    /**
     * selects given database
     *
     * @param string|DatabaseName $dbname database name to select
     */
    public function selectDb(string|DatabaseName $dbname, ConnectionType $connectionType = ConnectionType::User): bool;

    /**
     * Prepare next result from multi_query
     */
    public function nextResult(ConnectionType $connectionType = ConnectionType::User): ResultInterface|false;

    /**
     * Returns a string representing the type of connection used
     *
     * @return string|bool type of connection used
     */
    public function getHostInfo(ConnectionType $connectionType = ConnectionType::User): string|bool;

    /**
     * Returns the version of the MySQL protocol used
     *
     * @return int|bool version of the MySQL protocol used
     */
    public function getProtoInfo(ConnectionType $connectionType = ConnectionType::User): int|bool;

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo(): string;

    /**
     * Returns last error message or an empty string if no errors occurred.
     */
    public function getError(ConnectionType $connectionType = ConnectionType::User): string;

    /**
     * returns the number of rows returned by last query
     * used with tryQuery as it accepts false
     *
     * @param string $query query to run
     *
     * @psalm-return int|numeric-string
     */
    public function queryAndGetNumRows(string $query): string|int;

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     */
    public function insertId(ConnectionType $connectionType = ConnectionType::User): int;

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
    ): int|string;

    /**
     * returns metainfo for fields in $result
     *
     * @param ResultInterface $result result set identifier
     *
     * @return FieldMetadata[] meta info for fields in $result
     */
    public function getFieldsMeta(ResultInterface $result): array;

    /**
     * Returns properly quoted string for use in MySQL queries.
     *
     * @param string $str string to be quoted
     *
     * @psalm-return non-empty-string
     *
     * @psalm-taint-escape sql
     */
    public function quoteString(string $str, ConnectionType $connectionType = ConnectionType::User): string;

    /**
     * Returns properly escaped string for use in MySQL LIKE clauses.
     * This method escapes only _, %, and /. It does not escape quotes or any other characters.
     *
     * @param string $str string to be escaped
     *
     * @return string a MySQL escaped LIKE string
     */
    public function escapeMysqlWildcards(string $str): string;

    /**
     * Checks if this database server is running on Amazon RDS.
     */
    public function isAmazonRds(): bool;

    /**
     * Gets SQL for killing a process.
     *
     * @param int $process Process ID
     */
    public function getKillQuery(int $process): string;

    /**
     * Get the phpmyadmin database manager
     */
    public function getSystemDatabase(): SystemDatabase;

    /**
     * Get a table with database name and table name
     *
     * @param string $dbName    DB name
     * @param string $tableName Table name
     */
    public function getTable(string $dbName, string $tableName): Table;

    /**
     * returns collation of given db
     *
     * @param string $db name of db
     *
     * @return string  collation of $db
     */
    public function getDbCollation(string $db): string;

    /**
     * returns default server collation from show variables
     */
    public function getServerCollation(): string;

    /**
     * Server version as number
     */
    public function getVersion(): int;

    /**
     * Server version
     */
    public function getVersionString(): string;

    /**
     * Server version comment
     */
    public function getVersionComment(): string;

    /**
     * Whether connection is MariaDB
     */
    public function isMariaDB(): bool;

    /**
     * Whether connection is Percona
     */
    public function isPercona(): bool;

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The query, as a string.
     */
    public function prepare(string $query, ConnectionType $connectionType = ConnectionType::User): Statement|null;
}
