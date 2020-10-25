<?php

declare(strict_types=1);

namespace PhpMyAdmin\Dbal;

use mysqli_result;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Table;

/**
 * Main interface for database interactions
 */
interface DbalInterface
{
    /**
     * runs a query
     *
     * @param string $query               SQL query to execute
     * @param mixed  $link                optional database link to use
     * @param int    $options             optional query options
     * @param bool   $cache_affected_rows whether to cache affected rows
     *
     * @return mixed
     */
    public function query(
        string $query,
        $link = DatabaseInterface::CONNECT_USER,
        int $options = 0,
        bool $cache_affected_rows = true
    );

    /**
     * runs a query and returns the result
     *
     * @param string $query               query to run
     * @param mixed  $link                link type
     * @param int    $options             query options
     * @param bool   $cache_affected_rows whether to cache affected row
     *
     * @return mixed
     */
    public function tryQuery(
        string $query,
        $link = DatabaseInterface::CONNECT_USER,
        int $options = 0,
        bool $cache_affected_rows = true
    );

    /**
     * Run multi query statement and return results
     *
     * @param string $multiQuery multi query statement to execute
     * @param int    $linkIndex  index of the opened database link
     *
     * @return mysqli_result[]|bool (false)
     */
    public function tryMultiQuery(string $multiQuery = '', $linkIndex = DatabaseInterface::CONNECT_USER);

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     * @param mixed  $link     mysql link resource|object
     *
     * @return array   tables names
     */
    public function getTables(string $database, $link = DatabaseInterface::CONNECT_USER): array;

    /**
     * returns
     *
     * @param string $database name of database
     * @param array  $tables   list of tables to search for for relations
     * @param int    $link     mysql link resource|object
     *
     * @return array           array of found foreign keys
     */
    public function getForeignKeyConstrains(
        string $database,
        array $tables,
        $link = DatabaseInterface::CONNECT_USER
    ): array;

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
     * @param string       $database     database
     * @param string|array $table        table name(s)
     * @param bool         $tbl_is_group $table is a table group
     * @param int          $limit_offset zero-based offset for the count
     * @param bool|int     $limit_count  number of tables to return
     * @param string       $sort_by      table attribute to sort by
     * @param string       $sort_order   direction to sort (ASC or DESC)
     * @param string       $table_type   whether table or view
     * @param mixed        $link         link type
     *
     * @return array           list of tables in given db(s)
     *
     * @todo    move into Table
     */
    public function getTablesFull(
        string $database,
        $table = '',
        bool $tbl_is_group = false,
        int $limit_offset = 0,
        $limit_count = false,
        string $sort_by = 'Name',
        string $sort_order = 'ASC',
        ?string $table_type = null,
        $link = DatabaseInterface::CONNECT_USER
    ): array;

    /**
     * Get VIEWs in a particular database
     *
     * @param string $db Database name to look in
     *
     * @return array Set of VIEWs inside the database
     */
    public function getVirtualTables(string $db): array;

    /**
     * returns array with databases containing extended infos about them
     *
     * @param string   $database     database
     * @param bool     $force_stats  retrieve stats also for MySQL < 5
     * @param int      $link         link type
     * @param string   $sort_by      column to order by
     * @param string   $sort_order   ASC or DESC
     * @param int      $limit_offset starting offset for LIMIT
     * @param bool|int $limit_count  row count for LIMIT or true
     *                               for
     *                               $GLOBALS['cfg']['MaxDbList']
     *
     * @return array
     *
     * @todo    move into ListDatabase?
     */
    public function getDatabasesFull(
        ?string $database = null,
        bool $force_stats = false,
        $link = DatabaseInterface::CONNECT_USER,
        string $sort_by = 'SCHEMA_NAME',
        string $sort_order = 'ASC',
        int $limit_offset = 0,
        $limit_count = false
    ): array;

    /**
     * returns detailed array with all columns for sql
     *
     * @param string $sql_query    target SQL query to get columns
     * @param array  $view_columns alias for columns
     *
     * @return array
     */
    public function getColumnMapFromSql(string $sql_query, array $view_columns = []): array;

    /**
     * returns detailed array with all columns for given table in database,
     * or all tables/databases
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param string $column   name of specific column
     * @param mixed  $link     mysql link resource
     *
     * @return array
     */
    public function getColumnsFull(
        ?string $database = null,
        ?string $table = null,
        ?string $column = null,
        $link = DatabaseInterface::CONNECT_USER
    ): array;

    /**
     * Returns descriptions of columns in given table (all or given by $column)
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param string $column   name of column, null to show all columns
     * @param bool   $full     whether to return full info or only column names
     * @param int    $link     link type
     *
     * @return array array indexed by column names or,
     *               if $column is given, flat array description
     */
    public function getColumns(
        string $database,
        string $table,
        ?string $column = null,
        bool $full = false,
        $link = DatabaseInterface::CONNECT_USER
    ): array;

    /**
     * Returns all column names in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param mixed  $link     mysql link resource
     *
     * @return array|null
     */
    public function getColumnNames(
        string $database,
        string $table,
        $link = DatabaseInterface::CONNECT_USER
    ): ?array;

    /**
     * Returns indexes of a table
     *
     * @param string $database name of database
     * @param string $table    name of the table whose indexes are to be retrieved
     * @param mixed  $link     mysql link resource
     *
     * @return array
     */
    public function getTableIndexes(
        string $database,
        string $table,
        $link = DatabaseInterface::CONNECT_USER
    ): array;

    /**
     * returns value of given mysql server variable
     *
     * @param string $var  mysql server variable name
     * @param int    $type DatabaseInterface::GETVAR_SESSION |
     *                     DatabaseInterface::GETVAR_GLOBAL
     * @param mixed  $link mysql link resource|object
     *
     * @return mixed   value for mysql server variable
     */
    public function getVariable(
        string $var,
        int $type = DatabaseInterface::GETVAR_SESSION,
        $link = DatabaseInterface::CONNECT_USER
    );

    /**
     * Sets new value for a variable if it is different from the current value
     *
     * @param string $var   variable name
     * @param string $value value to set
     * @param mixed  $link  mysql link resource|object
     *
     * @return bool whether query was a successful
     */
    public function setVariable(string $var, string $value, $link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnect(): void;

    /**
     * Sets collation connection for user link
     *
     * @param string $collation collation to set
     */
    public function setCollation(string $collation): void;

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnectControl(): void;

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
     * @param string     $query      The query to execute
     * @param int        $row_number row to fetch the value from,
     *                               starting at 0, with 0 being
     *                               default
     * @param int|string $field      field to fetch the value from,
     *                               starting at 0, with 0 being
     *                               default
     * @param int        $link       link type
     *
     * @return mixed value of first field in first row from result
     *               or false if not found
     */
    public function fetchValue(
        string $query,
        int $row_number = 0,
        $field = 0,
        $link = DatabaseInterface::CONNECT_USER
    );

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
     * @param string $type  NUM|ASSOC|BOTH returned array should either numeric
     *                      associative or both
     * @param int    $link  link type
     */
    public function fetchSingleRow(
        string $query,
        string $type = 'ASSOC',
        $link = DatabaseInterface::CONNECT_USER
    ): ?array;

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
     * @param string           $query   query to execute
     * @param string|int|array $key     field-name or offset
     *                                  used as key for
     *                                  array or array of
     *                                  those
     * @param string|int       $value   value-name or offset
     *                                  used as value for
     *                                  array
     * @param int              $link    link type
     * @param int              $options query options
     *
     * @return array resultrows or values indexed by $key
     */
    public function fetchResult(
        string $query,
        $key = null,
        $value = null,
        $link = DatabaseInterface::CONNECT_USER,
        int $options = 0
    );

    /**
     * Get supported SQL compatibility modes
     *
     * @return array supported SQL compatibility modes
     */
    public function getCompatibilities(): array;

    /**
     * returns warnings for last query
     *
     * @param int $link link type
     *
     * @return array warnings
     */
    public function getWarnings($link = DatabaseInterface::CONNECT_USER): array;

    /**
     * returns an array of PROCEDURE or FUNCTION names for a db
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION
     * @param int    $link  link type
     *
     * @return array the procedure names or function names
     */
    public function getProceduresOrFunctions(
        string $db,
        string $which,
        $link = DatabaseInterface::CONNECT_USER
    ): array;

    /**
     * returns the definition of a specific PROCEDURE, FUNCTION, EVENT or VIEW
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION | EVENT | VIEW
     * @param string $name  the procedure|function|event|view name
     * @param int    $link  link type
     *
     * @return string|null the definition
     */
    public function getDefinition(
        string $db,
        string $which,
        string $name,
        $link = DatabaseInterface::CONNECT_USER
    ): ?string;

    /**
     * returns details about the PROCEDUREs or FUNCTIONs for a specific database
     * or details about a specific routine
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION or null for both
     * @param string $name  name of the routine (to fetch a specific routine)
     *
     * @return array information about ROCEDUREs or FUNCTIONs
     */
    public function getRoutines(string $db, ?string $which = null, string $name = ''): array;

    /**
     * returns details about the EVENTs for a specific database
     *
     * @param string $db   db name
     * @param string $name event name
     *
     * @return array information about EVENTs
     */
    public function getEvents(string $db, string $name = ''): array;

    /**
     * returns details about the TRIGGERs for a specific table or database
     *
     * @param string $db        db name
     * @param string $table     table name
     * @param string $delimiter the delimiter to use (may be empty)
     *
     * @return array information about triggers (may be empty)
     */
    public function getTriggers(string $db, string $table = '', $delimiter = '//');

    /**
     * gets the current user with host
     *
     * @return string the current user i.e. user@host
     */
    public function getCurrentUser(): string;

    /**
     * Checks if current user is superuser
     *
     * @return bool Whether user is a superuser
     */
    public function isSuperUser(): bool;

    public function isGrantUser(): bool;

    public function isCreateUser(): bool;

    public function isConnected(): bool;

    /**
     * Get the current user and host
     *
     * @return array array of username and hostname
     */
    public function getCurrentUserAndHost(): array;

    /**
     * Returns value for lower_case_table_names variable
     *
     * @return string|bool
     */
    public function getLowerCaseNames();

    /**
     * connects to the database server
     *
     * @param int        $mode   Connection mode on of CONNECT_USER, CONNECT_CONTROL
     *                           or CONNECT_AUXILIARY.
     * @param array|null $server Server information like host/port/socket/persistent
     * @param int        $target How to store connection link, defaults to $mode
     *
     * @return mixed false on error or a connection object on success
     */
    public function connect(int $mode, ?array $server = null, ?int $target = null);

    /**
     * selects given database
     *
     * @param string $dbname database name to select
     * @param int    $link   link type
     */
    public function selectDb(string $dbname, $link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param object $result result set identifier
     */
    public function fetchArray($result): ?array;

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result result set identifier
     */
    public function fetchAssoc($result): ?array;

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result result set identifier
     */
    public function fetchRow($result): ?array;

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param object $result database result
     * @param int    $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, int $offset): bool;

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     */
    public function freeResult($result): void;

    /**
     * Check if there are any more query results from a multi query
     *
     * @param int $link link type
     *
     * @return bool true or false
     */
    public function moreResults($link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * Prepare next result from multi_query
     *
     * @param int $link link type
     *
     * @return bool true or false
     */
    public function nextResult($link = DatabaseInterface::CONNECT_USER): bool;

    /**
     * Store the result returned from multi query
     *
     * @param int $link link type
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link = DatabaseInterface::CONNECT_USER);

    /**
     * Returns a string representing the type of connection used
     *
     * @param int $link link type
     *
     * @return string|bool type of connection used
     */
    public function getHostInfo($link = DatabaseInterface::CONNECT_USER);

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param int $link link type
     *
     * @return int|bool version of the MySQL protocol used
     */
    public function getProtoInfo($link = DatabaseInterface::CONNECT_USER);

    /**
     * returns a string that represents the client library version
     *
     * @param int $link link type
     *
     * @return string MySQL client library version
     */
    public function getClientInfo($link = DatabaseInterface::CONNECT_USER): string;

    /**
     * returns last error message or false if no errors occurred
     *
     * @param int $link link type
     *
     * @return string|bool error or false
     */
    public function getError($link = DatabaseInterface::CONNECT_USER);

    /**
     * returns the number of rows returned by last query
     *
     * @param object $result result set identifier
     *
     * @return string|int
     */
    public function numRows($result);

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     *
     * @param int $link link type
     *
     * @return int|bool
     */
    public function insertId($link = DatabaseInterface::CONNECT_USER);

    /**
     * returns the number of rows affected by last query
     *
     * @param int  $link           link type
     * @param bool $get_from_cache whether to retrieve from cache
     *
     * @return int|bool
     */
    public function affectedRows($link = DatabaseInterface::CONNECT_USER, bool $get_from_cache = true);

    /**
     * returns metainfo for fields in $result
     *
     * @param object $result result set identifier
     *
     * @return mixed meta info for fields in $result
     */
    public function getFieldsMeta($result);

    /**
     * return number of fields in given $result
     *
     * @param object $result result set identifier
     *
     * @return int field count
     */
    public function numFields($result): int;

    /**
     * returns the length of the given field $i in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return int|bool length of field
     */
    public function fieldLen($result, int $i);

    /**
     * returns name of $i. field in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, int $i): string;

    /**
     * returns concatenated string of human readable field flags
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i): string;

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $str  string to be escaped
     * @param mixed  $link optional database link to use
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(string $str, $link = DatabaseInterface::CONNECT_USER);

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
     * @param string $db_name    DB name
     * @param string $table_name Table name
     */
    public function getTable(string $db_name, string $table_name): Table;

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
     * @param int    $link  Link type.
     *
     * @return object|false A statement object or false.
     */
    public function prepare(string $query, $link = DatabaseInterface::CONNECT_USER);
}
