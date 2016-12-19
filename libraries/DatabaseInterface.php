<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main interface for database interactions
 *
 * @package PhpMyAdmin-DBI
 */
namespace PMA\libraries;

use PMA\libraries\dbi\DBIExtension;
use PMA\libraries\LanguageManager;

require_once './libraries/logging.lib.php';
require_once './libraries/util.lib.php';

/**
 * Main interface for database interactions
 *
 * @package PhpMyAdmin-DBI
 */
class DatabaseInterface
{
    /**
     * Force STORE_RESULT method, ignored by classic MySQL.
     */
    const QUERY_STORE = 1;
    /**
     * Do not read whole query.
     */
    const QUERY_UNBUFFERED = 2;
    /**
     * Get session variable.
     */
    const GETVAR_SESSION = 1;
    /**
     * Get global variable.
     */
    const GETVAR_GLOBAL = 2;

    /**
     * @var DBIExtension
     */
    private $_extension;

    /**
     * @var array Table data cache
     */
    private $_table_cache;

    /**
     * @var null|string lower_case_table_names value cache
     */
    private $_lower_case_table_names = null;

    /**
     * Constructor
     *
     * @param DBIExtension $ext Object to be used for database queries
     */
    public function __construct($ext)
    {
        $this->_extension = $ext;
        $this->_table_cache = array();
    }

    /**
     * Checks whether database extension is loaded
     *
     * @param string $extension mysql extension to check
     *
     * @return bool
     */
    public static function checkDbExtension($extension = 'mysql')
    {
        if (function_exists($extension . '_connect')) {
            return true;
        }
        return false;
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
    public function query($query, $link = null, $options = 0,
        $cache_affected_rows = true
    ) {
        $res = $this->tryQuery($query, $link, $options, $cache_affected_rows)
            or Util::mysqlDie($this->getError($link), $query);
        return $res;
    }

    /**
     * Get a cached value from table cache.
     *
     * @param array $contentPath Array of the name of the target value
     * @param mixed $default     Return value on cache miss
     *
     * @return mixed cached value or default
     */
    public function getCachedTableContent($contentPath, $default = null)
    {
        return \PMA\Util\get($this->_table_cache, $contentPath, $default);
    }

    /**
     * Set an item in table cache using dot notation.
     *
     * @param array $contentPath Array with the target path
     * @param mixed $value       Target value
     *
     * @return void
     */
    public function cacheTableContent($contentPath, $value)
    {
        $loc = &$this->_table_cache;

        if (!isset($contentPath)) {
            $loc = $value;
            return;
        }

        while (count($contentPath) > 1) {
            $key = array_shift($contentPath);

            // If the key doesn't exist at this depth, we will just create an empty
            // array to hold the next value, allowing us to create the arrays to hold
            // final values at the correct depth. Then we'll keep digging into the
            // array.
            if (!isset($loc[$key]) || !is_array($loc[$key])) {
                $loc[$key] = array();
            }
            $loc = &$loc[$key];
        }

        $loc[array_shift($contentPath)] = $value;
    }

    /**
     * Clear the table cache.
     *
     * @return void
     */
    public function clearTableCache()
    {
        $this->_table_cache = array();
    }

    /**
     * Caches table data so Table does not require to issue
     * SHOW TABLE STATUS again
     *
     * @param array  $tables information for tables of some databases
     * @param string $table  table name
     *
     * @return void
     */
    private function _cacheTableData($tables, $table)
    {
        // Note: I don't see why we would need array_merge_recursive() here,
        // as it creates double entries for the same table (for example a double
        // entry for Comment when changing the storage engine in Operations)
        // Note 2: Instead of array_merge(), simply use the + operator because
        //  array_merge() renumbers numeric keys starting with 0, therefore
        //  we would lose a db name that consists only of numbers

        foreach ($tables as $one_database => $its_tables) {
            if (isset($this->_table_cache[$one_database])) {
                // the + operator does not do the intended effect
                // when the cache for one table already exists
                if ($table
                    && isset($this->_table_cache[$one_database][$table])
                ) {
                    unset($this->_table_cache[$one_database][$table]);
                }
                $this->_table_cache[$one_database]
                    = $this->_table_cache[$one_database] + $tables[$one_database];
            } else {
                $this->_table_cache[$one_database] = $tables[$one_database];
            }
        }
    }

    /**
     * Stores query data into session data for debugging purposes
     *
     * @param string         $query  Query text
     * @param object         $link   database link
     * @param object|boolean $result Query result
     * @param integer        $time   Time to execute query
     *
     * @return void
     */
    private function _dbgQuery($query, $link, $result, $time)
    {
        $dbgInfo = array();
        $error_message = $this->getError($link);
        if ($result == false && is_string($error_message)) {
            $dbgInfo['error']
                = '<span style="color:red">'
                . htmlspecialchars($error_message) . '</span>';
        }
        $dbgInfo['query'] = htmlspecialchars($query);
        $dbgInfo['time'] = $time;
        // Get and slightly format backtrace, this is used
        // in the javascript console.
        // Strip call to _dbgQuery
        $dbgInfo['trace'] = Error::processBacktrace(
            array_slice(debug_backtrace(), 1)
        );
        $dbgInfo['hash'] = md5($query);

        $_SESSION['debug']['queries'][] = $dbgInfo;
    }

    /**
     * runs a query and returns the result
     *
     * @param string  $query               query to run
     * @param object  $link                mysql link resource
     * @param integer $options             query options
     * @param bool    $cache_affected_rows whether to cache affected row
     *
     * @return mixed
     */
    public function tryQuery($query, $link = null, $options = 0,
        $cache_affected_rows = true
    ) {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }

        if ($GLOBALS['cfg']['DBG']['sql']) {
            $time = microtime(true);
        }

        $result = $this->_extension->realQuery($query, $link, $options);

        if ($cache_affected_rows) {
            $GLOBALS['cached_affected_rows'] = $this->affectedRows($link, false);
        }

        if ($GLOBALS['cfg']['DBG']['sql']) {
            $time = microtime(true) - $time;
            $this->_dbgQuery($query, $link, $result, $time);
        }

        if ((!empty($result)) && (Tracker::isActive())) {
            Tracker::handleQuery($query);
        }

        return $result;
    }

    /**
     * Run multi query statement and return results
     *
     * @param string $multi_query multi query statement to execute
     * @param mysqli $link        mysqli object
     *
     * @return mysqli_result collection | boolean(false)
     */
    public function tryMultiQuery($multi_query = '', $link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }

        return $this->_extension->realMultiQuery($link, $multi_query);
    }

    /**
     * returns array with table names for given db
     *
     * @param string $database name of database
     * @param mixed  $link     mysql link resource|object
     *
     * @return array   tables names
     */
    public function getTables($database, $link = null)
    {
        return $this->fetchResult(
            'SHOW TABLES FROM ' . Util::backquote($database) . ';',
            null,
            0,
            $link,
            self::QUERY_STORE
        );
    }

    /**
     * returns a segment of the SQL WHERE clause regarding table name and type
     *
     * @param string  $table        table
     * @param boolean $tbl_is_group $table is a table group
     * @param string  $table_type   whether table or view
     *
     * @return string a segment of the WHERE clause
     */
    private function _getTableCondition($table, $tbl_is_group, $table_type)
    {
        // get table information from information_schema
        if ($table) {
            if (true === $tbl_is_group) {
                $sql_where_table = 'AND t.`TABLE_NAME` LIKE \''
                    . Util::escapeMysqlWildcards(
                        $GLOBALS['dbi']->escapeString($table)
                    )
                    . '%\'';
            } else {
                $sql_where_table = 'AND t.`TABLE_NAME` '
                    . Util::getCollateForIS() . ' = \''
                    . $GLOBALS['dbi']->escapeString($table) . '\'';
            }
        } else {
            $sql_where_table = '';
        }

        if ($table_type) {
            if ($table_type == 'view') {
                $sql_where_table .= " AND t.`TABLE_TYPE` != 'BASE TABLE'";
            } else if ($table_type == 'table') {
                $sql_where_table .= " AND t.`TABLE_TYPE` = 'BASE TABLE'";
            }
        }
        return $sql_where_table;
    }

    /**
     * returns the beginning of the SQL statement to fetch the list of tables
     *
     * @param string[] $this_databases  databases to list
     * @param string   $sql_where_table additional condition
     *
     * @return string the SQL statement
     */
    private function _getSqlForTablesFull($this_databases, $sql_where_table)
    {
        $sql = '
            SELECT *,
                `TABLE_SCHEMA`       AS `Db`,
                `TABLE_NAME`         AS `Name`,
                `TABLE_TYPE`         AS `TABLE_TYPE`,
                `ENGINE`             AS `Engine`,
                `ENGINE`             AS `Type`,
                `VERSION`            AS `Version`,
                `ROW_FORMAT`         AS `Row_format`,
                `TABLE_ROWS`         AS `Rows`,
                `AVG_ROW_LENGTH`     AS `Avg_row_length`,
                `DATA_LENGTH`        AS `Data_length`,
                `MAX_DATA_LENGTH`    AS `Max_data_length`,
                `INDEX_LENGTH`       AS `Index_length`,
                `DATA_FREE`          AS `Data_free`,
                `AUTO_INCREMENT`     AS `Auto_increment`,
                `CREATE_TIME`        AS `Create_time`,
                `UPDATE_TIME`        AS `Update_time`,
                `CHECK_TIME`         AS `Check_time`,
                `TABLE_COLLATION`    AS `Collation`,
                `CHECKSUM`           AS `Checksum`,
                `CREATE_OPTIONS`     AS `Create_options`,
                `TABLE_COMMENT`      AS `Comment`
            FROM `information_schema`.`TABLES` t
            WHERE `TABLE_SCHEMA` ' . Util::getCollateForIS() . '
                IN (\'' . implode("', '", $this_databases) . '\')
                ' . $sql_where_table;

        return $sql;
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
     * $GLOBALS['dbi']->getTablesFull('my_database');
     * $GLOBALS['dbi']->getTablesFull('my_database', 'my_table'));
     * $GLOBALS['dbi']->getTablesFull('my_database', 'my_tables_', true));
     * </code>
     *
     * @param string          $database     database
     * @param string          $table        table name
     * @param boolean         $tbl_is_group $table is a table group
     * @param mixed           $link         mysql link
     * @param integer         $limit_offset zero-based offset for the count
     * @param boolean|integer $limit_count  number of tables to return
     * @param string          $sort_by      table attribute to sort by
     * @param string          $sort_order   direction to sort (ASC or DESC)
     * @param string          $table_type   whether table or view
     *
     * @todo    move into Table
     *
     * @return array           list of tables in given db(s)
     */
    public function getTablesFull($database, $table = '',
        $tbl_is_group = false,  $link = null, $limit_offset = 0,
        $limit_count = false, $sort_by = 'Name', $sort_order = 'ASC',
        $table_type = null
    ) {
        if (true === $limit_count) {
            $limit_count = $GLOBALS['cfg']['MaxTableList'];
        }
        // prepare and check parameters
        if (! is_array($database)) {
            $databases = array($database);
        } else {
            $databases = $database;
        }

        $tables = array();

        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $sql_where_table = $this->_getTableCondition(
                $table, $tbl_is_group, $table_type
            );

            // for PMA bc:
            // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
            //
            // on non-Windows servers,
            // added BINARY in the WHERE clause to force a case sensitive
            // comparison (if we are looking for the db Aa we don't want
            // to find the db aa)
            $this_databases = array_map(
                array($this, 'escapeString'),
                $databases
            );

            $sql = $this->_getSqlForTablesFull($this_databases, $sql_where_table);

            // Sort the tables
            $sql .= " ORDER BY $sort_by $sort_order";

            if ($limit_count) {
                $sql .= ' LIMIT ' . $limit_count . ' OFFSET ' . $limit_offset;
            }

            $tables = $this->fetchResult(
                $sql, array('TABLE_SCHEMA', 'TABLE_NAME'), null, $link
            );

            if ($sort_by == 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                // here, the array's first key is by schema name
                foreach ($tables as $one_database_name => $one_database_tables) {
                    uksort($one_database_tables, 'strnatcasecmp');

                    if ($sort_order == 'DESC') {
                        $one_database_tables = array_reverse($one_database_tables);
                    }
                    $tables[$one_database_name] = $one_database_tables;
                }
            } elseif ($sort_by == 'Data_length') {
                // Size = Data_length + Index_length
                foreach ($tables as $one_database_name => $one_database_tables) {
                    uasort(
                        $one_database_tables,
                        function ($a, $b) {
                            $aLength = $a['Data_length'] + $a['Index_length'];
                            $bLength = $b['Data_length'] + $b['Index_length'];
                            return ($aLength == $bLength)
                                ? 0
                                : ($aLength < $bLength) ? -1 : 1;
                        }
                    );

                    if ($sort_order == 'DESC') {
                        $one_database_tables = array_reverse($one_database_tables);
                    }
                    $tables[$one_database_name] = $one_database_tables;
                }
            }
        } // end (get information from table schema)

        // If permissions are wrong on even one database directory,
        // information_schema does not return any table info for any database
        // this is why we fall back to SHOW TABLE STATUS even for MySQL >= 50002
        if (empty($tables)) {
            foreach ($databases as $each_database) {
                if ($table || (true === $tbl_is_group) || ! empty($table_type)) {
                    $sql = 'SHOW TABLE STATUS FROM '
                        . Util::backquote($each_database)
                        . ' WHERE';
                    $needAnd = false;
                    if ($table || (true === $tbl_is_group)) {
                        $sql .= " `Name` LIKE '"
                            . Util::escapeMysqlWildcards(
                                $this->escapeString($table, $link)
                            )
                            . "%'";
                        $needAnd = true;
                    }
                    if (! empty($table_type)) {
                        if ($needAnd) {
                            $sql .= " AND";
                        }
                        if ($table_type == 'view') {
                            $sql .= " `Comment` = 'VIEW'";
                        } else if ($table_type == 'table') {
                            $sql .= " `Comment` != 'VIEW'";
                        }
                    }
                } else {
                    $sql = 'SHOW TABLE STATUS FROM '
                        . Util::backquote($each_database);
                }

                $useStatusCache = false;

                if (extension_loaded('apc')
                    && isset($GLOBALS['cfg']['Server']['StatusCacheDatabases'])
                    && ! empty($GLOBALS['cfg']['Server']['StatusCacheLifetime'])
                ) {
                    $statusCacheDatabases
                        = (array) $GLOBALS['cfg']['Server']['StatusCacheDatabases'];
                    if (in_array($each_database, $statusCacheDatabases)) {
                        $useStatusCache = true;
                    }
                }

                $each_tables = null;

                if ($useStatusCache) {
                    $cacheKey = 'phpMyAdmin_tableStatus_'
                        . sha1($GLOBALS['cfg']['Server']['host'] . '_' . $sql);

                    $each_tables = apc_fetch($cacheKey);
                }

                if (! $each_tables) {
                    $each_tables = $this->fetchResult($sql, 'Name', null, $link);
                }

                if ($useStatusCache) {
                    apc_store(
                        $cacheKey, $each_tables,
                        $GLOBALS['cfg']['Server']['StatusCacheLifetime']
                    );
                }

                // Sort naturally if the config allows it and we're sorting
                // the Name column.
                if ($sort_by == 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
                    uksort($each_tables, 'strnatcasecmp');

                    if ($sort_order == 'DESC') {
                        $each_tables = array_reverse($each_tables);
                    }
                } else {
                    // Prepare to sort by creating array of the selected sort
                    // value to pass to array_multisort

                    // Size = Data_length + Index_length
                    if ($sort_by == 'Data_length') {
                        foreach ($each_tables as $table_name => $table_data) {
                            ${$sort_by}[$table_name] = strtolower(
                                $table_data['Data_length']
                                + $table_data['Index_length']
                            );
                        }
                    } else {
                        foreach ($each_tables as $table_name => $table_data) {
                            ${$sort_by}[$table_name]
                                = strtolower($table_data[$sort_by]);
                        }
                    }

                    if (! empty($$sort_by)) {
                        if ($sort_order == 'DESC') {
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
                        $each_tables, $limit_offset, $limit_count
                    );
                }

                foreach ($each_tables as $table_name => $each_table) {
                    if (! isset($each_tables[$table_name]['Type'])
                        && isset($each_tables[$table_name]['Engine'])
                    ) {
                        // pma BC, same parts of PMA still uses 'Type'
                        $each_tables[$table_name]['Type']
                            =& $each_tables[$table_name]['Engine'];
                    } elseif (! isset($each_tables[$table_name]['Engine'])
                        && isset($each_tables[$table_name]['Type'])
                    ) {
                        // old MySQL reports Type, newer MySQL reports Engine
                        $each_tables[$table_name]['Engine']
                            =& $each_tables[$table_name]['Type'];
                    }

                    // MySQL forward compatibility
                    // so pma could use this array as if every server
                    // is of version >5.0
                    // todo : remove and check usage in the rest of the code,
                    // MySQL 5.0 is required by current PMA version
                    $each_tables[$table_name]['TABLE_SCHEMA']
                        = $each_database;
                    $each_tables[$table_name]['TABLE_NAME']
                        =& $each_tables[$table_name]['Name'];
                    $each_tables[$table_name]['ENGINE']
                        =& $each_tables[$table_name]['Engine'];
                    $each_tables[$table_name]['VERSION']
                        =& $each_tables[$table_name]['Version'];
                    $each_tables[$table_name]['ROW_FORMAT']
                        =& $each_tables[$table_name]['Row_format'];
                    $each_tables[$table_name]['TABLE_ROWS']
                        =& $each_tables[$table_name]['Rows'];
                    $each_tables[$table_name]['AVG_ROW_LENGTH']
                        =& $each_tables[$table_name]['Avg_row_length'];
                    $each_tables[$table_name]['DATA_LENGTH']
                        =& $each_tables[$table_name]['Data_length'];
                    $each_tables[$table_name]['MAX_DATA_LENGTH']
                        =& $each_tables[$table_name]['Max_data_length'];
                    $each_tables[$table_name]['INDEX_LENGTH']
                        =& $each_tables[$table_name]['Index_length'];
                    $each_tables[$table_name]['DATA_FREE']
                        =& $each_tables[$table_name]['Data_free'];
                    $each_tables[$table_name]['AUTO_INCREMENT']
                        =& $each_tables[$table_name]['Auto_increment'];
                    $each_tables[$table_name]['CREATE_TIME']
                        =& $each_tables[$table_name]['Create_time'];
                    $each_tables[$table_name]['UPDATE_TIME']
                        =& $each_tables[$table_name]['Update_time'];
                    $each_tables[$table_name]['CHECK_TIME']
                        =& $each_tables[$table_name]['Check_time'];
                    $each_tables[$table_name]['TABLE_COLLATION']
                        =& $each_tables[$table_name]['Collation'];
                    $each_tables[$table_name]['CHECKSUM']
                        =& $each_tables[$table_name]['Checksum'];
                    $each_tables[$table_name]['CREATE_OPTIONS']
                        =& $each_tables[$table_name]['Create_options'];
                    $each_tables[$table_name]['TABLE_COMMENT']
                        =& $each_tables[$table_name]['Comment'];

                    if (strtoupper($each_tables[$table_name]['Comment']) === 'VIEW'
                        && $each_tables[$table_name]['Engine'] == null
                    ) {
                        $each_tables[$table_name]['TABLE_TYPE'] = 'VIEW';
                    } elseif ($each_database == 'information_schema') {
                        $each_tables[$table_name]['TABLE_TYPE'] = 'SYSTEM VIEW';
                    } else {
                        /**
                         * @todo difference between 'TEMPORARY' and 'BASE TABLE'
                         * but how to detect?
                         */
                        $each_tables[$table_name]['TABLE_TYPE'] = 'BASE TABLE';
                    }
                }

                $tables[$each_database] = $each_tables;
            }
        }

        // cache table data
        // so Table does not require to issue SHOW TABLE STATUS again
        $this->_cacheTableData($tables, $table);

        if (is_array($database)) {
            return $tables;
        }

        if (isset($tables[$database])) {
            return $tables[$database];
        }

        if (isset($tables[mb_strtolower($database)])) {
            // on windows with lower_case_table_names = 1
            // MySQL returns
            // with SHOW DATABASES or information_schema.SCHEMATA: `Test`
            // but information_schema.TABLES gives `test`
            // bug #2036
            // https://sourceforge.net/p/phpmyadmin/bugs/2036/
            return $tables[mb_strtolower($database)];
        }

        return $tables;
    }

    /**
     * Copies the table properties to the set of property names used by PMA.
     *
     * @param array  $tables   array of table properties
     * @param string $database database name
     *
     * @return array array with added properties
     */
    public function copyTableProperties($tables, $database)
    {
        foreach ($tables as $table_name => $each_table) {
            if (! isset($tables[$table_name]['Type'])
                && isset($tables[$table_name]['Engine'])
            ) {
                // pma BC, same parts of PMA still uses 'Type'
                $tables[$table_name]['Type']
                    =& $tables[$table_name]['Engine'];
            } elseif (! isset($tables[$table_name]['Engine'])
                && isset($tables[$table_name]['Type'])
            ) {
                // old MySQL reports Type, newer MySQL reports Engine
                $tables[$table_name]['Engine']
                    =& $tables[$table_name]['Type'];
            }

            // MySQL forward compatibility
            // so pma could use this array as if every server
            // is of version >5.0
            // todo : remove and check usage in the rest of the code,
            // MySQL 5.0 is required by current PMA version
            $tables[$table_name]['TABLE_SCHEMA']
                = $database;
            $tables[$table_name]['TABLE_NAME']
                =& $tables[$table_name]['Name'];
            $tables[$table_name]['ENGINE']
                =& $tables[$table_name]['Engine'];
            $tables[$table_name]['VERSION']
                =& $tables[$table_name]['Version'];
            $tables[$table_name]['ROW_FORMAT']
                =& $tables[$table_name]['Row_format'];
            $tables[$table_name]['TABLE_ROWS']
                =& $tables[$table_name]['Rows'];
            $tables[$table_name]['AVG_ROW_LENGTH']
                =& $tables[$table_name]['Avg_row_length'];
            $tables[$table_name]['DATA_LENGTH']
                =& $tables[$table_name]['Data_length'];
            $tables[$table_name]['MAX_DATA_LENGTH']
                =& $tables[$table_name]['Max_data_length'];
            $tables[$table_name]['INDEX_LENGTH']
                =& $tables[$table_name]['Index_length'];
            $tables[$table_name]['DATA_FREE']
                =& $tables[$table_name]['Data_free'];
            $tables[$table_name]['AUTO_INCREMENT']
                =& $tables[$table_name]['Auto_increment'];
            $tables[$table_name]['CREATE_TIME']
                =& $tables[$table_name]['Create_time'];
            $tables[$table_name]['UPDATE_TIME']
                =& $tables[$table_name]['Update_time'];
            $tables[$table_name]['CHECK_TIME']
                =& $tables[$table_name]['Check_time'];
            $tables[$table_name]['TABLE_COLLATION']
                =& $tables[$table_name]['Collation'];
            $tables[$table_name]['CHECKSUM']
                =& $tables[$table_name]['Checksum'];
            $tables[$table_name]['CREATE_OPTIONS']
                =& $tables[$table_name]['Create_options'];
            $tables[$table_name]['TABLE_COMMENT']
                =& $tables[$table_name]['Comment'];

            $commentUpper = mb_strtoupper(
                $tables[$table_name]['Comment']
            );
            if ($commentUpper === 'VIEW'
                && $tables[$table_name]['Engine'] == null
            ) {
                $tables[$table_name]['TABLE_TYPE'] = 'VIEW';
            } else {
                /**
                 * @todo difference between 'TEMPORARY' and 'BASE TABLE'
                 * but how to detect?
                 */
                $tables[$table_name]['TABLE_TYPE'] = 'BASE TABLE';
            }
        }
        return $tables;
    }

    /**
     * Get VIEWs in a particular database
     *
     * @param string $db Database name to look in
     *
     * @return array $views Set of VIEWs inside the database
     */
    public function getVirtualTables($db)
    {

        $tables_full = $this->getTablesFull($db);
        $views = array();

        foreach ($tables_full as $table=>$tmp) {

            $_table = $this->getTable($db, $table);
            if ($_table->isView()) {
                $views[] = $table;
            }

        }

        return $views;

    }


    /**
     * returns array with databases containing extended infos about them
     *
     * @param string   $database     database
     * @param boolean  $force_stats  retrieve stats also for MySQL < 5
     * @param object   $link         mysql link
     * @param string   $sort_by      column to order by
     * @param string   $sort_order   ASC or DESC
     * @param integer  $limit_offset starting offset for LIMIT
     * @param bool|int $limit_count  row count for LIMIT or true
     *                               for $GLOBALS['cfg']['MaxDbList']
     *
     * @todo    move into ListDatabase?
     *
     * @return array $databases
     */
    public function getDatabasesFull($database = null, $force_stats = false,
        $link = null, $sort_by = 'SCHEMA_NAME', $sort_order = 'ASC',
        $limit_offset = 0, $limit_count = false
    ) {
        $sort_order = strtoupper($sort_order);

        if (true === $limit_count) {
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
            if (! empty($database)) {
                $sql_where_schema = 'WHERE `SCHEMA_NAME` LIKE \''
                    . $this->escapeString($database, $link) . '\'';
            } else {
                $sql_where_schema = '';
            }

            $sql  = 'SELECT *,
                    CAST(BIN_NAME AS CHAR CHARACTER SET utf8) AS SCHEMA_NAME
                FROM (';
            $sql .= 'SELECT
                BINARY s.SCHEMA_NAME AS BIN_NAME,
                s.DEFAULT_COLLATION_NAME';
            if ($force_stats) {
                $sql .= ',
                    COUNT(t.TABLE_SCHEMA)  AS SCHEMA_TABLES,
                    SUM(t.TABLE_ROWS)      AS SCHEMA_TABLE_ROWS,
                    SUM(t.DATA_LENGTH)     AS SCHEMA_DATA_LENGTH,
                    SUM(t.MAX_DATA_LENGTH) AS SCHEMA_MAX_DATA_LENGTH,
                    SUM(t.INDEX_LENGTH)    AS SCHEMA_INDEX_LENGTH,
                    SUM(t.DATA_LENGTH + t.INDEX_LENGTH)
                                           AS SCHEMA_LENGTH,
                    SUM(IF(t.ENGINE <> \'InnoDB\', t.DATA_FREE, 0))
                                           AS SCHEMA_DATA_FREE';
            }
            $sql .= '
                   FROM `information_schema`.SCHEMATA s';
            if ($force_stats) {
                $sql .= '
                    LEFT JOIN `information_schema`.TABLES t
                        ON BINARY t.TABLE_SCHEMA = BINARY s.SCHEMA_NAME';
            }
            $sql .= $sql_where_schema . '
                    GROUP BY BINARY s.SCHEMA_NAME, s.DEFAULT_COLLATION_NAME
                    ORDER BY ';
            if ($sort_by == 'SCHEMA_NAME'
                || $sort_by == 'DEFAULT_COLLATION_NAME'
            ) {
                $sql .= 'BINARY ';
            }
            $sql .= Util::backquote($sort_by)
                . ' ' . $sort_order
                . $limit;
            $sql .= ') a';

            $databases = $this->fetchResult($sql, 'SCHEMA_NAME', null, $link);

            $mysql_error = $this->getError($link);
            if (! count($databases) && $GLOBALS['errno']) {
                Util::mysqlDie($mysql_error, $sql);
            }

            // display only databases also in official database list
            // f.e. to apply hide_db and only_db
            $drops = array_diff(
                array_keys($databases), (array) $GLOBALS['dblist']->databases
            );
            foreach ($drops as $drop) {
                unset($databases[$drop]);
            }
        } else {
            $databases = array();
            foreach ($GLOBALS['dblist']->databases as $database_name) {
                // MySQL forward compatibility
                // so pma could use this array as if every server is of version >5.0
                // todo : remove and check the rest of the code for usage,
                // MySQL 5.0 or higher is required for current PMA version
                $databases[$database_name]['SCHEMA_NAME']      = $database_name;

                include_once './libraries/mysql_charsets.inc.php';
                $databases[$database_name]['DEFAULT_COLLATION_NAME']
                    = PMA_getDbCollation($database_name);

                if (!$force_stats) {
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
                    if ('InnoDB' != $row['Engine']) {
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
            $GLOBALS['callback_sort_order'] = $sort_order;
            $GLOBALS['callback_sort_by'] = $sort_by;
            usort(
                $databases,
                array('PMA\libraries\DatabaseInterface', '_usortComparisonCallback')
            );
            unset($GLOBALS['callback_sort_order'], $GLOBALS['callback_sort_by']);

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
     * usort comparison callback
     *
     * @param string $a first argument to sort
     * @param string $b second argument to sort
     *
     * @return integer  a value representing whether $a should be before $b in the
     *                   sorted array or not
     *
     * @access  private
     */
    private static function _usortComparisonCallback($a, $b)
    {
        if ($GLOBALS['cfg']['NaturalOrder']) {
            $sorter = 'strnatcasecmp';
        } else {
            $sorter = 'strcasecmp';
        }
        /* No sorting when key is not present */
        if (! isset($a[$GLOBALS['callback_sort_by']])
            || ! isset($b[$GLOBALS['callback_sort_by']])
        ) {
            return 0;
        }
        // produces f.e.:
        // return -1 * strnatcasecmp($a["SCHEMA_TABLES"], $b["SCHEMA_TABLES"])
        return ($GLOBALS['callback_sort_order'] == 'ASC' ? 1 : -1) * $sorter(
            $a[$GLOBALS['callback_sort_by']], $b[$GLOBALS['callback_sort_by']]
        );
    } // end of the '_usortComparisonCallback()' method

    /**
     * returns detailed array with all columns for sql
     *
     * @param string $sql_query    target SQL query to get columns
     * @param array  $view_columns alias for columns
     *
     * @return array
     */
    public function getColumnMapFromSql($sql_query, $view_columns = array())
    {
        $result = $this->tryQuery($sql_query);

        if ($result === false) {
            return array();
        }

        $meta = $this->getFieldsMeta(
            $result
        );

        $nbFields = count($meta);
        if ($nbFields <= 0) {
            return array();
        }

        $column_map = array();
        $nbColumns = count($view_columns);

        for ($i=0; $i < $nbFields; $i++) {

            $map = array();
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
    public function getColumnsFull($database = null, $table = null,
        $column = null, $link = null
    ) {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $sql_wheres = array();
            $array_keys = array();

            // get columns information from information_schema
            if (null !== $database) {
                $sql_wheres[] = '`TABLE_SCHEMA` = \''
                    . $this->escapeString($database, $link) . '\' ';
            } else {
                $array_keys[] = 'TABLE_SCHEMA';
            }
            if (null !== $table) {
                $sql_wheres[] = '`TABLE_NAME` = \''
                    . $this->escapeString($table, $link) . '\' ';
            } else {
                $array_keys[] = 'TABLE_NAME';
            }
            if (null !== $column) {
                $sql_wheres[] = '`COLUMN_NAME` = \''
                    . $this->escapeString($column, $link) . '\' ';
            } else {
                $array_keys[] = 'COLUMN_NAME';
            }

            // for PMA bc:
            // `[SCHEMA_FIELD_NAME]` AS `[SHOW_FULL_COLUMNS_FIELD_NAME]`
            $sql = '
                 SELECT *,
                        `COLUMN_NAME`       AS `Field`,
                        `COLUMN_TYPE`       AS `Type`,
                        `COLLATION_NAME`    AS `Collation`,
                        `IS_NULLABLE`       AS `Null`,
                        `COLUMN_KEY`        AS `Key`,
                        `COLUMN_DEFAULT`    AS `Default`,
                        `EXTRA`             AS `Extra`,
                        `PRIVILEGES`        AS `Privileges`,
                        `COLUMN_COMMENT`    AS `Comment`
                   FROM `information_schema`.`COLUMNS`';

            if (count($sql_wheres)) {
                $sql .= "\n" . ' WHERE ' . implode(' AND ', $sql_wheres);
            }
            return $this->fetchResult($sql, $array_keys, null, $link);
        } else {
            $columns = array();
            if (null === $database) {
                foreach ($GLOBALS['dblist']->databases as $database) {
                    $columns[$database] = $this->getColumnsFull(
                        $database, null, null, $link
                    );
                }
                return $columns;
            } elseif (null === $table) {
                $tables = $this->getTables($database);
                foreach ($tables as $table) {
                    $columns[$table] = $this->getColumnsFull(
                        $database, $table, null, $link
                    );
                }
                return $columns;
            }
            $sql = 'SHOW FULL COLUMNS FROM '
                . Util::backquote($database) . '.' . Util::backquote($table);
            if (null !== $column) {
                $sql .= " LIKE '" . $this->escapeString($column, $link) . "'";
            }

            $columns = $this->fetchResult($sql, 'Field', null, $link);
            $ordinal_position = 1;
            foreach ($columns as $column_name => $each_column) {

                // MySQL forward compatibility
                // so pma could use this array as if every server is of version >5.0
                // todo : remove and check the rest of the code for usage,
                // MySQL 5.0 or higher is required for current PMA version
                $columns[$column_name]['COLUMN_NAME']
                    =& $columns[$column_name]['Field'];
                $columns[$column_name]['COLUMN_TYPE']
                    =& $columns[$column_name]['Type'];
                $columns[$column_name]['COLLATION_NAME']
                    =& $columns[$column_name]['Collation'];
                $columns[$column_name]['IS_NULLABLE']
                    =& $columns[$column_name]['Null'];
                $columns[$column_name]['COLUMN_KEY']
                    =& $columns[$column_name]['Key'];
                $columns[$column_name]['COLUMN_DEFAULT']
                    =& $columns[$column_name]['Default'];
                $columns[$column_name]['EXTRA']
                    =& $columns[$column_name]['Extra'];
                $columns[$column_name]['PRIVILEGES']
                    =& $columns[$column_name]['Privileges'];
                $columns[$column_name]['COLUMN_COMMENT']
                    =& $columns[$column_name]['Comment'];

                $columns[$column_name]['TABLE_CATALOG'] = null;
                $columns[$column_name]['TABLE_SCHEMA'] = $database;
                $columns[$column_name]['TABLE_NAME'] = $table;
                $columns[$column_name]['ORDINAL_POSITION'] = $ordinal_position;
                $columns[$column_name]['DATA_TYPE']
                    = substr(
                        $columns[$column_name]['COLUMN_TYPE'],
                        0,
                        strpos($columns[$column_name]['COLUMN_TYPE'], '(')
                    );
                /**
                 * @todo guess CHARACTER_MAXIMUM_LENGTH from COLUMN_TYPE
                */
                $columns[$column_name]['CHARACTER_MAXIMUM_LENGTH'] = null;
                /**
                 * @todo guess CHARACTER_OCTET_LENGTH from CHARACTER_MAXIMUM_LENGTH
                 */
                $columns[$column_name]['CHARACTER_OCTET_LENGTH'] = null;
                $columns[$column_name]['NUMERIC_PRECISION'] = null;
                $columns[$column_name]['NUMERIC_SCALE'] = null;
                $columns[$column_name]['CHARACTER_SET_NAME']
                    = substr(
                        $columns[$column_name]['COLLATION_NAME'],
                        0,
                        strpos($columns[$column_name]['COLLATION_NAME'], '_')
                    );

                $ordinal_position++;
            }

            if (null !== $column) {
                return reset($columns);
            }

            return $columns;
        }
    }

    /**
     * Returns SQL query for fetching columns for a table
     *
     * The 'Key' column is not calculated properly, use $GLOBALS['dbi']->getColumns()
     * to get correct values.
     *
     * @param string  $database name of database
     * @param string  $table    name of table to retrieve columns from
     * @param string  $column   name of column, null to show all columns
     * @param boolean $full     whether to return full info or only column names
     *
     * @see getColumns()
     *
     * @return string
     */
    public function getColumnsSql($database, $table, $column = null, $full = false)
    {
        $sql = 'SHOW ' . ($full ? 'FULL' : '') . ' COLUMNS FROM '
            . Util::backquote($database) . '.' . Util::backquote($table)
            . (($column !== null) ? "LIKE '"
            . $GLOBALS['dbi']->escapeString($column) . "'" : '');

        return $sql;
    }

    /**
     * Returns descriptions of columns in given table (all or given by $column)
     *
     * @param string  $database name of database
     * @param string  $table    name of table to retrieve columns from
     * @param string  $column   name of column, null to show all columns
     * @param boolean $full     whether to return full info or only column names
     * @param mixed   $link     mysql link resource
     *
     * @return array array indexed by column names or,
     *               if $column is given, flat array description
     */
    public function getColumns($database, $table, $column = null, $full = false,
        $link = null
    ) {
        $sql = $this->getColumnsSql($database, $table, $column, $full);
        $fields = $this->fetchResult($sql, 'Field', null, $link);
        if (! is_array($fields) || count($fields) == 0) {
            return array();
        }
        // Check if column is a part of multiple-column index and set its 'Key'.
        $indexes = Index::getFromTable($table, $database);
        foreach ($fields as $field => $field_data) {
            if (!empty($field_data['Key'])) {
                continue;
            }

            foreach ($indexes as $index) {
                /** @var Index $index */
                if (!$index->hasColumn($field)) {
                    continue;
                }

                $index_columns = $index->getColumns();
                if ($index_columns[$field]->getSeqInIndex() > 1) {
                    if ($index->isUnique()) {
                        $fields[$field]['Key'] = 'UNI';
                    } else {
                        $fields[$field]['Key'] = 'MUL';
                    }
                }
            }
        }

        return ($column != null) ? array_shift($fields) : $fields;
    }

    /**
     * Returns all column names in given table
     *
     * @param string $database name of database
     * @param string $table    name of table to retrieve columns from
     * @param mixed  $link     mysql link resource
     *
     * @return null|array
     */
    public function getColumnNames($database, $table, $link = null)
    {
        $sql = $this->getColumnsSql($database, $table);
        // We only need the 'Field' column which contains the table's column names
        $fields = array_keys($this->fetchResult($sql, 'Field', null, $link));

        if (! is_array($fields) || count($fields) == 0) {
            return null;
        }
        return $fields;
    }

    /**
    * Returns SQL for fetching information on table indexes (SHOW INDEXES)
    *
    * @param string $database name of database
    * @param string $table    name of the table whose indexes are to be retrieved
    * @param string $where    additional conditions for WHERE
    *
    * @return string SQL for getting indexes
    */
    public function getTableIndexesSql($database, $table, $where = null)
    {
        $sql = 'SHOW INDEXES FROM ' . Util::backquote($database) . '.'
            . Util::backquote($table);
        if ($where) {
            $sql .= ' WHERE (' . $where . ')';
        }
        return $sql;
    }

    /**
     * Returns indexes of a table
     *
     * @param string $database name of database
     * @param string $table    name of the table whose indexes are to be retrieved
     * @param mixed  $link     mysql link resource
     *
     * @return array   $indexes
     */
    public function getTableIndexes($database, $table, $link = null)
    {
        $sql = $this->getTableIndexesSql($database, $table);
        $indexes = $this->fetchResult($sql, null, null, $link);

        if (! is_array($indexes) || count($indexes) < 1) {
            return array();
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
        $var, $type = self::GETVAR_SESSION, $link = null
    ) {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }

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
            'SHOW' . $modifier . ' VARIABLES LIKE \'' . $var . '\';', 0, 1, $link
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
    public function setVariable($var, $value, $link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        $current_value = $GLOBALS['dbi']->getVariable(
            $var, self::GETVAR_SESSION, $link
        );
        if ($current_value == $value) {
            return true;
        }

        return $this->query("SET " . $var . " = " . $value . ';', $link);
    }

    /**
     * Function called just after a connection to the MySQL database server has
     * been established. It sets the connection collation, and determines the
     * version of MySQL which is running.
     *
     * @param mixed $link mysql link resource|object
     *
     * @return void
     */
    public function postConnect($link)
    {
        if (! defined('PMA_MYSQL_INT_VERSION')) {
            $version = $this->fetchSingleRow(
                'SELECT @@version, @@version_comment',
                'ASSOC',
                $link
            );

            if ($version) {
                $match = explode('.', $version['@@version']);
                define('PMA_MYSQL_MAJOR_VERSION', (int)$match[0]);
                define(
                    'PMA_MYSQL_INT_VERSION',
                    (int) sprintf(
                        '%d%02d%02d', $match[0], $match[1], intval($match[2])
                    )
                );
                define('PMA_MYSQL_STR_VERSION', $version['@@version']);
                define(
                    'PMA_MYSQL_VERSION_COMMENT',
                    $version['@@version_comment']
                );
            } else {
                define('PMA_MYSQL_INT_VERSION', 50501);
                define('PMA_MYSQL_MAJOR_VERSION', 5);
                define('PMA_MYSQL_STR_VERSION', '5.05.01');
                define('PMA_MYSQL_VERSION_COMMENT', '');
            }
            /* Detect MariaDB */
            if (mb_strpos(PMA_MYSQL_STR_VERSION, 'MariaDB') !== false) {
                define('PMA_MARIADB', true);
            } else {
                define('PMA_MARIADB', false);
            }
        }

        if (PMA_MYSQL_INT_VERSION >  50503) {
            $default_charset = 'utf8mb4';
            $default_collation = 'utf8mb4_general_ci';
        } else {
            $default_charset = 'utf8';
            $default_collation = 'utf8_general_ci';
        }
        $collation_connection = $GLOBALS['PMA_Config']->get('collation_connection');
        if (! empty($collation_connection)) {
            $this->query(
                "SET CHARACTER SET '$default_charset';",
                $link,
                self::QUERY_STORE
            );
            /* Automatically adjust collation if not supported by server */
            if ($default_charset == 'utf8'
                && strncmp('utf8mb4_', $collation_connection, 8) == 0
            ) {
                $collation_connection = 'utf8_' . substr($collation_connection, 8);
            }
            $result = $this->tryQuery(
                "SET collation_connection = '"
                . $this->escapeString($collation_connection, $link)
                . "';",
                $link,
                self::QUERY_STORE
            );
            if ($result === false) {
                trigger_error(
                    __('Failed to set configured collation connection!'),
                    E_USER_WARNING
                );
                $this->query(
                    "SET collation_connection = '"
                    . $this->escapeString($collation_connection, $link)
                    . "';",
                    $link,
                    self::QUERY_STORE
                );
            }
        } else {
            $this->query(
                "SET NAMES '$default_charset' COLLATE '$default_collation';",
                $link,
                self::QUERY_STORE
            );
        }

        /* Locale for messages */
        $locale = LanguageManager::getInstance()->getCurrentLanguage()->getMySQLLocale();
        if (! empty($locale)) {
            $this->query(
                "SET lc_messages = '" . $locale . "';",
                $link,
                self::QUERY_STORE
            );
        }
    }

    /**
     * returns a single value from the given result or query,
     * if the query or the result has more than one row or field
     * the first field of the first row is returned
     *
     * <code>
     * $sql = 'SELECT `name` FROM `user` WHERE `id` = 123';
     * $user_name = $GLOBALS['dbi']->fetchValue($sql);
     * // produces
     * // $user_name = 'John Doe'
     * </code>
     *
     * @param string         $query      The query to execute
     * @param integer        $row_number row to fetch the value from,
     *                                   starting at 0, with 0 being default
     * @param integer|string $field      field to fetch the value from,
     *                                   starting at 0, with 0 being default
     * @param object         $link       mysql link
     *
     * @return mixed value of first field in first row from result
     *               or false if not found
     */
    public function fetchValue($query, $row_number = 0, $field = 0, $link = null)
    {
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
        if ($this->numRows($result) < ($row_number + 1)) {
            return $value;
        }

        // if $field is an integer use non associative mysql fetch function
        if (is_int($field)) {
            $fetch_function = 'fetchRow';
        } else {
            $fetch_function = 'fetchAssoc';
        }

        // get requested row
        for ($i = 0; $i <= $row_number; $i++) {
            $row = $this->$fetch_function($result);
        }
        $this->freeResult($result);

        // return requested field
        if (isset($row[$field])) {
            $value = $row[$field];
        }

        return $value;
    }

    /**
     * returns only the first row from the result
     *
     * <code>
     * $sql = 'SELECT * FROM `user` WHERE `id` = 123';
     * $user = $GLOBALS['dbi']->fetchSingleRow($sql);
     * // produces
     * // $user = array('id' => 123, 'name' => 'John Doe')
     * </code>
     *
     * @param string $query The query to execute
     * @param string $type  NUM|ASSOC|BOTH returned array should either
     *                      numeric associative or both
     * @param object $link  mysql link
     *
     * @return array|boolean first row from result
     *                       or false if result is empty
     */
    public function fetchSingleRow($query, $type = 'ASSOC', $link = null)
    {
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
        if (! $this->numRows($result)) {
            return false;
        }

        switch ($type) {
        case 'NUM' :
            $fetch_function = 'fetchRow';
            break;
        case 'ASSOC' :
            $fetch_function = 'fetchAssoc';
            break;
        case 'BOTH' :
        default :
            $fetch_function = 'fetchArray';
            break;
        }

        $row = $this->$fetch_function($result);
        $this->freeResult($result);
        return $row;
    }

    /**
     * Returns row or element of a row
     *
     * @param array       $row   Row to process
     * @param string|null $value Which column to return
     *
     * @return mixed
     */
    private function _fetchValue($row, $value)
    {
        if (is_null($value)) {
            return $row;
        } else {
            return $row[$value];
        }
    }

    /**
     * returns all rows in the resultset in one array
     *
     * <code>
     * $sql = 'SELECT * FROM `user`';
     * $users = $GLOBALS['dbi']->fetchResult($sql);
     * // produces
     * // $users[] = array('id' => 123, 'name' => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $GLOBALS['dbi']->fetchResult($sql, 'id');
     * // produces
     * // $users['123'] = array('id' => 123, 'name' => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $GLOBALS['dbi']->fetchResult($sql, 0);
     * // produces
     * // $users['123'] = array(0 => 123, 1 => 'John Doe')
     *
     * $sql = 'SELECT `id`, `name` FROM `user`';
     * $users = $GLOBALS['dbi']->fetchResult($sql, 'id', 'name');
     * // or
     * $users = $GLOBALS['dbi']->fetchResult($sql, 0, 1);
     * // produces
     * // $users['123'] = 'John Doe'
     *
     * $sql = 'SELECT `name` FROM `user`';
     * $users = $GLOBALS['dbi']->fetchResult($sql);
     * // produces
     * // $users[] = 'John Doe'
     *
     * $sql = 'SELECT `group`, `name` FROM `user`'
     * $users = $GLOBALS['dbi']->fetchResult($sql, array('group', null), 'name');
     * // produces
     * // $users['admin'][] = 'John Doe'
     *
     * $sql = 'SELECT `group`, `name` FROM `user`'
     * $users = $GLOBALS['dbi']->fetchResult($sql, array('group', 'name'), 'id');
     * // produces
     * // $users['admin']['John Doe'] = '123'
     * </code>
     *
     * @param string               $query   query to execute
     * @param string|integer|array $key     field-name or offset
     *                                      used as key for array
     *                                      or array of those
     * @param string|integer       $value   value-name or offset
     *                                      used as value for array
     * @param object               $link    mysql link
     * @param integer              $options query options
     *
     * @return array resultrows or values indexed by $key
     */
    public function fetchResult($query, $key = null, $value = null,
        $link = null, $options = 0
    ) {
        $resultrows = array();

        $result = $this->tryQuery($query, $link, $options, false);

        // return empty array if result is empty or false
        if ($result === false) {
            return $resultrows;
        }

        $fetch_function = 'fetchAssoc';

        // no nested array if only one field is in result
        if (null === $key && 1 === $this->numFields($result)) {
            $value = 0;
            $fetch_function = 'fetchRow';
        }

        // if $key is an integer use non associative mysql fetch function
        if (is_int($key)) {
            $fetch_function = 'fetchRow';
        }

        if (null === $key) {
            while ($row = $this->$fetch_function($result)) {
                $resultrows[] = $this->_fetchValue($row, $value);
            }
        } else {
            if (is_array($key)) {
                while ($row = $this->$fetch_function($result)) {
                    $result_target =& $resultrows;
                    foreach ($key as $key_index) {
                        if (null === $key_index) {
                            $result_target =& $result_target[];
                            continue;
                        }

                        if (! isset($result_target[$row[$key_index]])) {
                            $result_target[$row[$key_index]] = array();
                        }
                        $result_target =& $result_target[$row[$key_index]];
                    }
                    $result_target = $this->_fetchValue($row, $value);
                }
            } else {
                while ($row = $this->$fetch_function($result)) {
                    $resultrows[$row[$key]] = $this->_fetchValue($row, $value);
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
    public function getCompatibilities()
    {
        $compats = array('NONE');
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
     * @param object $link mysql link resource
     *
     * @return array warnings
     */
    public function getWarnings($link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }

        return $this->fetchResult('SHOW WARNINGS', null, null, $link);
    }

    /**
     * returns an array of PROCEDURE or FUNCTION names for a db
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION
     * @param object $link  mysql link
     *
     * @return array the procedure names or function names
     */
    public function getProceduresOrFunctions($db, $which, $link = null)
    {
        $shows = $this->fetchResult(
            'SHOW ' . $which . ' STATUS;', null, null, $link
        );
        $result = array();
        foreach ($shows as $one_show) {
            if ($one_show['Db'] == $db && $one_show['Type'] == $which) {
                $result[] = $one_show['Name'];
            }
        }
        return($result);
    }

    /**
     * returns the definition of a specific PROCEDURE, FUNCTION, EVENT or VIEW
     *
     * @param string $db    db name
     * @param string $which PROCEDURE | FUNCTION | EVENT | VIEW
     * @param string $name  the procedure|function|event|view name
     * @param object $link  MySQL link
     *
     * @return string the definition
     */
    public function getDefinition($db, $which, $name, $link = null)
    {
        $returned_field = array(
            'PROCEDURE' => 'Create Procedure',
            'FUNCTION'  => 'Create Function',
            'EVENT'     => 'Create Event',
            'VIEW'      => 'Create View'
        );
        $query = 'SHOW CREATE ' . $which . ' '
            . Util::backquote($db) . '.'
            . Util::backquote($name);
        return($this->fetchValue($query, 0, $returned_field[$which], $link));
    }

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
    public function getRoutines($db, $which = null, $name = '')
    {
        $routines = array();
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = "SELECT"
                . " `ROUTINE_SCHEMA` AS `Db`,"
                . " `SPECIFIC_NAME` AS `Name`,"
                . " `ROUTINE_TYPE` AS `Type`,"
                . " `DEFINER` AS `Definer`,"
                . " `LAST_ALTERED` AS `Modified`,"
                . " `CREATED` AS `Created`,"
                . " `SECURITY_TYPE` AS `Security_type`,"
                . " `ROUTINE_COMMENT` AS `Comment`,"
                . " `CHARACTER_SET_CLIENT` AS `character_set_client`,"
                . " `COLLATION_CONNECTION` AS `collation_connection`,"
                . " `DATABASE_COLLATION` AS `Database Collation`,"
                . " `DTD_IDENTIFIER`"
                . " FROM `information_schema`.`ROUTINES`"
                . " WHERE `ROUTINE_SCHEMA` " . Util::getCollateForIS()
                . " = '" . $GLOBALS['dbi']->escapeString($db) . "'";
            if (PMA_isValid($which, array('FUNCTION','PROCEDURE'))) {
                $query .= " AND `ROUTINE_TYPE` = '" . $which . "'";
            }
            if (! empty($name)) {
                $query .= " AND `SPECIFIC_NAME`"
                    . " = '" . $GLOBALS['dbi']->escapeString($name) . "'";
            }
            $result = $this->fetchResult($query);
            if (!empty($result)) {
                $routines = $result;
            }
        } else {
            if ($which == 'FUNCTION' || $which == null) {
                $query = "SHOW FUNCTION STATUS"
                    . " WHERE `Db` = '" . $GLOBALS['dbi']->escapeString($db) . "'";
                if (! empty($name)) {
                    $query .= " AND `Name` = '"
                        . $GLOBALS['dbi']->escapeString($name) . "'";
                }
                $result = $this->fetchResult($query);
                if (!empty($result)) {
                    $routines = array_merge($routines, $result);
                }
            }
            if ($which == 'PROCEDURE' || $which == null) {
                $query = "SHOW PROCEDURE STATUS"
                    . " WHERE `Db` = '" . $GLOBALS['dbi']->escapeString($db) . "'";
                if (! empty($name)) {
                    $query .= " AND `Name` = '"
                        . $GLOBALS['dbi']->escapeString($name) . "'";
                }
                $result = $this->fetchResult($query);
                if (!empty($result)) {
                    $routines = array_merge($routines, $result);
                }
            }
        }

        $ret = array();
        foreach ($routines as $routine) {
            $one_result = array();
            $one_result['db'] = $routine['Db'];
            $one_result['name'] = $routine['Name'];
            $one_result['type'] = $routine['Type'];
            $one_result['definer'] = $routine['Definer'];
            $one_result['returns'] = isset($routine['DTD_IDENTIFIER'])
                ? $routine['DTD_IDENTIFIER'] : "";
            $ret[] = $one_result;
        }

        // Sort results by name
        $name = array();
        foreach ($ret as $value) {
            $name[] = $value['name'];
        }
        array_multisort($name, SORT_ASC, $ret);

        return($ret);
    }

    /**
     * returns details about the EVENTs for a specific database
     *
     * @param string $db   db name
     * @param string $name event name
     *
     * @return array information about EVENTs
     */
    public function getEvents($db, $name = '')
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = "SELECT"
                . " `EVENT_SCHEMA` AS `Db`,"
                . " `EVENT_NAME` AS `Name`,"
                . " `DEFINER` AS `Definer`,"
                . " `TIME_ZONE` AS `Time zone`,"
                . " `EVENT_TYPE` AS `Type`,"
                . " `EXECUTE_AT` AS `Execute at`,"
                . " `INTERVAL_VALUE` AS `Interval value`,"
                . " `INTERVAL_FIELD` AS `Interval field`,"
                . " `STARTS` AS `Starts`,"
                . " `ENDS` AS `Ends`,"
                . " `STATUS` AS `Status`,"
                . " `ORIGINATOR` AS `Originator`,"
                . " `CHARACTER_SET_CLIENT` AS `character_set_client`,"
                . " `COLLATION_CONNECTION` AS `collation_connection`, "
                . "`DATABASE_COLLATION` AS `Database Collation`"
                . " FROM `information_schema`.`EVENTS`"
                . " WHERE `EVENT_SCHEMA` " . Util::getCollateForIS()
                . " = '" . $GLOBALS['dbi']->escapeString($db) . "'";
            if (! empty($name)) {
                $query .= " AND `EVENT_NAME`"
                    . " = '" . $GLOBALS['dbi']->escapeString($name) . "'";
            }
        } else {
            $query = "SHOW EVENTS FROM " . Util::backquote($db);
            if (! empty($name)) {
                $query .= " AND `Name` = '"
                    . $GLOBALS['dbi']->escapeString($name) . "'";
            }
        }

        $result = array();
        if ($events = $this->fetchResult($query)) {
            foreach ($events as $event) {
                $one_result = array();
                $one_result['name'] = $event['Name'];
                $one_result['type'] = $event['Type'];
                $one_result['status'] = $event['Status'];
                $result[] = $one_result;
            }
        }

        // Sort results by name
        $name = array();
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
    public function getTriggers($db, $table = '', $delimiter = '//')
    {
        $result = array();
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION'
                . ', EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT'
                . ', EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER'
                . ' FROM information_schema.TRIGGERS'
                . ' WHERE EVENT_OBJECT_SCHEMA ' . Util::getCollateForIS() . '='
                . ' \'' . $GLOBALS['dbi']->escapeString($db) . '\'';

            if (! empty($table)) {
                $query .= " AND EVENT_OBJECT_TABLE " . Util::getCollateForIS()
                    . " = '" . $GLOBALS['dbi']->escapeString($table) . "';";
            }
        } else {
            $query = "SHOW TRIGGERS FROM " . Util::backquote($db);
            if (! empty($table)) {
                $query .= " LIKE '" . $GLOBALS['dbi']->escapeString($table) . "';";
            }
        }

        if ($triggers = $this->fetchResult($query)) {
            foreach ($triggers as $trigger) {
                if ($GLOBALS['cfg']['Server']['DisableIS']) {
                    $trigger['TRIGGER_NAME'] = $trigger['Trigger'];
                    $trigger['ACTION_TIMING'] = $trigger['Timing'];
                    $trigger['EVENT_MANIPULATION'] = $trigger['Event'];
                    $trigger['EVENT_OBJECT_TABLE'] = $trigger['Table'];
                    $trigger['ACTION_STATEMENT'] = $trigger['Statement'];
                    $trigger['DEFINER'] = $trigger['Definer'];
                }
                $one_result = array();
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
        }

        // Sort results by name
        $name = array();
        foreach ($result as $value) {
            $name[] = $value['name'];
        }
        array_multisort($name, SORT_ASC, $result);

        return($result);
    }

    /**
     * Formats database error message in a friendly way.
     * This is needed because some errors messages cannot
     * be obtained by mysql_error().
     *
     * @param int    $error_number  Error code
     * @param string $error_message Error message as returned by server
     *
     * @return string HML text with error details
     */
    public function formatError($error_number, $error_message)
    {
        $error_message = htmlspecialchars($error_message);

        $error = '#' . ((string) $error_number);
        $separator = ' &mdash; ';

        if ($error_number == 2002) {
            $error .= ' - ' . $error_message;
            $error .= $separator;
            $error .= __(
                'The server is not responding (or the local server\'s socket'
                . ' is not correctly configured).'
            );
        } elseif ($error_number == 2003) {
            $error .= ' - ' . $error_message;
            $error .= $separator . __('The server is not responding.');
        } elseif ($error_number == 1005) {
            if (strpos($error_message, 'errno: 13') !== false) {
                $error .= ' - ' . $error_message;
                $error .= $separator
                    . __(
                        'Please check privileges of directory containing database.'
                    );
            } else {
                /* InnoDB constraints, see
                 * https://dev.mysql.com/doc/refman/5.0/en/
                 *  innodb-foreign-key-constraints.html
                 */
                $error .= ' - ' . $error_message .
                    ' (<a href="server_engines.php' .
                    PMA_URL_getCommon(
                        array('engine' => 'InnoDB', 'page' => 'Status')
                    ) . '">' . __('Details…') . '</a>)';
            }
        } else {
            $error .= ' - ' . $error_message;
        }

        return $error;
    }

    /**
     * gets the current user with host
     *
     * @return string the current user i.e. user@host
     */
    public function getCurrentUser()
    {
        if (Util::cacheExists('mysql_cur_user')) {
            return Util::cacheGet('mysql_cur_user');
        }
        $user = $GLOBALS['dbi']->fetchValue('SELECT CURRENT_USER();');
        if ($user !== false) {
            Util::cacheSet('mysql_cur_user', $user);
            return Util::cacheGet('mysql_cur_user');
        }
        return '';
    }

    /**
     * Checks if current user is superuser
     *
     * @return bool Whether user is a superuser
     */
    public function isSuperuser()
    {
        return self::isUserType('super');
    }

    /**
     * Checks if current user has global create user/grant privilege
     * or is a superuser (i.e. SELECT on mysql.users)
     * while caching the result in session.
     *
     * @param string $type type of user to check for
     *                     i.e. 'create', 'grant', 'super'
     *
     * @return bool Whether user is a given type of user
     */
    public function isUserType($type)
    {
        if (Util::cacheExists('is_' . $type . 'user')) {
            return Util::cacheGet('is_' . $type . 'user');
        }

        // when connection failed we don't have a $userlink
        if (! isset($GLOBALS['userlink'])) {
            Util::cacheSet('is_' . $type . 'user', false);
            return Util::cacheGet('is_' . $type . 'user');
        }

        if (! $GLOBALS['cfg']['Server']['DisableIS'] || $type === 'super') {
            // Prepare query for each user type check
            $query = '';
            if ($type === 'super') {
                $query = 'SELECT 1 FROM mysql.user LIMIT 1';
            } elseif ($type === 'create') {
                list($user, $host) = $this->_getCurrentUserAndHost();
                $query = "SELECT 1 FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` "
                    . "WHERE `PRIVILEGE_TYPE` = 'CREATE USER' AND "
                    . "'''" . $user . "''@''" . $host . "''' LIKE `GRANTEE` LIMIT 1";
            } elseif ($type === 'grant') {
                list($user, $host) = $this->_getCurrentUserAndHost();
                $query = "SELECT 1 FROM ("
                    . "SELECT `GRANTEE`, `IS_GRANTABLE` FROM "
                    . "`INFORMATION_SCHEMA`.`COLUMN_PRIVILEGES` UNION "
                    . "SELECT `GRANTEE`, `IS_GRANTABLE` FROM "
                    . "`INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` UNION "
                    . "SELECT `GRANTEE`, `IS_GRANTABLE` FROM "
                    . "`INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` UNION "
                    . "SELECT `GRANTEE`, `IS_GRANTABLE` FROM "
                    . "`INFORMATION_SCHEMA`.`USER_PRIVILEGES`) t "
                    . "WHERE `IS_GRANTABLE` = 'YES' AND "
                    . "'''" . $user . "''@''" . $host . "''' LIKE `GRANTEE` LIMIT 1";
            }

            $is = false;
            $result = $GLOBALS['dbi']->tryQuery(
                $query,
                $GLOBALS['userlink'],
                self::QUERY_STORE
            );
            if ($result) {
                $is = (bool) $GLOBALS['dbi']->numRows($result);
            }
            $GLOBALS['dbi']->freeResult($result);

            Util::cacheSet('is_' . $type . 'user', $is);
        } else {
            $is = false;
            $grants = $GLOBALS['dbi']->fetchResult(
                "SHOW GRANTS FOR CURRENT_USER();",
                null,
                null,
                $GLOBALS['userlink'],
                self::QUERY_STORE
            );
            if ($grants) {
                foreach ($grants as $grant) {
                    if ($type === 'create') {
                        if (strpos($grant, "ALL PRIVILEGES ON *.*") !== false
                            || strpos($grant, "CREATE USER") !== false
                        ) {
                            $is = true;
                            break;
                        }
                    } elseif ($type === 'grant') {
                        if (strpos($grant, "WITH GRANT OPTION") !== false) {
                            $is = true;
                            break;
                        }
                    }
                }
            }

            Util::cacheSet('is_' . $type . 'user', $is);
        }

        return Util::cacheGet('is_' . $type . 'user');
    }

    /**
     * Get the current user and host
     *
     * @return array array of username and hostname
     */
    private function _getCurrentUserAndHost()
    {
        $user = $GLOBALS['dbi']->fetchValue("SELECT CURRENT_USER();");
        return explode("@", $user);
    }

    /**
     * Returns value for lower_case_table_names variable
     *
     * @return string
     */
    public function getLowerCaseNames()
    {
        if (is_null($this->_lower_case_table_names)) {
            $this->_lower_case_table_names = $this->fetchValue(
                "SELECT @@lower_case_table_names"
            );
        }
        return $this->_lower_case_table_names;
    }

    /**
     * Get the list of system schemas
     *
     * @return array list of system schemas
     */
    public function getSystemSchemas()
    {
        $schemas = array(
            'information_schema', 'performance_schema', 'mysql', 'sys'
        );
        $systemSchemas = array();
        foreach ($schemas as $schema) {
            if ($this->isSystemSchema($schema, true)) {
                $systemSchemas[] = $schema;
            }
        }
        return $systemSchemas;
    }

    /**
     * Checks whether given schema is a system schema
     *
     * @param string $schema_name        Name of schema (database) to test
     * @param bool   $testForMysqlSchema Whether 'mysql' schema should
     *                                   be treated the same as IS and DD
     *
     * @return bool
     */
    public function isSystemSchema($schema_name, $testForMysqlSchema = false)
    {
        $schema_name = strtolower($schema_name);
        return $schema_name == 'information_schema'
            || $schema_name == 'performance_schema'
            || ($schema_name == 'mysql' && $testForMysqlSchema)
            || $schema_name == 'sys';
    }

    /**
     * connects to the database server
     *
     * @param string $user                 user name
     * @param string $password             user password
     * @param bool   $is_controluser       whether this is a control user connection
     * @param array  $server               host/port/socket/persistent
     * @param bool   $auxiliary_connection (when true, don't go back to login if
     *                                     connection fails)
     *
     * @return mixed false on error or a connection object on success
     */
    public function connect(
        $user, $password, $is_controluser = false, $server = null,
        $auxiliary_connection = false
    ) {
        // Do not show location and backtrace for connection errors
        $GLOBALS['error_handler']->setHideLocation(true);
        $result = $this->_extension->connect(
            $user, $password, $is_controluser, $server, $auxiliary_connection
        );
        $GLOBALS['error_handler']->setHideLocation(false);

        if ($result) {
            if (! $auxiliary_connection && ! $is_controluser) {
                $GLOBALS['dbi']->postConnect($result);
            }
            return $result;
        }

        if ($is_controluser) {
            trigger_error(
                __(
                    'Connection for controluser as defined in your '
                    . 'configuration failed.'
                ),
                E_USER_WARNING
            );
            return false;
        }

        // Do not go back to main login if connection failed
        // (currently used only in unit testing)
        if ($auxiliary_connection) {
            return false;
        }

        PMA_logUser($user, 'mysql-denied');
        $GLOBALS['auth_plugin']->authFails();

        return $result;
    }

    /**
     * selects given database
     *
     * @param string $dbname database name to select
     * @param object $link   connection object
     *
     * @return boolean
     */
    public function selectDb($dbname, $link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        return $this->_extension->selectDb($dbname, $link);
    }

    /**
     * returns array of rows with associative and numeric keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchArray($result)
    {
        return $this->_extension->fetchArray($result);
    }

    /**
     * returns array of rows with associative keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchAssoc($result)
    {
        return $this->_extension->fetchAssoc($result);
    }

    /**
     * returns array of rows with numeric keys from $result
     *
     * @param object $result result set identifier
     *
     * @return array
     */
    public function fetchRow($result)
    {
        return $this->_extension->fetchRow($result);
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     *
     * @param object  $result database result
     * @param integer $offset offset to seek
     *
     * @return bool true on success, false on failure
     */
    public function dataSeek($result, $offset)
    {
        return $this->_extension->dataSeek($result, $offset);
    }

    /**
     * Frees memory associated with the result
     *
     * @param object $result database result
     *
     * @return void
     */
    public function freeResult($result)
    {
        $this->_extension->freeResult($result);
    }

    /**
     * Check if there are any more query results from a multi query
     *
     * @param object $link the connection object
     *
     * @return bool true or false
     */
    public function moreResults($link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        return $this->_extension->moreResults($link);
    }

    /**
     * Prepare next result from multi_query
     *
     * @param object $link the connection object
     *
     * @return bool true or false
     */
    public function nextResult($link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        return $this->_extension->nextResult($link);
    }

    /**
     * Store the result returned from multi query
     *
     * @param object $link the connection object
     *
     * @return mixed false when empty results / result set when not empty
     */
    public function storeResult($link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        return $this->_extension->storeResult($link);
    }

    /**
     * Returns a string representing the type of connection used
     *
     * @param object $link mysql link
     *
     * @return string type of connection used
     */
    public function getHostInfo($link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        return $this->_extension->getHostInfo($link);
    }

    /**
     * Returns the version of the MySQL protocol used
     *
     * @param object $link mysql link
     *
     * @return integer version of the MySQL protocol used
     */
    public function getProtoInfo($link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        return $this->_extension->getProtoInfo($link);
    }

    /**
     * returns a string that represents the client library version
     *
     * @return string MySQL client library version
     */
    public function getClientInfo()
    {
        return $this->_extension->getClientInfo();
    }

    /**
     * returns last error message or false if no errors occurred
     *
     * @param object $link connection link
     *
     * @return string|bool $error or false
     */
    public function getError($link = null)
    {
        $link = $this->getLink($link);
        return $this->_extension->getError($link);
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
        return $this->_extension->numRows($result);
    }

    /**
     * returns last inserted auto_increment id for given $link
     * or $GLOBALS['userlink']
     *
     * @param object $link the connection object
     *
     * @return int|boolean
     */
    public function insertId($link = null)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }
        // If the primary key is BIGINT we get an incorrect result
        // (sometimes negative, sometimes positive)
        // and in the present function we don't know if the PK is BIGINT
        // so better play safe and use LAST_INSERT_ID()
        //
        // When no controluser is defined, using mysqli_insert_id($link)
        // does not always return the last insert id due to a mixup with
        // the tracking mechanism, but this works:
        return $GLOBALS['dbi']->fetchValue('SELECT LAST_INSERT_ID();', 0, 0, $link);
    }

    /**
     * returns the number of rows affected by last query
     *
     * @param object $link           the connection object
     * @param bool   $get_from_cache whether to retrieve from cache
     *
     * @return int|boolean
     */
    public function affectedRows($link = null, $get_from_cache = true)
    {
        $link = $this->getLink($link);
        if ($link === false) {
            return false;
        }

        if ($get_from_cache) {
            return $GLOBALS['cached_affected_rows'];
        } else {
            return $this->_extension->affectedRows($link);
        }
    }

    /**
     * returns metainfo for fields in $result
     *
     * @param object $result result set identifier
     *
     * @return array meta info for fields in $result
     */
    public function getFieldsMeta($result)
    {
        $result = $this->_extension->getFieldsMeta($result);

        if ($this->getLowerCaseNames() === '2') {
            /**
             * Fixup orgtable for lower_case_table_names = 2
             *
             * In this setup MySQL server reports table name lower case
             * but we still need to operate on original case to properly
             * match existing strings
             */
            foreach ($result as $value) {
                if (strlen($value->orgtable) !== 0 &&
                        mb_strtolower($value->orgtable) === mb_strtolower($value->table)) {
                    $value->orgtable = $value->table;
                }
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
    public function numFields($result)
    {
        return $this->_extension->numFields($result);
    }

    /**
     * returns the length of the given field $i in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return int length of field
     */
    public function fieldLen($result, $i)
    {
        return $this->_extension->fieldLen($result, $i);
    }

    /**
     * returns name of $i. field in $result
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string name of $i. field in $result
     */
    public function fieldName($result, $i)
    {
        return $this->_extension->fieldName($result, $i);
    }

    /**
     * returns concatenated string of human readable field flags
     *
     * @param object $result result set identifier
     * @param int    $i      field
     *
     * @return string field flags
     */
    public function fieldFlags($result, $i)
    {
        return $this->_extension->fieldFlags($result, $i);
    }

    /**
     * returns properly escaped string for use in MySQL queries
     *
     * @param string $str  string to be escaped
     * @param mixed  $link optional database link to use
     *
     * @return string a MySQL escaped string
     */
    public function escapeString($str, $link = null)
    {
        if ($link === null) {
            $link = $this->getLink();
        }

        if ($this->_extension === null) {
            return $str;
        }

        return $this->_extension->escapeString($link, $str);
    }

    /**
     * Gets server connection port
     *
     * @param array|null $server host/port/socket/persistent
     *
     * @return int
     */
    public function getServerPort($server = null)
    {
        if (is_null($server)) {
            $server = &$GLOBALS['cfg']['Server'];
        }

        if (empty($server['port'])) {
            return 0;
        }
        return intval($server['port']);
    }

    /**
     * Gets server connection socket
     *
     * @param array|null $server host/port/socket/persistent
     *
     * @return null|string
     */
    public function getServerSocket($server = null)
    {
        if (is_null($server)) {
            $server = &$GLOBALS['cfg']['Server'];
        }

        if (empty($server['socket'])) {
            return null;
        } else {
            return $server['socket'];
        }
    }

    /**
     * Gets correct link object.
     *
     * @param object $link optional database link to use
     *
     * @return object|boolean
     */
    public function getLink($link = null)
    {
        if (! is_null($link) && $link !== false) {
            return $link;
        }

        if (isset($GLOBALS['userlink']) && !is_null($GLOBALS['userlink'])) {
            return $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    /**
     * Checks if this database server is running on Amazon RDS.
     *
     * @return boolean
     */
    public function isAmazonRds()
    {
        if (Util::cacheExists('is_amazon_rds')) {
            return Util::cacheGet('is_amazon_rds');
        }
        $sql = 'SELECT @@basedir';
        $result = $this->fetchResult($sql);
        $rds = ($result[0] == '/rdsdbbin/mysql/');
        Util::cacheSet('is_amazon_rds', $rds);

        return $rds;
    }

    /**
     * Gets SQL for killing a process.
     *
     * @param int $process Process ID
     *
     * @return string
     */
    public function getKillQuery($process)
    {
        if ($this->isAmazonRds()) {
            return 'CALL mysql.rds_kill(' . $process . ');';
        } else {
            return 'KILL ' . $process . ';';
        }
    }

    /**
     * Get the phpmyadmin database manager
     *
     * @return SystemDatabase
     */
    public function getSystemDatabase()
    {
        return new SystemDatabase($this);
    }

    /**
     * Get a table with database name and table name
     *
     * @param string $db_name    DB name
     * @param string $table_name Table name
     *
     * @return Table
     */
    public function getTable($db_name, $table_name)
    {
        return new Table($table_name, $db_name, $this);
    }
}
