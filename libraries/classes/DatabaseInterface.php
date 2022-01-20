<?php
/**
 * Main interface for database interactions
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use mysqli_result;
use PhpMyAdmin\Database\DatabaseList;
use PhpMyAdmin\Dbal\DbalInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Dbal\DbiMysqli;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Query\Cache;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\Utils\SessionCache;
use const E_USER_WARNING;
use const LOG_INFO;
use const LOG_NDELAY;
use const LOG_PID;
use const LOG_USER;
use const SORT_ASC;
use const SORT_DESC;
use function array_diff;
use function array_keys;
use function array_map;
use function array_merge;
use function array_multisort;
use function array_reverse;
use function array_shift;
use function array_slice;
use function basename;
use function closelog;
use function count;
use function defined;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function mb_strtolower;
use function microtime;
use function openlog;
use function reset;
use function rtrim;
use function sprintf;
use function stripos;
use function strlen;
use function strncmp;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use function syslog;
use function trigger_error;
use function uasort;
use function uksort;
use function usort;

/**
 * Main interface for database interactions
 */
class DatabaseInterface implements DbalInterface
{
    /**
     * Force STORE_RESULT method, ignored by classic MySQL.
     */
    public const QUERY_STORE = 1;
    /**
     * Do not read whole query.
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
     * User connection.
     */
    public const CONNECT_USER = 0x100;
    /**
     * Control user connection.
     */
    public const CONNECT_CONTROL = 0x101;
    /**
     * Auxiliary connection.
     *
     * Used for example for replication setup.
     */
    public const CONNECT_AUXILIARY = 0x102;

    /** @var DbiExtension */
    private $extension;

    /**
     * Opened database links
     *
     * @var array
     */
    private $links;

    /** @var array Current user and host cache */
    private $currentUser;

    /** @var string|null lower_case_table_names value cache */
    private $lowerCaseTableNames = null;

    /** @var bool Whether connection is MariaDB */
    private $isMariaDb = false;
    /** @var bool Whether connection is Percona */
    private $isPercona = false;
    /** @var int Server version as number */
    private $versionInt = 55000;
    /** @var string Server version */
    private $versionString = '5.50.0';
    /** @var string Server version comment */
    private $versionComment = '';

    /** @var Types MySQL types data */
    public $types;

    /** @var Relation */
    private $relation;

    /** @var Cache */
    private $cache;

    /**
     * @param DbiExtension $ext Object to be used for database queries
     */
    public function __construct(DbiExtension $ext)
    {
        $this->extension = $ext;
        $this->links = [];
        if (defined('TESTSUITE')) {
            $this->links[self::CONNECT_USER] = 1;
            $this->links[self::CONNECT_CONTROL] = 2;
        }
        $this->currentUser = [];
        $this->cache = new Cache();
        $this->types = new Types($this);
        $this->relation = new Relation($this);
    }

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
        $link = self::CONNECT_USER,
        int $options = 0,
        bool $cache_affected_rows = true
    ) {
        $result = $this->tryQuery($query, $link, $options, $cache_affected_rows);

        if (! $result) {
            Generator::mysqlDie($this->getError($link), $query);

            return false;
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
     * @param string $query               query to run
     * @param mixed  $link                link type
     * @param int    $options             query options
     * @param bool   $cache_affected_rows whether to cache affected row
     *
     * @return mixed
     */
    public function tryQuery(
        string $query,
        $link = self::CONNECT_USER,
        int $options = 0,
        bool $cache_affected_rows = true
    ) {
        $debug = isset($GLOBALS['cfg']['DBG']) ? $GLOBALS['cfg']['DBG']['sql'] : false;
        if (! isset($this->links[$link])) {
            return false;
        }

        $time = 0;
        if ($debug) {
            $time = microtime(true);
        }

        $result = $this->extension->realQuery($query, $this->links[$link], $options);

        if ($cache_affected_rows) {
            $GLOBALS['cached_affected_rows'] = $this->affectedRows($link, false);
        }

        if ($debug) {
            $time = microtime(true) - $time;
            $errorMessage = $this->getError($link);
            Utilities::debugLogQueryIntoSession(
                $query,
                is_string($errorMessage) ? $errorMessage : null,
                $result,
                $time
            );
            if ($GLOBALS['cfg']['DBG']['sqllog']) {
                $warningsCount = '';
                if (($options & self::QUERY_STORE) == self::QUERY_STORE) {
                    if (isset($this->links[$link]->warning_count)) {
                        $warningsCount = $this->links[$link]->warning_count;
                    }
                }

                openlog('phpMyAdmin', LOG_NDELAY | LOG_PID, LOG_USER);

                syslog(
                    LOG_INFO,
                    sprintf(
                        'SQL[%s?route=%s]: %0.3f(W:%s,C:%s,L:0x%02X) > %s',
                        basename($_SERVER['SCRIPT_NAME']),
                        Routing::getCurrentRoute(),
                        $time,
                        $warningsCount,
                        $cache_affected_rows ? 'y' : 'n',
                        $link,
                        $query
                    )
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
     * Run multi query statement and return results
     *
     * @param string $multiQuery multi query statement to execute
     * @param int    $linkIndex  index of the opened database link
     *
     * @return mysqli_result[]|bool (false)
     */
    public function tryMultiQuery(
        string $multiQuery = '',
        $linkIndex = self::CONNECT_USER
    ) {
        if (! isset($this->links[$linkIndex])) {
            return false;
        }

        return $this->extension->realMultiQuery($this->links[$linkIndex], $multiQuery);
    }

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     * @param mixed  $link     mysql link resource|object
     *
     * @return array   tables names
     */
    public function getTables(string $database, $link = self::CONNECT_USER): array
    {
        $tables = $this->fetchResult(
            'SHOW TABLES FROM ' . Util::backquote($database) . ';',
            null,
            0,
            $link,
            self::QUERY_STORE
        );
        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($tables, 'strnatcasecmp');
        }

        return $tables;
    }

    /**
     * returns
     *
     * @param string $database name of database
     * @param array  $tables   list of tables to search for for relations
     * @param int    $link     mysql link resource|object
     *
     * @return array           array of found foreign keys
     */
    public function getForeignKeyConstrains(string $database, array $tables, $link = self::CONNECT_USER): array
    {
        $tablesListForQuery = '';
        foreach ($tables as $table) {
            $tablesListForQuery .= "'" . $this->escapeString($table) . "',";
        }
        $tablesListForQuery = rtrim($tablesListForQuery, ',');

        return $this->fetchResult(
            QueryGenerator::getInformationSchemaForeignKeyConstraintsRequest(
                $this->escapeString($database),
                $tablesListForQuery
            ),
            null,
            null,
            $link,
            self::QUERY_STORE
        );
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
        $link = self::CONNECT_USER
    ): array {
        if ($limit_count === true) {
            $limit_count = $GLOBALS['cfg']['MaxTableList'];
        }

        $databases = [$database];

        $tables = [];

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $sql_where_table = QueryGenerator::getTableCondition(
                is_array($table) ? array_map(
                    [
                        $this,
                        'escapeString',
                    ],
                    $table
                ) : $this->escapeString($table),
                $tbl_is_group,
                $table_type
            );

            // for PMA bc:
            // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
            //
            // on non-Windows servers,
            // added BINARY in the WHERE clause to force a case sensitive
            // comparison (if we are looking for the db Aa we don't want
            // to find the db aa)
            $this_databases = array_map(
                [
                    $this,
                    'escapeString',
                ],
                $databases
            );

            $sql = QueryGenerator::getSqlForTablesFull($this_databases, $sql_where_table);

            // Sort the tables
            $sql .= ' ORDER BY ' . $sort_by . ' ' . $sort_order;

            if ($limit_count) {
                $sql .= ' LIMIT ' . $limit_count . ' OFFSET ' . $limit_offset;
            }

            $tables = $this->fetchResult(
                $sql,
                [
                    'TABLE_SCHEMA',
                    'TABLE_NAME',
                ],
                null,
                $link
            );

            if ($sort_by === 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                // here, the array's first key is by schema name
                foreach ($tables as $one_database_name => $one_database_tables) {
                    uksort($one_database_tables, 'strnatcasecmp');

                    if ($sort_order === 'DESC') {
                        $one_database_tables = array_reverse($one_database_tables);
                    }
                    $tables[$one_database_name] = $one_database_tables;
                }
            } elseif ($sort_by === 'Data_length') {
                // Size = Data_length + Index_length
                foreach ($tables as $one_database_name => $one_database_tables) {
                    uasort(
                        $one_database_tables,
                        /**
                         * @param array $a
                         * @param array $b
                         */
                        static function ($a, $b) {
                            $aLength = $a['Data_length'] + $a['Index_length'];
                            $bLength = $b['Data_length'] + $b['Index_length'];

                            return $aLength <=> $bLength;
                        }
                    );

                    if ($sort_order === 'DESC') {
                        $one_database_tables = array_reverse($one_database_tables);
                    }
                    $tables[$one_database_name] = $one_database_tables;
                }
            }
        }

        // If permissions are wrong on even one database directory,
        // information_schema does not return any table info for any database
        // this is why we fall back to SHOW TABLE STATUS even for MySQL >= 50002
        if (empty($tables)) {
            foreach ($databases as $each_database) {
                if ($table || ($tbl_is_group === true) || ! empty($table_type)) {
                    $sql = 'SHOW TABLE STATUS FROM '
                        . Util::backquote($each_database)
                        . ' WHERE';
                    $needAnd = false;
                    if ($table || ($tbl_is_group === true)) {
                        if (is_array($table)) {
                            $sql .= ' `Name` IN (\''
                                . implode(
                                    '\', \'',
                                    array_map(
                                        [
                                            $this,
                                            'escapeString',
                                        ],
                                        $table,
                                        $link
                                    )
                                ) . '\')';
                        } else {
                            $sql .= " `Name` LIKE '"
                                . Util::escapeMysqlWildcards(
                                    $this->escapeString($table, $link)
                                )
                                . "%'";
                        }
                        $needAnd = true;
                    }
                    if (! empty($table_type)) {
                        if ($needAnd) {
                            $sql .= ' AND';
                        }
                        if ($table_type === 'view') {
                            $sql .= " `Comment` = 'VIEW'";
                        } elseif ($table_type === 'table') {
                            $sql .= " `Comment` != 'VIEW'";
                        }
                    }
                } else {
                    $sql = 'SHOW TABLE STATUS FROM '
                        . Util::backquote($each_database);
                }

                $each_tables = $this->fetchResult($sql, 'Name', null, $link);

                // Sort naturally if the config allows it and we're sorting
                // the Name column.
                if ($sort_by === 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                    uksort($each_tables, 'strnatcasecmp');

                    if ($sort_order === 'DESC') {
                        $each_tables = array_reverse($each_tables);
                    }
                } else {
                    // Prepare to sort by creating array of the selected sort
                    // value to pass to array_multisort

                    // Size = Data_length + Index_length
                    if ($sort_by === 'Data_length') {
                        foreach ($each_tables as $table_name => $table_data) {
                            ${$sort_by}[$table_name] = strtolower(
                                (string) ($table_data['Data_length']
                                + $table_data['Index_length'])
                            );
                        }
                    } else {
                        foreach ($each_tables as $table_name => $table_data) {
                            ${$sort_by}[$table_name]
                                = strtolower($table_data[$sort_by] ?? '');
                        }
                    }

                    if (! empty($$sort_by)) {
                        if ($sort_order === 'DESC') {
                            array_multisort($$sort_by, SORT_DESC, $each_tables);
                        } else {
                            array_multisort($$sort_by, SORT_ASC, $each_tables);
                        }
                    }

                    // cleanup the temporary sort array
                    unset($$sort_by);
                }

                if ($limit_count) {
                    $each_tables = array_slice(
                        $each_tables,
                        $limit_offset,
                        $limit_count
                    );
                }

                $tables[$each_database] = Compatibility::getISCompatForGetTablesFull($each_tables, $each_database);
            }
        }

        // cache table data
        // so Table does not require to issue SHOW TABLE STATUS again
        $this->cache->cacheTableData($tables, $table);

        if (isset($tables[$database])) {
            return $tables[$database];
        }

        if (isset($tables[mb_strtolower($database)])) {
            // on windows with lower_case_table_names = 1
            // MySQL returns
            // with SHOW DATABASES or information_schema.SCHEMATA: `Test`
            // but information_schema.TABLES gives `test`
            // see https://github.com/phpmyadmin/phpmyadmin/issues/8402
            return $tables[mb_strtolower($database)];
        }

        return $tables;
    }

    /**
     * Get VIEWs in a particular database
     *
     * @param string $db Database name to look in
     *
     * @return array Set of VIEWs inside the database
     */
    public function getVirtualTables(string $db): array
    {
        $tables_full = $this->getTablesFull($db);
        $views = [];

        foreach ($tables_full as $table => $tmp) {
            $table = $this->getTable($db, (string) $table);
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
     * @param string   $database     database
     * @param bool     $force_stats  retrieve stats also for MySQL < 5
     * @param int      $link         link type
     * @param string   $sort_by      column to order by
     * @param string   $sort_order   ASC or DESC
     * @param int      $limit_offset starting offset for LIMIT
     * @param bool|int $limit_count  row count for LIMIT or true
     *                               for $GLOBALS['cfg']['MaxDbList']
     *
     * @return array
     *
     * @todo    move into ListDatabase?
     */
    public function getDatabasesFull(
        ?string $database = null,
        bool $force_stats = false,
        $link = self::CONNECT_USER,
        string $sort_by = 'SCHEMA_NAME',
        string $sort_order = 'ASC',
        int $limit_offset = 0,
        $limit_count = false
    ): array {
        $sort_order = strtoupper($sort_order);

        if ($limit_count === true) {
            $limit_count = $GLOBALS['cfg']['MaxDbList'];
        }

        $apply_limit_and_order_manual = true;

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            /**
             * if $GLOBALS['cfg']['NaturalOrder'] is enabled, we cannot use LIMIT
             * cause MySQL does not support natural ordering,
             * we have to do it afterward
             */
            $limit = '';
            if (! $GLOBALS['cfg']['NaturalOrder']) {
                if ($limit_count) {
                    $limit = ' LIMIT ' . $limit_count . ' OFFSET ' . $limit_offset;
                }

                $apply_limit_and_order_manual = false;
            }

            // get table information from information_schema
            $sqlWhereSchema = '';
            if (! empty($database)) {
                $sqlWhereSchema = 'WHERE `SCHEMA_NAME` LIKE \''
                    . $this->escapeString($database, $link) . '\'';
            }

            $sql = QueryGenerator::getInformationSchemaDatabasesFullRequest(
                $force_stats,
                $sqlWhereSchema,
                $sort_by,
                $sort_order,
                $limit
            );

            $databases = $this->fetchResult($sql, 'SCHEMA_NAME', null, $link);

            $mysql_error = $this->getError($link);
            if (! count($databases) && isset($GLOBALS['errno'])) {
                Generator::mysqlDie($mysql_error, $sql);
            }

            // display only databases also in official database list
            // f.e. to apply hide_db and only_db
            $drops = array_diff(
                array_keys($databases),
                (array) $GLOBALS['dblist']->databases
            );
            foreach ($drops as $drop) {
                unset($databases[$drop]);
            }
        } else {
            $databases = [];
            foreach ($GLOBALS['dblist']->databases as $database_name) {
                // Compatibility with INFORMATION_SCHEMA output
                $databases[$database_name]['SCHEMA_NAME']      = $database_name;

                $databases[$database_name]['DEFAULT_COLLATION_NAME']
                    = $this->getDbCollation($database_name);

                if (! $force_stats) {
                    continue;
                }

                // get additional info about tables
                $databases[$database_name]['SCHEMA_TABLES']          = 0;
                $databases[$database_name]['SCHEMA_TABLE_ROWS']      = 0;
                $databases[$database_name]['SCHEMA_DATA_LENGTH']     = 0;
                $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH'] = 0;
                $databases[$database_name]['SCHEMA_INDEX_LENGTH']    = 0;
                $databases[$database_name]['SCHEMA_LENGTH']          = 0;
                $databases[$database_name]['SCHEMA_DATA_FREE']       = 0;

                $res = $this->query(
                    'SHOW TABLE STATUS FROM '
                    . Util::backquote($database_name) . ';'
                );

                if ($res === false) {
                    unset($res);
                    continue;
                }

                while ($row = $this->fetchAssoc($res)) {
                    $databases[$database_name]['SCHEMA_TABLES']++;
                    $databases[$database_name]['SCHEMA_TABLE_ROWS']
                        += $row['Rows'];
                    $databases[$database_name]['SCHEMA_DATA_LENGTH']
                        += $row['Data_length'];
                    $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH']
                        += $row['Max_data_length'];
                    $databases[$database_name]['SCHEMA_INDEX_LENGTH']
                        += $row['Index_length'];

                    // for InnoDB, this does not contain the number of
                    // overhead bytes but the total free space
                    if ($row['Engine'] !== 'InnoDB') {
                        $databases[$database_name]['SCHEMA_DATA_FREE']
                            += $row['Data_free'];
                    }
                    $databases[$database_name]['SCHEMA_LENGTH']
                        += $row['Data_length'] + $row['Index_length'];
                }
                $this->freeResult($res);
                unset($res);
            }
        }

        /**
         * apply limit and order manually now
         * (caused by older MySQL < 5 or $GLOBALS['cfg']['NaturalOrder'])
         */
        if ($apply_limit_and_order_manual) {
            usort(
                $databases,
                static function ($a, $b) use ($sort_by, $sort_order) {
                    return Utilities::usortComparisonCallback($a, $b, $sort_by, $sort_order);
                }
            );

            /**
             * now apply limit
             */
            if ($limit_count) {
                $databases = array_slice($databases, $limit_offset, $limit_count);
            }
        }

        return $databases;
    }

    /**
     * returns detailed array with all columns for sql
     *
     * @param string $sql_query    target SQL query to get columns
     * @param array  $view_columns alias for columns
     *
     * @return array
     */
    public function getColumnMapFromSql(string $sql_query, array $view_columns = []): array
    {
        $result = $this->tryQuery($sql_query);

        if ($result === false) {
            return [];
        }

        $meta = $this->getFieldsMeta(
            $result
        );

        $nbFields = count($meta);
        if ($nbFields <= 0) {
            return [];
        }

        $column_map = [];
        $nbColumns = count($view_columns);

        for ($i = 0; $i < $nbFields; $i++) {
            $map = [];
            $map['table_name'] = $meta[$i]->table;
            $map['refering_column'] = $meta[$i]->name;

            if ($nbColumns > 1) {
                $map['real_column'] = $view_columns[$i];
            }

            $column_map[] = $map;
        }

        return $column_map;
    }

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
        $link = self::CONNECT_USER
    ): array {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            [$sql, $arrayKeys] = QueryGenerator::getInformationSchemaColumnsFullRequest(
                $database !== null ? $this->escapeString($database, $link) : null,
                $table !== null ? $this->escapeString($table, $link) : null,
                $column !== null ? $this->escapeString($column, $link) : null
            );

            return $this->fetchResult($sql, $arrayKeys, null, $link);
        }

        $columns = [];
        if ($database === null) {
            foreach ($GLOBALS['dblist']->databases as $database) {
                $columns[$database] = $this->getColumnsFull(
                    $database,
                    null,
                    null,
                    $link
                );
            }

            return $columns;
        }

        if ($table === null) {
            $tables = $this->getTables($database);
            foreach ($tables as $table) {
                $columns[$table] = $this->getColumnsFull(
                    $database,
                    $table,
                    null,
                    $link
                );
            }

            return $columns;
        }
        $sql = 'SHOW FULL COLUMNS FROM '
            . Util::backquote($database) . '.' . Util::backquote($table);
        if ($column !== null) {
            $sql .= " LIKE '" . $this->escapeString($column, $link) . "'";
        }

        $columns = $this->fetchResult($sql, 'Field', null, $link);

        $columns = Compatibility::getISCompatForGetColumnsFull($columns, $database, $table);

        if ($column !== null) {
            return reset($columns);
        }

        return $columns;
    }

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
        $link = self::CONNECT_USER
    ): array {
        $sql = QueryGenerator::getColumnsSql(
            $database,
            $table,
            $column === null ? null : Util::escapeMysqlWildcards($this->escapeString($column)),
            $full
        );
        $fields = $this->fetchResult($sql, 'Field', null, $link);
        if (! is_array($fields) || count($fields) === 0) {
            return [];
        }
        // Check if column is a part of multiple-column index and set its 'Key'.
        $indexes = Index::getFromTable($table, $database);
        foreach ($fields as $field => $field_data) {
            if (! empty($field_data['Key'])) {
                continue;
            }

            foreach ($indexes as $index) {
                /** @var Index $index */
                if (! $index->hasColumn($field)) {
                    continue;
                }

                $index_columns = $index->getColumns();
                if ($index_columns[$field]->getSeqInIndex() <= 1) {
                    continue;
                }

                if ($index->isUnique()) {
                    $fields[$field]['Key'] = 'UNI';
                } else {
                    $fields[$field]['Key'] = 'MUL';
                }
            }
        }

        return $column != null ? array_shift($fields) : $fields;
    }

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
        $link = self::CONNECT_USER
    ): ?array {
        $sql = QueryGenerator::getColumnsSql($database, $table);
        // We only need the 'Field' column which contains the table's column names
        $fields = array_keys($this->fetchResult($sql, 'Field', null, $link));

        if (! is_array($fields) || count($fields) === 0) {
            return null;
        }

        return $fields;
    }

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
        $link = self::CONNECT_USER
    ): array {
        $sql = QueryGenerator::getTableIndexesSql($database, $table);
        $indexes = $this->fetchResult($sql, null, null, $link);

        if (! is_array($indexes) || count($indexes) < 1) {
            return [];
        }

        return $indexes;
    }

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
        int $type = self::GETVAR_SESSION,
        $link = self::CONNECT_USER
    ) {
        switch ($type) {
            case self::GETVAR_SESSION:
                $modifier = ' SESSION';
                break;
            case self::GETVAR_GLOBAL:
                $modifier = ' GLOBAL';
                break;
            default:
                $modifier = '';
        }

        return $this->fetchValue(
            'SHOW' . $modifier . ' VARIABLES LIKE \'' . $var . '\';',
            0,
            1,
            $link
        );
    }

    /**
     * Sets new value for a variable if it is different from the current value
     *
     * @param string $var   variable name
     * @param string $value value to set
     * @param mixed  $link  mysql link resource|object
     *
     * @return bool whether query was a successful
     */
    public function setVariable(
        string $var,
        string $value,
        $link = self::CONNECT_USER
    ): bool {
        $current_value = $this->getVariable(
            $var,
            self::GETVAR_SESSION,
            $link
        );
        if ($current_value == $value) {
            return true;
        }

        return $this->query('SET ' . $var . ' = ' . $value . ';', $link);
    }

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnect(): void
    {
        $version = $this->fetchSingleRow(
            'SELECT @@version, @@version_comment',
            'ASSOC',
            self::CONNECT_USER
        );

        if (is_array($version)) {
            $this->versionString = $version['@@version'] ?? '';
            $this->versionInt = Utilities::versionToInt($this->versionString);
            $this->versionComment = $version['@@version_comment'] ?? '';
            if (stripos($this->versionString, 'mariadb') !== false) {
                $this->isMariaDb = true;
            }
            if (stripos($this->versionComment, 'percona') !== false) {
                $this->isPercona = true;
            }
        }

        if ($this->versionInt > 50503) {
            $default_charset = 'utf8mb4';
            $default_collation = 'utf8mb4_general_ci';
        } else {
            $default_charset = 'utf8';
            $default_collation = 'utf8_general_ci';
        }
        $GLOBALS['collation_connection'] = $default_collation;
        $GLOBALS['charset_connection'] = $default_charset;
        $this->query(
            sprintf('SET NAMES \'%s\' COLLATE \'%s\';', $default_charset, $default_collation),
            self::CONNECT_USER,
            self::QUERY_STORE
        );

        /* Locale for messages */
        $locale = LanguageManager::getInstance()->getCurrentLanguage()->getMySQLLocale();
        if (! empty($locale)) {
            $this->query(
                "SET lc_messages = '" . $locale . "';",
                self::CONNECT_USER,
                self::QUERY_STORE
            );
        }

        // Set timezone for the session, if required.
        if ($GLOBALS['cfg']['Server']['SessionTimeZone'] != '') {
            $sql_query_tz = 'SET ' . Util::backquote('time_zone') . ' = '
                . '\''
                . $this->escapeString($GLOBALS['cfg']['Server']['SessionTimeZone'])
                . '\'';

            if (! $this->tryQuery($sql_query_tz)) {
                $error_message_tz = sprintf(
                    __(
                        'Unable to use timezone "%1$s" for server %2$d. '
                        . 'Please check your configuration setting for '
                        . '[em]$cfg[\'Servers\'][%3$d][\'SessionTimeZone\'][/em]. '
                        . 'phpMyAdmin is currently using the default time zone '
                        . 'of the database server.'
                    ),
                    $GLOBALS['cfg']['Server']['SessionTimeZone'],
                    $GLOBALS['server'],
                    $GLOBALS['server']
                );

                trigger_error($error_message_tz, E_USER_WARNING);
            }
        }

        /* Loads closest context to this version. */
        Context::loadClosest(
            ($this->isMariaDb ? 'MariaDb' : 'MySql') . $this->versionInt
        );

        /**
         * the DatabaseList class as a stub for the ListDatabase class
         */
        $GLOBALS['dblist'] = new DatabaseList();
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
        if ($charset === 'utf8' && strncmp('utf8mb4_', $collation, 8) == 0) {
            $collation = 'utf8_' . substr($collation, 8);
        }
        $result = $this->tryQuery(
            "SET collation_connection = '"
            . $this->escapeString($collation, self::CONNECT_USER)
            . "';",
            self::CONNECT_USER,
            self::QUERY_STORE
        );
        if ($result === false) {
            trigger_error(
                __('Failed to set configured collation connection!'),
                E_USER_WARNING
            );
        } else {
            $GLOBALS['collation_connection'] = $collation;
        }
    }

    /**
     * This function checks and initializes the phpMyAdmin configuration
     * storage state before it is used into session cache.
     *
     * @return void
     */
    public function initRelationParamsCache()
    {
        $storageDbName = $GLOBALS['cfg']['Server']['pmadb'] ?? '';
        // Use "phpmyadmin" as a default database name to check to keep the behavior consistent
        $storageDbName = $storageDbName !== null && is_string($storageDbName) && $storageDbName !== ''
            ? $storageDbName
            : 'phpmyadmin';

        // This will make users not having explicitly listed databases
        // have config values filled by the default phpMyAdmin storage table name values
        $this->relation->fixPmaTables($storageDbName, false);

        // This global will be changed if fixPmaTables did find one valid table
        $storageDbName = $GLOBALS['cfg']['Server']['pmadb'] ?? '';

        // Empty means that until now no pmadb was found eligible
        if (! empty($storageDbName)) {
            return;
        }

        $this->relation->fixPmaTables($GLOBALS['db'], false);
    }

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     */
    public function postConnectControl(): void
    {
        // If Zero configuration mode enabled, check PMA tables in current db.
        if ($GLOBALS['cfg']['ZeroConf'] != true) {
            return;
        }

        /**
         * the DatabaseList class as a stub for the ListDatabase class
         */
        $GLOBALS['dblist'] = new DatabaseList();

        $this->initRelationParamsCache();
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
     * @param string     $query      The query to execute
     * @param int        $row_number row to fetch the value from,
     *                               starting at 0, with 0 being default
     * @param int|string $field      field to fetch the value from,
     *                               starting at 0, with 0 being default
     * @param int        $link       link type
     *
     * @return mixed value of first field in first row from result
     *               or false if not found
     */
    public function fetchValue(
        string $query,
        int $row_number = 0,
        $field = 0,
        $link = self::CONNECT_USER
    ) {
        $value = false;

        $result = $this->tryQuery(
            $query,
            $link,
            self::QUERY_STORE,
            false
        );
        if ($result === false) {
            return false;
        }

        // return false if result is empty or false
        // or requested row is larger than rows in result
        if ($this->numRows($result) < $row_number + 1) {
            return $value;
        }

        // get requested row
        for ($i = 0; $i <= $row_number; $i++) {
            // if $field is an integer use non associative mysql fetch function
            if (is_int($field)) {
                $row = $this->fetchRow($result);
                continue;
            }
            $row = $this->fetchAssoc($result);
        }
        $this->freeResult($result);

        // return requested field
        if (isset($row[$field])) {
            $value = $row[$field];
        }

        return $value;
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
     * @param string $type  NUM|ASSOC|BOTH returned array should either numeric
     *                      associative or both
     * @param int    $link  link type
     */
    public function fetchSingleRow(
        string $query,
        string $type = 'ASSOC',
        $link = self::CONNECT_USER
    ): ?array {
        $result = $this->tryQuery(
            $query,
            $link,
            self::QUERY_STORE,
            false
        );
        if ($result === false) {
            return null;
        }

        if (! $this->numRows($result)) {
            return null;
        }

        switch ($type) {
            case 'NUM':
                $row = $this->fetchRow($result);
                break;
            case 'ASSOC':
                $row = $this->fetchAssoc($result);
                break;
            case 'BOTH':
            default:
                $row = $this->fetchArray($result);
                break;
        }

        $this->freeResult($result);

        return $row;
    }

    /**
     * Returns row or element of a row
     *
     * @param array|string    $row   Row to process
     * @param string|int|null $value Which column to return
     *
     * @return mixed
     */
    private function fetchValueOrValueByIndex($row, $value)
    {
        return $value === null ? $row : $row[$value];
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
     * @param string           $query   query to execute
     * @param string|int|array $key     field-name or offset
     *                                  used as key for array
     *                                  or array of those
     * @param string|int       $value   value-name or offset
     *                                  used as value for array
     * @param int              $link    link type
     * @param int              $options query options
     *
     * @return array resultrows or values indexed by $key
     */
    public function fetchResult(
        string $query,
        $key = null,
        $value = null,
        $link = self::CONNECT_USER,
        int $options = 0
    ) {
        $resultrows = [];

        $result = $this->tryQuery($query, $link, $options, false);

        // return empty array if result is empty or false
        if ($result === false) {
            return $resultrows;
        }

        $fetch_function = 'fetchAssoc';

        // no nested array if only one field is in result
        if ($key === null && $this->numFields($result) === 1) {
            $value = 0;
            $fetch_function = 'fetchRow';
        }

        // if $key is an integer use non associative mysql fetch function
        if (is_int($key)) {
            $fetch_function = 'fetchRow';
        }

        if ($key === null) {
            while ($row = $this->$fetch_function($result)) {
                $resultrows[] = $this->fetchValueOrValueByIndex($row, $value);
            }
        } else {
            if (is_array($key)) {
                while ($row = $this->$fetch_function($result)) {
                    $result_target =& $resultrows;
                    foreach ($key as $key_index) {
                        if ($key_index === null) {
                            $result_target =& $result_target[];
                            continue;
                        }

                        if (! isset($result_target[$row[$key_index]])) {
                            $result_target[$row[$key_index]] = [];
                        }
                        $result_target =& $result_target[$row[$key_index]];
                    }
                    $result_target = $this->fetchValueOrValueByIndex($row, $value);
                }
            } else {
                while ($row = $this->$fetch_function($result)) {
                    $resultrows[$row[$key]] = $this->fetchValueOrValueByIndex($row, $value);
                }
            }
        }

        $this->freeResult($result);

        return $resultrows;
    }

    /**
     * Get supported SQL compatibility modes
     *
     * @return array supported SQL compatibility modes
     */
    public function getCompatibilities(): array
    {
        $compats = ['NONE'];
        $compats[] = 'ANSI';
        $compats[] = 'DB2';
        $compats[] = 'MAXDB';
        $compats[] = 'MYSQL323';
        $compats[] = 'MYSQL40';
        $compats[] = 'MSSQL';
        $compats[] = 'ORACLE';
        // removed; in MySQL 5.0.33, this produces exports that
        // can't be read by POSTGRESQL (see our bug #1596328)
        //$compats[] = 'POSTGRESQL';
        $compats[] = 'TRADITIONAL';

        return $compats;
    }

    /**
     * returns warnings for last query
     *
     * @param int $link link type
     *
     * @return array warnings
     */
    public function getWarnings($link = self::CONNECT_USER): array
    {
        return $this->fetchResult('SHOW WARNINGS', null, null, $link);
    }

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
        $link = self::CONNECT_USER
    ): array {
        $shows = $this->fetchResult(
            'SHOW ' . $which . ' STATUS;',
            null,
            null,
            $link
        );
        $result = [];
        foreach ($shows as $one_show) {
            if ($one_show['Db'] != $db || $one_show['Type'] != $which) {
                continue;
            }

            $result[] = $one_show['Name'];
        }

        return $result;
    }

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
        $link = self::CONNECT_USER
    ): ?string {
        $returned_field = [
            'PROCEDURE' => 'Create Procedure',
            'FUNCTION'  => 'Create Function',
            'EVENT'     => 'Create Event',
            'VIEW'      => 'Create View',
        ];
        $query = 'SHOW CREATE ' . $which . ' '
            . Util::backquote($db) . '.'
            . Util::backquote($name);
        $result = $this->fetchValue($query, 0, $returned_field[$which], $link);

        return is_string($result) ? $result : null;
    }

    /**
     * returns details about the PROCEDUREs or FUNCTIONs for a specific database
     * or details about a specific routine
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION or null for both
     * @param string $name  name of the routine (to fetch a specific routine)
     *
     * @return array information about PROCEDUREs or FUNCTIONs
     */
    public function getRoutines(
        string $db,
        ?string $which = null,
        string $name = ''
    ): array {
        $routines = [];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaRoutinesRequest(
                $this->escapeString($db),
                Core::isValid($which, ['FUNCTION', 'PROCEDURE']) ? $which : null,
                empty($name) ? null : $this->escapeString($name)
            );
            $result = $this->fetchResult($query);
            if (! empty($result)) {
                $routines = $result;
            }
        } else {
            if ($which === 'FUNCTION' || $which == null) {
                $query = 'SHOW FUNCTION STATUS'
                    . " WHERE `Db` = '" . $this->escapeString($db) . "'";
                if (! empty($name)) {
                    $query .= " AND `Name` = '"
                        . $this->escapeString($name) . "'";
                }
                $result = $this->fetchResult($query);
                if (! empty($result)) {
                    $routines = array_merge($routines, $result);
                }
            }
            if ($which === 'PROCEDURE' || $which == null) {
                $query = 'SHOW PROCEDURE STATUS'
                    . " WHERE `Db` = '" . $this->escapeString($db) . "'";
                if (! empty($name)) {
                    $query .= " AND `Name` = '"
                        . $this->escapeString($name) . "'";
                }
                $result = $this->fetchResult($query);
                if (! empty($result)) {
                    $routines = array_merge($routines, $result);
                }
            }
        }

        $ret = [];
        foreach ($routines as $routine) {
            $one_result = [];
            $one_result['db'] = $routine['Db'];
            $one_result['name'] = $routine['Name'];
            $one_result['type'] = $routine['Type'];
            $one_result['definer'] = $routine['Definer'];
            $one_result['returns'] = $routine['DTD_IDENTIFIER'] ?? '';
            $ret[] = $one_result;
        }

        // Sort results by name
        $name = [];
        foreach ($ret as $value) {
            $name[] = $value['name'];
        }
        array_multisort($name, SORT_ASC, $ret);

        return $ret;
    }

    /**
     * returns details about the EVENTs for a specific database
     *
     * @param string $db   db name
     * @param string $name event name
     *
     * @return array information about EVENTs
     */
    public function getEvents(string $db, string $name = ''): array
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaEventsRequest(
                $this->escapeString($db),
                empty($name) ? null : $this->escapeString($name)
            );
        } else {
            $query = 'SHOW EVENTS FROM ' . Util::backquote($db);
            if (! empty($name)) {
                $query .= " WHERE `Name` = '"
                    . $this->escapeString($name) . "'";
            }
        }

        $result = [];
        $events = $this->fetchResult($query);

        foreach ($events as $event) {
            $result[] = [
                'name' => $event['Name'],
                'type' => $event['Type'],
                'status' => $event['Status'],
            ];
        }

        // Sort results by name
        $name = [];
        foreach ($result as $value) {
            $name[] = $value['name'];
        }
        array_multisort($name, SORT_ASC, $result);

        return $result;
    }

    /**
     * returns details about the TRIGGERs for a specific table or database
     *
     * @param string $db        db name
     * @param string $table     table name
     * @param string $delimiter the delimiter to use (may be empty)
     *
     * @return array information about triggers (may be empty)
     */
    public function getTriggers(string $db, string $table = '', $delimiter = '//')
    {
        $result = [];
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaTriggersRequest(
                $this->escapeString($db),
                empty($table) ? null : $this->escapeString($table)
            );
        } else {
            $query = 'SHOW TRIGGERS FROM ' . Util::backquote($db);
            if (! empty($table)) {
                $query .= " LIKE '" . $this->escapeString($table) . "';";
            }
        }

        $triggers = $this->fetchResult($query);

        foreach ($triggers as $trigger) {
            if ($GLOBALS['cfg']['Server']['DisableIS']) {
                $trigger['TRIGGER_NAME'] = $trigger['Trigger'];
                $trigger['ACTION_TIMING'] = $trigger['Timing'];
                $trigger['EVENT_MANIPULATION'] = $trigger['Event'];
                $trigger['EVENT_OBJECT_TABLE'] = $trigger['Table'];
                $trigger['ACTION_STATEMENT'] = $trigger['Statement'];
                $trigger['DEFINER'] = $trigger['Definer'];
            }
            $one_result = [];
            $one_result['name'] = $trigger['TRIGGER_NAME'];
            $one_result['table'] = $trigger['EVENT_OBJECT_TABLE'];
            $one_result['action_timing'] = $trigger['ACTION_TIMING'];
            $one_result['event_manipulation'] = $trigger['EVENT_MANIPULATION'];
            $one_result['definition'] = $trigger['ACTION_STATEMENT'];
            $one_result['definer'] = $trigger['DEFINER'];

            // do not prepend the schema name; this way, importing the
            // definition into another schema will work
            $one_result['full_trigger_name'] = Util::backquote(
                $trigger['TRIGGER_NAME']
            );
            $one_result['drop'] = 'DROP TRIGGER IF EXISTS '
                . $one_result['full_trigger_name'];
            $one_result['create'] = 'CREATE TRIGGER '
                . $one_result['full_trigger_name'] . ' '
                . $trigger['ACTION_TIMING'] . ' '
                . $trigger['EVENT_MANIPULATION']
                . ' ON ' . Util::backquote($trigger['EVENT_OBJECT_TABLE'])
                . "\n" . ' FOR EACH ROW '
                . $trigger['ACTION_STATEMENT'] . "\n" . $delimiter . "\n";

            $result[] = $one_result;
        }

        // Sort results by name
        $name = [];
        foreach ($result as $value) {
            $name[] = $value['name'];
        }
        array_multisort($name, SORT_ASC, $result);

        return $result;
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
            return SessionCache::get('is_superuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $result = $this->tryQuery(
            'SELECT 1 FROM mysql.user LIMIT 1',
            self::CONNECT_USER,
            self::QUERY_STORE
        );
        $isSuperUser = false;

        if ($result) {
            $isSuperUser = (bool) $this->numRows($result);
        }

        $this->freeResult($result);
        SessionCache::set('is_superuser', $isSuperUser);

        return $isSuperUser;
    }

    public function isGrantUser(): bool
    {
        global $cfg;

        if (SessionCache::has('is_grantuser')) {
            return SessionCache::get('is_grantuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $hasGrantPrivilege = false;

        if ($cfg['Server']['DisableIS']) {
            $grants = $this->getCurrentUserGrants();

            foreach ($grants as $grant) {
                if (strpos($grant, 'WITH GRANT OPTION') !== false) {
                    $hasGrantPrivilege = true;
                    break;
                }
            }

            SessionCache::set('is_grantuser', $hasGrantPrivilege);

            return $hasGrantPrivilege;
        }

        [$user, $host] = $this->getCurrentUserAndHost();
        $query = QueryGenerator::getInformationSchemaDataForGranteeRequest($user, $host);
        $result = $this->tryQuery($query, self::CONNECT_USER, self::QUERY_STORE);

        if ($result) {
            $hasGrantPrivilege = (bool) $this->numRows($result);
        }

        $this->freeResult($result);
        SessionCache::set('is_grantuser', $hasGrantPrivilege);

        return $hasGrantPrivilege;
    }

    public function isCreateUser(): bool
    {
        global $cfg;

        if (SessionCache::has('is_createuser')) {
            return SessionCache::get('is_createuser');
        }

        if (! $this->isConnected()) {
            return false;
        }

        $hasCreatePrivilege = false;

        if ($cfg['Server']['DisableIS']) {
            $grants = $this->getCurrentUserGrants();

            foreach ($grants as $grant) {
                if (strpos($grant, 'ALL PRIVILEGES ON *.*') !== false
                    || strpos($grant, 'CREATE USER') !== false
                ) {
                    $hasCreatePrivilege = true;
                    break;
                }
            }

            SessionCache::set('is_createuser', $hasCreatePrivilege);

            return $hasCreatePrivilege;
        }

        [$user, $host] = $this->getCurrentUserAndHost();
        $query = QueryGenerator::getInformationSchemaDataForCreateRequest($user, $host);
        $result = $this->tryQuery($query, self::CONNECT_USER, self::QUERY_STORE);

        if ($result) {
            $hasCreatePrivilege = (bool) $this->numRows($result);
        }

        $this->freeResult($result);
        SessionCache::set('is_createuser', $hasCreatePrivilege);

        return $hasCreatePrivilege;
    }

    public function isConnected(): bool
    {
        return isset($this->links[self::CONNECT_USER]);
    }

    private function getCurrentUserGrants(): array
    {
        return $this->fetchResult(
            'SHOW GRANTS FOR CURRENT_USER();',
            null,
            null,
            self::CONNECT_USER,
            self::QUERY_STORE
        );
    }

    /**
     * Get the current user and host
     *
     * @return array array of username and hostname
     */
    public function getCurrentUserAndHost(): array
    {
        if (count($this->currentUser) === 0) {
            $user = $this->getCurrentUser();
            $this->currentUser = explode('@', $user);
        }

        return $this->currentUser;
    }

    /**
     * Returns value for lower_case_table_names variable
     *
     * @return string|bool
     */
    public function getLowerCaseNames()
    {
        if ($this->lowerCaseTableNames === null) {
            $this->lowerCaseTableNames = $this->fetchValue(
                'SELECT @@lower_case_table_names'
            );
        }

        return $this->lowerCaseTableNames;
    }

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
    public function connect(int $mode, ?array $server = null, ?int $target = null)
    {
        [$user, $password, $server] = Config::getConnectionParams($mode, $server);

        if ($target === null) {
            $target = $mode;
        }

        if ($user === null || $password === null) {
            trigger_error(
                __('Missing connection parameters!'),
                E_USER_WARNING
            );

            return false;
        }

        // Do not show location and backtrace for connection errors
        $GLOBALS['error_handler']->setHideLocation(true);
        $result = $this->extension->connect(
            $user,
            $password,
            $server
        );
        $GLOBALS['error_handler']->setHideLocation(false);

        if ($result) {
            $this->links[$target] = $result;
            /* Run post connect for user connections */
            if ($target == self::CONNECT_USER) {
                $this->postConnect();
            }

            return $result;
        }

        if ($mode == self::CONNECT_CONTROL) {
            trigger_error(
                __(
                    'Connection for controluser as defined in your '
                    . 'configuration failed.'
                ),
                E_USER_WARNING
            );

            return false;
        }

        if ($mode == self::CONNECT_AUXILIARY) {
            // Do not go back to main login if connection failed
            // (currently used only in unit testing)
            return false;
        }

        return $result;
    }

    /**
     * selects given database
     *
     * @param string $dbname database name to select
     * @param int    $link   link type
     */
    public function selectDb(string $dbname, $link = self::CONNECT_USER): bool
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->selectDb($dbname, $this->links[$link]);
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param object $result result set identifier
     */
    public function fetchArray($result): ?array
    {
        return $this->extension->fetchArray($result);
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result result set identifier
     */
    public function fetchAssoc($result): ?array
    {
        return $this->extension->fetchAssoc($result);
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result result set identifier
     */
    public function fetchRow($result): ?array
    {
        return $this->extension->fetchRow($result);
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param object $result database result
     * @param int    $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, int $offset): bool
    {
        return $this->extension->dataSeek($result, $offset);
    }

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     */
    public function freeResult($result): void
    {
        $this->extension->freeResult($result);
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param int $link link type
     *
     * @return bool true or false
     */
    public function moreResults($link = self::CONNECT_USER): bool
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->moreResults($this->links[$link]);
    }

    /**
     * Prepare next result from multi_query
     *
     * @param int $link link type
     *
     * @return bool true or false
     */
    public function nextResult($link = self::CONNECT_USER): bool
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->nextResult($this->links[$link]);
    }

    /**
     * Store the result returned from multi query
     *
     * @param int $link link type
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link = self::CONNECT_USER)
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->storeResult($this->links[$link]);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param int $link link type
     *
     * @return string|bool type of connection used
     */
    public function getHostInfo($link = self::CONNECT_USER)
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->getHostInfo($this->links[$link]);
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param int $link link type
     *
     * @return int|bool version of the MySQL protocol used
     */
    public function getProtoInfo($link = self::CONNECT_USER)
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->getProtoInfo($this->links[$link]);
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
     * returns last error message or false if no errors occurred
     *
     * @param int $link link type
     *
     * @return string|bool error or false
     */
    public function getError($link = self::CONNECT_USER)
    {
        if (! isset($this->links[$link])) {
            return false;
        }

        return $this->extension->getError($this->links[$link]);
    }

    /**
     * returns the number of rows returned by last query
     *
     * @param object $result result set identifier
     *
     * @return string|int
     */
    public function numRows($result)
    {
        return $this->extension->numRows($result);
    }

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     *
     * @param int $link link type
     *
     * @return int|bool
     */
    public function insertId($link = self::CONNECT_USER)
    {
        // If the primary key is BIGINT we get an incorrect result
        // (sometimes negative, sometimes positive)
        // and in the present function we don't know if the PK is BIGINT
        // so better play safe and use LAST_INSERT_ID()
        //
        // When no controluser is defined, using mysqli_insert_id($link)
        // does not always return the last insert id due to a mixup with
        // the tracking mechanism, but this works:
        return $this->fetchValue('SELECT LAST_INSERT_ID();', 0, 0, $link);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param int  $link           link type
     * @param bool $get_from_cache whether to retrieve from cache
     *
     * @return int|bool
     */
    public function affectedRows(
        $link = self::CONNECT_USER,
        bool $get_from_cache = true
    ) {
        if (! isset($this->links[$link])) {
            return false;
        }

        if ($get_from_cache) {
            return $GLOBALS['cached_affected_rows'];
        }

        return $this->extension->affectedRows($this->links[$link]);
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param object $result result set identifier
     *
     * @return mixed meta info for fields in $result
     */
    public function getFieldsMeta($result)
    {
        $result = $this->extension->getFieldsMeta($result);

        if ($this->getLowerCaseNames() === '2') {
            /**
             * Fixup orgtable for lower_case_table_names = 2
             *
             * In this setup MySQL server reports table name lower case
             * but we still need to operate on original case to properly
             * match existing strings
             */
            foreach ($result as $value) {
                if (strlen($value->orgtable) === 0 ||
                        mb_strtolower($value->orgtable) !== mb_strtolower($value->table)
                ) {
                    continue;
                }

                $value->orgtable = $value->table;
            }
        }

        return $result;
    }

    /**
     * return number of fields in given $result
     *
     * @param object $result result set identifier
     *
     * @return int field count
     */
    public function numFields($result): int
    {
        return $this->extension->numFields($result);
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return int|bool length of field
     */
    public function fieldLen($result, int $i)
    {
        return $this->extension->fieldLen($result, $i);
    }

    /**
     * returns name of $i. field in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, int $i): string
    {
        return $this->extension->fieldName($result, $i);
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i): string
    {
        return $this->extension->fieldFlags($result, $i);
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $str  string to be escaped
     * @param mixed  $link optional database link to use
     *
     * @return string a MySQL escaped string
     */
    public function escapeString(string $str, $link = self::CONNECT_USER)
    {
        if ($this->extension === null || ! isset($this->links[$link])) {
            return $str;
        }

        return $this->extension->escapeString($this->links[$link], $str);
    }

    /**
     * Checks if this database server is running on Amazon RDS.
     */
    public function isAmazonRds(): bool
    {
        if (SessionCache::has('is_amazon_rds')) {
            return SessionCache::get('is_amazon_rds');
        }
        $sql = 'SELECT @@basedir';
        $result = $this->fetchValue($sql);
        $rds = (substr($result, 0, 10) === '/rdsdbbin/');
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
     * @param string $db_name    DB name
     * @param string $table_name Table name
     */
    public function getTable(string $db_name, string $table_name): Table
    {
        return new Table($table_name, $db_name, $this);
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
                . ' WHERE SCHEMA_NAME = \'' . $this->escapeString($db)
                . '\' LIMIT 1';

            return $this->fetchValue($sql);
        }

        $this->selectDb($db);
        $return = $this->fetchValue('SELECT @@collation_database');
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
        return $this->fetchValue('SELECT @@collation_server');
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
     * Load correct database driver
     *
     * @param DbiExtension|null $extension Force the use of an alternative extension
     */
    public static function load(?DbiExtension $extension = null): self
    {
        if ($extension !== null) {
            return new self($extension);
        }

        if (! Util::checkDbExtension('mysqli')) {
            $docLink = sprintf(
                __('See %sour documentation%s for more information.'),
                '[doc@faqmysql]',
                '[/doc]'
            );
            Core::warnMissingExtension(
                'mysqli',
                true,
                $docLink
            );
        }

        return new self(new DbiMysqli());
    }

    /**
     * Prepare an SQL statement for execution.
     *
     * @param string $query The query, as a string.
     * @param int    $link  Link type.
     *
     * @return object|false A statement object or false.
     */
    public function prepare(string $query, $link = self::CONNECT_USER)
    {
        return $this->extension->prepare($this->links[$link], $query);
    }
}
