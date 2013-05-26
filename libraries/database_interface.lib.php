<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common Option Constants For DBI Functions
 *
 * @package PhpMyAdmin-DBI
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Force STORE_RESULT method, ignored by classic MySQL.
 */
define('PMA_DBI_QUERY_STORE',       1);
/**
 * Do not read whole query.
 */
define('PMA_DBI_QUERY_UNBUFFERED',  2);
/**
 * Get session variable.
 */
define('PMA_DBI_GETVAR_SESSION',    1);
/**
 * Get global variable.
 */
define('PMA_DBI_GETVAR_GLOBAL',     2);

/**
 * Checks whether database extension is loaded
 *
 * @param string $extension mysql extension to check
 *
 * @return bool
 */
function PMA_DBI_checkDbExtension($extension = 'mysql')
{
    if ($extension == 'drizzle' && function_exists('drizzle_create')) {
        return true;
    } else if (function_exists($extension . '_connect')) {
        return true;
    }

    return false;
}

if (defined('TESTSUITE')) {
    /**
     * For testsuite we use dummy driver which can fake some queries.
     */
    include_once './libraries/dbi/dummy.lib.php';
} else {

    /**
     * check for requested extension
     */
    if (! PMA_DBI_checkDbExtension($GLOBALS['cfg']['Server']['extension'])) {

        // if it fails try alternative extension ...
        // and display an error ...

        /**
         * @todo add different messages for alternative extension
         * and complete fail (no alternative extension too)
         */
        PMA_warnMissingExtension(
            $GLOBALS['cfg']['Server']['extension'],
            false,
            PMA_Util::showDocu('faq', 'faqmysql')
        );

        if ($GLOBALS['cfg']['Server']['extension'] === 'mysql') {
            $alternativ_extension = 'mysqli';
        } else {
            $alternativ_extension = 'mysql';
        }

        if (! PMA_DBI_checkDbExtension($alternativ_extension)) {
            // if alternative fails too ...
            PMA_warnMissingExtension(
                $GLOBALS['cfg']['Server']['extension'],
                true,
                PMA_Util::showDocu('faq', 'faqmysql')
            );
        }

        $GLOBALS['cfg']['Server']['extension'] = $alternativ_extension;
        unset($alternativ_extension);
    }

    /**
     * Including The DBI Plugin
     */
    include_once './libraries/dbi/'
        . $GLOBALS['cfg']['Server']['extension'] . '.dbi.lib.php';

}

/**
 * runs a query
 *
 * @param string $query               SQL query to execte
 * @param mixed  $link                optional database link to use
 * @param int    $options             optional query options
 * @param bool   $cache_affected_rows whether to cache affected rows
 *
 * @return mixed
 */
function PMA_DBI_query($query, $link = null, $options = 0,
    $cache_affected_rows = true
) {
    $res = PMA_DBI_try_query($query, $link, $options, $cache_affected_rows)
        or PMA_Util::mysqlDie(PMA_DBI_getError($link), $query);
    return $res;
}

/**
 * Stores query data into session data for debugging purposes
 *
 * @param string   $query  Query text
 * @param resource $link   database link
 * @param resource $result Query result
 * @param integer  $time   Time to execute query
 *
 * @return void
 */
function PMA_DBI_DBG_query($query, $link, $result, $time)
{
    $hash = md5($query);

    if (isset($_SESSION['debug']['queries'][$hash])) {
        $_SESSION['debug']['queries'][$hash]['count']++;
    } else {
        $_SESSION['debug']['queries'][$hash] = array();
        if ($result == false) {
            $_SESSION['debug']['queries'][$hash]['error']
                = '<b style="color:red">' . mysqli_error($link) . '</b>';
        }
        $_SESSION['debug']['queries'][$hash]['count'] = 1;
        $_SESSION['debug']['queries'][$hash]['query'] = $query;
        $_SESSION['debug']['queries'][$hash]['time'] = $time;
    }

    $trace = array();
    foreach (debug_backtrace() as $trace_step) {
        $trace[]
            = (isset($trace_step['file'])
                ? PMA_Error::relPath($trace_step['file'])
                : '')
            . (isset($trace_step['line'])
                ?  '#' . $trace_step['line'] . ': '
                : '')
            . (isset($trace_step['class']) ? $trace_step['class'] : '')
            . (isset($trace_step['type']) ? $trace_step['type'] : '')
            . (isset($trace_step['function']) ? $trace_step['function'] : '')
            . '('
            . (isset($trace_step['params'])
                ? implode(', ', $trace_step['params'])
                : ''
            )
            . ')'
            ;
    }
    $_SESSION['debug']['queries'][$hash]['trace'][] = $trace;
}

/**
 * runs a query and returns the result
 *
 * @param string   $query               query to run
 * @param resource $link                mysql link resource
 * @param integer  $options             query options
 * @param bool     $cache_affected_rows whether to cache affected row
 *
 * @return mixed
 */
function PMA_DBI_try_query($query, $link = null, $options = 0,
    $cache_affected_rows = true
) {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    if ($GLOBALS['cfg']['DBG']['sql']) {
        $time = microtime(true);
    }

    $result = PMA_DBI_real_query($query, $link, $options);

    if ($cache_affected_rows) {
        $GLOBALS['cached_affected_rows'] = PMA_DBI_affected_rows($link, false);
    }

    if ($GLOBALS['cfg']['DBG']['sql']) {
        $time = microtime(true) - $time;
        PMA_DBI_DBG_query($query, $link, $result, $time);
    }
    if ($result != false && PMA_Tracker::isActive() == true ) {
        PMA_Tracker::handleQuery($query);
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
function PMA_DBI_try_multi_query($multi_query = '', $link = null)
{

    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    return PMA_DBI_real_multi_query($link, $multi_query);

}

/**
 * converts charset of a mysql message, usually coming from mysql_error(),
 * into PMA charset, usally UTF-8
 * uses language to charset mapping from mysql/share/errmsg.txt
 * and charset names to ISO charset from information_schema.CHARACTER_SETS
 *
 * @param string $message the message
 *
 * @return string  $message
 */
function PMA_DBI_convert_message($message)
{
    // latin always last!
    $encodings = array(
        'japanese'      => 'EUC-JP', //'ujis',
        'japanese-sjis' => 'Shift-JIS', //'sjis',
        'korean'        => 'EUC-KR', //'euckr',
        'russian'       => 'KOI8-R', //'koi8r',
        'ukrainian'     => 'KOI8-U', //'koi8u',
        'greek'         => 'ISO-8859-7', //'greek',
        'serbian'       => 'CP1250', //'cp1250',
        'estonian'      => 'ISO-8859-13', //'latin7',
        'slovak'        => 'ISO-8859-2', //'latin2',
        'czech'         => 'ISO-8859-2', //'latin2',
        'hungarian'     => 'ISO-8859-2', //'latin2',
        'polish'        => 'ISO-8859-2', //'latin2',
        'romanian'      => 'ISO-8859-2', //'latin2',
        'spanish'       => 'CP1252', //'latin1',
        'swedish'       => 'CP1252', //'latin1',
        'italian'       => 'CP1252', //'latin1',
        'norwegian-ny'  => 'CP1252', //'latin1',
        'norwegian'     => 'CP1252', //'latin1',
        'portuguese'    => 'CP1252', //'latin1',
        'danish'        => 'CP1252', //'latin1',
        'dutch'         => 'CP1252', //'latin1',
        'english'       => 'CP1252', //'latin1',
        'french'        => 'CP1252', //'latin1',
        'german'        => 'CP1252', //'latin1',
    );

    $server_language = PMA_DBI_fetch_value(
        'SHOW VARIABLES LIKE \'language\';',
        0,
        1
    );
    if ($server_language) {
        $found = array();
        $match = preg_match(
            '&(?:\\\|\\/)([^\\\\\/]*)(?:\\\|\\/)$&i',
            $server_language,
            $found
        );
        if ($match) {
            $server_language = $found[1];
        }
    }

    if (! empty($server_language) && isset($encodings[$server_language])) {
        $encoding = $encodings[$server_language];
    } else {
        /* Fallback to CP1252 if we can not detect */
        $encoding = 'CP1252';
    }

    if (function_exists('iconv')) {
        if ((@stristr(PHP_OS, 'AIX'))
            && (@strcasecmp(ICONV_IMPL, 'unknown') == 0)
            && (@strcasecmp(ICONV_VERSION, 'unknown') == 0)
        ) {
            include_once './libraries/iconv_wrapper.lib.php';
            $message = PMA_aix_iconv_wrapper(
                $encoding,
                'utf-8' . $GLOBALS['cfg']['IconvExtraParams'],
                $message
            );
        } else {
            $message = iconv(
                $encoding,
                'utf-8' . $GLOBALS['cfg']['IconvExtraParams'],
                $message
            );
        }
    } elseif (function_exists('recode_string')) {
        $message = recode_string(
            $encoding . '..'  . 'utf-8',
            $message
        );
    } elseif (function_exists('libiconv')) {
        $message = libiconv($encoding, 'utf-8', $message);
    } elseif (function_exists('mb_convert_encoding')) {
        // do not try unsupported charsets
        if (! in_array($server_language, array('ukrainian', 'greek', 'serbian'))) {
            $message = mb_convert_encoding(
                $message,
                'utf-8',
                $encoding
            );
        }
    }

    return $message;
}

/**
 * returns array with table names for given db
 *
 * @param string $database name of database
 * @param mixed  $link     mysql link resource|object
 *
 * @return array   tables names
 */
function PMA_DBI_get_tables($database, $link = null)
{
    return PMA_DBI_fetch_result(
        'SHOW TABLES FROM ' . PMA_Util::backquote($database) . ';',
        null,
        0,
        $link,
        PMA_DBI_QUERY_STORE
    );
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
function PMA_usort_comparison_callback($a, $b)
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
} // end of the 'PMA_usort_comparison_callback()' function

/**
 * returns array of all tables in given db or dbs
 * this function expects unquoted names:
 * RIGHT: my_database
 * WRONG: `my_database`
 * WRONG: my\_database
 * if $tbl_is_group is true, $table is used as filter for table names
 * if $tbl_is_group is 'comment, $table is used as filter for table comments
 *
 * <code>
 * PMA_DBI_get_tables_full('my_database');
 * PMA_DBI_get_tables_full('my_database', 'my_table'));
 * PMA_DBI_get_tables_full('my_database', 'my_tables_', true));
 * PMA_DBI_get_tables_full('my_database', 'my_tables_', 'comment'));
 * </code>
 *
 * @param string          $database     database
 * @param string|bool     $table        table or false
 * @param boolean|string  $tbl_is_group $table is a table group
 * @param mixed           $link         mysql link
 * @param integer         $limit_offset zero-based offset for the count
 * @param boolean|integer $limit_count  number of tables to return
 * @param string          $sort_by      table attribute to sort by
 * @param string          $sort_order   direction to sort (ASC or DESC)
 *
 * @todo    move into PMA_Table
 *
 * @return array           list of tables in given db(s)
 */
function PMA_DBI_get_tables_full($database, $table = false,
    $tbl_is_group = false,  $link = null, $limit_offset = 0,
    $limit_count = false, $sort_by = 'Name', $sort_order = 'ASC'
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
        // get table information from information_schema
        if ($table) {
            if (true === $tbl_is_group) {
                $sql_where_table = 'AND t.`TABLE_NAME` LIKE \''
                    . PMA_Util::escapeMysqlWildcards(PMA_Util::sqlAddSlashes($table))
                    . '%\'';
            } elseif ('comment' === $tbl_is_group) {
                $sql_where_table = 'AND t.`TABLE_COMMENT` LIKE \''
                    . PMA_Util::escapeMysqlWildcards(PMA_Util::sqlAddSlashes($table))
                    . '%\'';
            } else {
                $sql_where_table = 'AND t.`TABLE_NAME` = \''
                    . PMA_Util::sqlAddSlashes($table) . '\'';
            }
        } else {
            $sql_where_table = '';
        }

        // for PMA bc:
        // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
        //
        // on non-Windows servers,
        // added BINARY in the WHERE clause to force a case sensitive
        // comparison (if we are looking for the db Aa we don't want
        // to find the db aa)
        $this_databases = array_map('PMA_Util::sqlAddSlashes', $databases);

        if (PMA_DRIZZLE) {
            $engine_info = PMA_Util::cacheGet('drizzle_engines', true);
            $stats_join = "LEFT JOIN (SELECT 0 NUM_ROWS) AS stat ON false";
            if (isset($engine_info['InnoDB'])
                && $engine_info['InnoDB']['module_library'] == 'innobase'
            ) {
                $stats_join = "LEFT JOIN data_dictionary.INNODB_SYS_TABLESTATS stat"
                    . " ON (t.ENGINE = 'InnoDB' AND stat.NAME"
                    . " = (t.TABLE_SCHEMA || '/') || t.TABLE_NAME)";
            }

            // data_dictionary.table_cache may not contain any data for some tables,
            // it's just a table cache
            // auto_increment == 0 is cast to NULL because currently (2011.03.13 GA)
            // Drizzle doesn't provide correct value
            $sql = "
                SELECT t.*,
                    t.TABLE_SCHEMA        AS `Db`,
                    t.TABLE_NAME          AS `Name`,
                    t.TABLE_TYPE          AS `TABLE_TYPE`,
                    t.ENGINE              AS `Engine`,
                    t.ENGINE              AS `Type`,
                    t.TABLE_VERSION       AS `Version`,-- VERSION
                    t.ROW_FORMAT          AS `Row_format`,
                    coalesce(tc.ROWS, stat.NUM_ROWS)
                                          AS `Rows`,-- TABLE_ROWS,
                    coalesce(tc.ROWS, stat.NUM_ROWS)
                                          AS `TABLE_ROWS`,
                    tc.AVG_ROW_LENGTH     AS `Avg_row_length`, -- AVG_ROW_LENGTH
                    tc.TABLE_SIZE         AS `Data_length`, -- DATA_LENGTH
                    NULL                  AS `Max_data_length`, -- MAX_DATA_LENGTH
                    NULL                  AS `Index_length`, -- INDEX_LENGTH
                    NULL                  AS `Data_free`, -- DATA_FREE
                    nullif(t.AUTO_INCREMENT, 0)
                                          AS `Auto_increment`,
                    t.TABLE_CREATION_TIME AS `Create_time`, -- CREATE_TIME
                    t.TABLE_UPDATE_TIME   AS `Update_time`, -- UPDATE_TIME
                    NULL                  AS `Check_time`, -- CHECK_TIME
                    t.TABLE_COLLATION     AS `Collation`,
                    NULL                  AS `Checksum`, -- CHECKSUM
                    NULL                  AS `Create_options`, -- CREATE_OPTIONS
                    t.TABLE_COMMENT       AS `Comment`
                FROM data_dictionary.TABLES t
                    LEFT JOIN data_dictionary.TABLE_CACHE tc
                        ON tc.TABLE_SCHEMA = t.TABLE_SCHEMA AND tc.TABLE_NAME
                        = t.TABLE_NAME
                    $stats_join
                WHERE t.TABLE_SCHEMA IN ('" . implode("', '", $this_databases) . "')
                    " . $sql_where_table;
        } else {
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
                WHERE ' . (PMA_IS_WINDOWS ? '' : 'BINARY') . ' `TABLE_SCHEMA`
                    IN (\'' . implode("', '", $this_databases) . '\')
                    ' . $sql_where_table;
        }

        // Sort the tables
        $sql .= " ORDER BY $sort_by $sort_order";

        if ($limit_count) {
            $sql .= ' LIMIT ' . $limit_count . ' OFFSET ' . $limit_offset;
        }

        $tables = PMA_DBI_fetch_result(
            $sql, array('TABLE_SCHEMA', 'TABLE_NAME'), null, $link
        );
        unset($sql_where_table, $sql);

        if (PMA_DRIZZLE) {
            // correct I_S and D_D names returned by D_D.TABLES -
            // Drizzle generally uses lower case for them,
            // but TABLES returns uppercase
            foreach ((array)$database as $db) {
                $db_upper = strtoupper($db);
                if (!isset($tables[$db]) && isset($tables[$db_upper])) {
                    $tables[$db] = $tables[$db_upper];
                    unset($tables[$db_upper]);
                }
            }
        }

        if ($sort_by == 'Name' && $GLOBALS['cfg']['NaturalOrder']) {
            // here, the array's first key is by schema name
            foreach ($tables as $one_database_name => $one_database_tables) {
                uksort($one_database_tables, 'strnatcasecmp');

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
    if (empty($tables) && !PMA_DRIZZLE) {
        foreach ($databases as $each_database) {
            if ($table || (true === $tbl_is_group)) {
                $sql = 'SHOW TABLE STATUS FROM '
                    . PMA_Util::backquote($each_database)
                    .' LIKE \''
                    . PMA_Util::escapeMysqlWildcards(
                        PMA_Util::sqlAddSlashes($table, true)
                    )
                    . '%\'';
            } else {
                $sql = 'SHOW TABLE STATUS FROM '
                    . PMA_Util::backquote($each_database);
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

            if (!$each_tables) {
                $each_tables = PMA_DBI_fetch_result($sql, 'Name', null, $link);
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
                            $table_data['Data_length'] + $table_data['Index_length']
                        );
                    }
                } else {
                    foreach ($each_tables as $table_name => $table_data) {
                        ${$sort_by}[$table_name] = strtolower($table_data[$sort_by]);
                    }
                }

                if ($sort_order == 'DESC') {
                    array_multisort($$sort_by, SORT_DESC, $each_tables);
                } else {
                    array_multisort($$sort_by, SORT_ASC, $each_tables);
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
                if ('comment' === $tbl_is_group
                    && 0 === strpos($each_table['Comment'], $table)
                ) {
                    // remove table from list
                    unset($each_tables[$table_name]);
                    continue;
                }

                if (! isset($each_tables[$table_name]['Type'])
                    && isset($each_tables[$table_name]['Engine'])
                ) {
                    // pma BC, same parts of PMA still uses 'Type'
                    $each_tables[$table_name]['Type']
                        =& $each_tables[$table_name]['Engine'];
                } elseif (! isset($each_tables[$table_name]['Engine'])
                        && isset($each_tables[$table_name]['Type'])) {
                    // old MySQL reports Type, newer MySQL reports Engine
                    $each_tables[$table_name]['Engine']
                        =& $each_tables[$table_name]['Type'];
                }

                // MySQL forward compatibility
                // so pma could use this array as if every server is of version >5.0
                // todo : remove and check usage in the rest of the code,
                // MySQL 5.0 is required by current PMA version
                $each_tables[$table_name]['TABLE_SCHEMA']      = $each_database;
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
    // so PMA_Table does not require to issue SHOW TABLE STATUS again
    // Note: I don't see why we would need array_merge_recursive() here,
    // as it creates double entries for the same table (for example a double
    // entry for Comment when changing the storage engine in Operations)
    // Note 2: Instead of array_merge(), simply use the + operator because
    //  array_merge() renumbers numeric keys starting with 0, therefore
    //  we would lose a db name thats consists only of numbers
    foreach ($tables as $one_database => $its_tables) {
        if (isset(PMA_Table::$cache[$one_database])) {
            PMA_Table::$cache[$one_database]
                = PMA_Table::$cache[$one_database] + $tables[$one_database];
        } else {
            PMA_Table::$cache[$one_database] = $tables[$one_database];
        }
    }
    unset($one_database, $its_tables);

    if (! is_array($database)) {
        if (isset($tables[$database])) {
            return $tables[$database];
        } elseif (isset($tables[strtolower($database)])) {
            // on windows with lower_case_table_names = 1
            // MySQL returns
            // with SHOW DATABASES or information_schema.SCHEMATA: `Test`
            // but information_schema.TABLES gives `test`
            // bug #2036
            // https://sourceforge.net/p/phpmyadmin/bugs/2036/
            return $tables[strtolower($database)];
        } else {
            // one database but inexact letter case match
            // as Drizzle is always case insensitive,
            // we can safely return the only result
            if (PMA_DRIZZLE && count($tables) == 1) {
                $keys = array_keys($tables);
                if (strlen(array_pop($keys)) == strlen($database)) {
                    return array_pop($tables);
                }
            }
            return $tables;
        }
    } else {
        return $tables;
    }
}


/**
 * Get VIEWs in a particular database
 *
 * @param string $db Database name to look in
 *
 * @return array $views Set of VIEWs inside the database
 */
function PMA_DBI_getVirtualTables($db)
{

    $tables_full = PMA_DBI_get_tables_full($db);
    $views = array();

    foreach ($tables_full as $table=>$tmp) {

        if (PMA_Table::isView($db, $table)) {
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
 * @param resource $link         mysql link
 * @param string   $sort_by      column to order by
 * @param string   $sort_order   ASC or DESC
 * @param integer  $limit_offset starting offset for LIMIT
 * @param bool|int $limit_count  row count for LIMIT or true
 *                               for $GLOBALS['cfg']['MaxDbList']
 *
 * @todo    move into PMA_List_Database?
 *
 * @return array $databases
 */
function PMA_DBI_get_databases_full($database = null, $force_stats = false,
    $link = null, $sort_by = 'SCHEMA_NAME', $sort_order = 'ASC',
    $limit_offset = 0, $limit_count = false
) {
    $sort_order = strtoupper($sort_order);

    if (true === $limit_count) {
        $limit_count = $GLOBALS['cfg']['MaxDbList'];
    }

    // initialize to avoid errors when there are no databases
    $databases = array();

    $apply_limit_and_order_manual = true;

    if (! $GLOBALS['cfg']['Server']['DisableIS']) {
        /**
         * if $GLOBALS['cfg']['NaturalOrder'] is enabled, we cannot use LIMIT
         * cause MySQL does not support natural ordering, we have to do it afterward
         */
        $limit = '';
        if (!$GLOBALS['cfg']['NaturalOrder']) {
            if ($limit_count) {
                $limit = ' LIMIT ' . $limit_count . ' OFFSET ' . $limit_offset;
            }

            $apply_limit_and_order_manual = false;
        }

        // get table information from information_schema
        if ($database) {
            $sql_where_schema = 'WHERE `SCHEMA_NAME` LIKE \''
                . PMA_Util::sqlAddSlashes($database) . '\'';
        } else {
            $sql_where_schema = '';
        }

        if (PMA_DRIZZLE) {
            // data_dictionary.table_cache may not contain any data for some
            // tables, it's just a table cache
            $sql = 'SELECT
                s.SCHEMA_NAME,
                s.DEFAULT_COLLATION_NAME';
            if ($force_stats) {
                // no TABLE_CACHE data, stable results are better than
                // constantly changing
                $sql .= ',
                    COUNT(t.TABLE_SCHEMA) AS SCHEMA_TABLES,
                    SUM(stat.NUM_ROWS)    AS SCHEMA_TABLE_ROWS';
            }
            $sql .= '
                   FROM data_dictionary.SCHEMAS s';
            if ($force_stats) {
                $engine_info = PMA_Util::cacheGet('drizzle_engines', true);
                $stats_join = "LEFT JOIN (SELECT 0 NUM_ROWS) AS stat ON false";
                if (isset($engine_info['InnoDB'])
                    && $engine_info['InnoDB']['module_library'] == 'innobase'
                ) {
                    $stats_join = "LEFT JOIN data_dictionary.INNODB_SYS_TABLESTATS"
                        . " stat ON (t.ENGINE = 'InnoDB' AND stat.NAME"
                        . " = (t.TABLE_SCHEMA || '/') || t.TABLE_NAME)";
                }

                $sql .= "
                    LEFT JOIN data_dictionary.TABLES t
                        ON t.TABLE_SCHEMA = s.SCHEMA_NAME
                    $stats_join";
            }
            $sql .= $sql_where_schema . '
                    GROUP BY s.SCHEMA_NAME
                    ORDER BY ' . PMA_Util::backquote($sort_by) . ' ' . $sort_order
                . $limit;
        } else {
            $sql = 'SELECT
                s.SCHEMA_NAME,
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
                    SUM(t.DATA_FREE)       AS SCHEMA_DATA_FREE';
            }
            $sql .= '
                   FROM `information_schema`.SCHEMATA s';
            if ($force_stats) {
                $sql .= '
                    LEFT JOIN `information_schema`.TABLES t
                        ON BINARY t.TABLE_SCHEMA = BINARY s.SCHEMA_NAME';
            }
            $sql .= $sql_where_schema . '
                    GROUP BY BINARY s.SCHEMA_NAME
                    ORDER BY BINARY ' . PMA_Util::backquote($sort_by)
                . ' ' . $sort_order
                . $limit;
        }

        $databases = PMA_DBI_fetch_result($sql, 'SCHEMA_NAME', null, $link);

        $mysql_error = PMA_DBI_getError($link);
        if (! count($databases) && $GLOBALS['errno']) {
            PMA_Util::mysqlDie($mysql_error, $sql);
        }

        // display only databases also in official database list
        // f.e. to apply hide_db and only_db
        $drops = array_diff(
            array_keys($databases), (array) $GLOBALS['pma']->databases
        );
        if (count($drops)) {
            foreach ($drops as $drop) {
                unset($databases[$drop]);
            }
            unset($drop);
        }
        unset($sql_where_schema, $sql, $drops);
    } else {
        foreach ($GLOBALS['pma']->databases as $database_name) {
            // MySQL forward compatibility
            // so pma could use this array as if every server is of version >5.0
            // todo : remove and check the rest of the code for usage,
            // MySQL 5.0 or higher is required for current PMA version
            $databases[$database_name]['SCHEMA_NAME']      = $database_name;

            if ($force_stats) {
                include_once './libraries/mysql_charsets.lib.php';

                $databases[$database_name]['DEFAULT_COLLATION_NAME']
                    = PMA_getDbCollation($database_name);

                // get additional info about tables
                $databases[$database_name]['SCHEMA_TABLES']          = 0;
                $databases[$database_name]['SCHEMA_TABLE_ROWS']      = 0;
                $databases[$database_name]['SCHEMA_DATA_LENGTH']     = 0;
                $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH'] = 0;
                $databases[$database_name]['SCHEMA_INDEX_LENGTH']    = 0;
                $databases[$database_name]['SCHEMA_LENGTH']          = 0;
                $databases[$database_name]['SCHEMA_DATA_FREE']       = 0;

                $res = PMA_DBI_query(
                    'SHOW TABLE STATUS FROM '
                    . PMA_Util::backquote($database_name) . ';'
                );

                while ($row = PMA_DBI_fetch_assoc($res)) {
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
                PMA_DBI_free_result($res);
                unset($res);
            }
        }
    }


    /**
     * apply limit and order manually now
     * (caused by older MySQL < 5 or $GLOBALS['cfg']['NaturalOrder'])
     */
    if ($apply_limit_and_order_manual) {
        $GLOBALS['callback_sort_order'] = $sort_order;
        $GLOBALS['callback_sort_by'] = $sort_by;
        usort($databases, 'PMA_usort_comparison_callback');
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
function PMA_DBI_get_columns_full($database = null, $table = null,
    $column = null, $link = null
) {
    $columns = array();

    if (! $GLOBALS['cfg']['Server']['DisableIS']) {
        $sql_wheres = array();
        $array_keys = array();

        // get columns information from information_schema
        if (null !== $database) {
            $sql_wheres[] = '`TABLE_SCHEMA` = \''
                . PMA_Util::sqlAddSlashes($database) . '\' ';
        } else {
            $array_keys[] = 'TABLE_SCHEMA';
        }
        if (null !== $table) {
            $sql_wheres[] = '`TABLE_NAME` = \''
                . PMA_Util::sqlAddSlashes($table) . '\' ';
        } else {
            $array_keys[] = 'TABLE_NAME';
        }
        if (null !== $column) {
            $sql_wheres[] = '`COLUMN_NAME` = \''
                . PMA_Util::sqlAddSlashes($column) . '\' ';
        } else {
            $array_keys[] = 'COLUMN_NAME';
        }

        // for PMA bc:
        // `[SCHEMA_FIELD_NAME]` AS `[SHOW_FULL_COLUMNS_FIELD_NAME]`
        if (PMA_DRIZZLE) {
            $sql = "SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME,
                column_name        AS `Field`,
                (CASE
                    WHEN character_maximum_length > 0
                    THEN concat(lower(data_type), '(', character_maximum_length, ')')
                    WHEN numeric_precision > 0 OR numeric_scale > 0
                    THEN concat(lower(data_type), '(', numeric_precision,
                        ',', numeric_scale, ')')
                    WHEN enum_values IS NOT NULL
                        THEN concat(lower(data_type), '(', enum_values, ')')
                    ELSE lower(data_type) END)
                                   AS `Type`,
                collation_name     AS `Collation`,
                (CASE is_nullable
                    WHEN 1 THEN 'YES'
                    ELSE 'NO' END) AS `Null`,
                (CASE
                    WHEN is_used_in_primary THEN 'PRI'
                    ELSE '' END)   AS `Key`,
                column_default     AS `Default`,
                (CASE
                    WHEN is_auto_increment THEN 'auto_increment'
                    WHEN column_default_update
                    THEN 'on update ' || column_default_update
                    ELSE '' END)   AS `Extra`,
                NULL               AS `Privileges`,
                column_comment     AS `Comment`
            FROM data_dictionary.columns";
        } else {
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
        }
        if (count($sql_wheres)) {
            $sql .= "\n" . ' WHERE ' . implode(' AND ', $sql_wheres);
        }

        $columns = PMA_DBI_fetch_result($sql, $array_keys, null, $link);
        unset($sql_wheres, $sql);
    } else {
        if (null === $database) {
            foreach ($GLOBALS['pma']->databases as $database) {
                $columns[$database] = PMA_DBI_get_columns_full(
                    $database, null, null, $link
                );
            }
            return $columns;
        } elseif (null === $table) {
            $tables = PMA_DBI_get_tables($database);
            foreach ($tables as $table) {
                $columns[$table] = PMA_DBI_get_columns_full(
                    $database, $table, null, $link
                );
            }
            return $columns;
        }

        $sql = 'SHOW FULL COLUMNS FROM '
            . PMA_Util::backquote($database) . '.' . PMA_Util::backquote($table);
        if (null !== $column) {
            $sql .= " LIKE '" . PMA_Util::sqlAddSlashes($column, true) . "'";
        }

        $columns = PMA_DBI_fetch_result($sql, 'Field', null, $link);
    }
    $ordinal_position = 1;
    foreach ($columns as $column_name => $each_column) {

        // MySQL forward compatibility
        // so pma could use this array as if every server is of version >5.0
        // todo : remove and check the rest of the code for usage,
        // MySQL 5.0 or higher is required for current PMA version
        $columns[$column_name]['COLUMN_NAME'] =& $columns[$column_name]['Field'];
        $columns[$column_name]['COLUMN_TYPE'] =& $columns[$column_name]['Type'];
        $columns[$column_name]['COLLATION_NAME']
            =& $columns[$column_name]['Collation'];
        $columns[$column_name]['IS_NULLABLE'] =& $columns[$column_name]['Null'];
        $columns[$column_name]['COLUMN_KEY'] =& $columns[$column_name]['Key'];
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
        reset($columns);
        $columns = current($columns);
    }

    return $columns;
}

/**
 * Returns SQL query for fetching columns for a table
 *
 * The 'Key' column is not calculated properly, use PMA_DBI_get_columns() to get
 * correct values.
 *
 * @param string  $database name of database
 * @param string  $table    name of table to retrieve columns from
 * @param string  $column   name of column, null to show all columns
 * @param boolean $full     whether to return full info or only column names
 *
 * @see PMA_DBI_get_columns()
 *
 * @return string
 */
function PMA_DBI_get_columns_sql($database, $table, $column = null, $full = false)
{
    if (PMA_DRIZZLE) {
        // `Key` column:
        // * used in primary key => PRI
        // * unique one-column => UNI
        // * indexed, one-column or first in multi-column => MUL
        // Promotion of UNI to PRI in case no promary index exists
        // is done after query is executed
        $sql = "SELECT
                column_name        AS `Field`,
                (CASE
                    WHEN character_maximum_length > 0
                    THEN concat(lower(data_type), '(', character_maximum_length, ')')
                    WHEN numeric_precision > 0 OR numeric_scale > 0
                    THEN concat(lower(data_type), '(', numeric_precision,
                        ',', numeric_scale, ')')
                    WHEN enum_values IS NOT NULL
                        THEN concat(lower(data_type), '(', enum_values, ')')
                    ELSE lower(data_type) END)
                                   AS `Type`,
                " . ($full ? "
                collation_name     AS `Collation`," : '') . "
                (CASE is_nullable
                    WHEN 1 THEN 'YES'
                    ELSE 'NO' END) AS `Null`,
                (CASE
                    WHEN is_used_in_primary THEN 'PRI'
                    WHEN is_unique AND NOT is_multi THEN 'UNI'
                    WHEN is_indexed
                    AND (NOT is_multi OR is_first_in_multi) THEN 'MUL'
                    ELSE '' END)   AS `Key`,
                column_default     AS `Default`,
                (CASE
                    WHEN is_auto_increment THEN 'auto_increment'
                    WHEN column_default_update <> ''
                    THEN 'on update ' || column_default_update
                    ELSE '' END)   AS `Extra`
                " . ($full ? " ,
                NULL               AS `Privileges`,
                column_comment     AS `Comment`" : '') . "
            FROM data_dictionary.columns
            WHERE table_schema = '" . PMA_Util::sqlAddSlashes($database) . "'
                AND table_name = '" . PMA_Util::sqlAddSlashes($table) . "'
                " . (($column != null) ? "
                AND column_name = '" . PMA_Util::sqlAddSlashes($column) . "'" : '');
        // ORDER BY ordinal_position
    } else {
        $sql = 'SHOW ' . ($full ? 'FULL' : '') . ' COLUMNS FROM '
            . PMA_Util::backquote($database) . '.' . PMA_Util::backquote($table)
            . (($column != null) ? "LIKE '"
            . PMA_Util::sqlAddSlashes($column, true) . "'" : '');
    }
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
 * @return false|array   array indexed by column names or,
 *                        if $column is given, flat array description
 */
function PMA_DBI_get_columns($database, $table, $column = null, $full = false,
    $link = null
) {
    $sql = PMA_DBI_get_columns_sql($database, $table, $column, $full);
    $fields = PMA_DBI_fetch_result($sql, 'Field', null, $link);
    if (! is_array($fields) || count($fields) == 0) {
        return null;
    }
    if (PMA_DRIZZLE) {
        // fix Key column, it's much simpler in PHP than in SQL
        $has_pk = false;
        $has_pk_candidates = false;
        foreach ($fields as $f) {
            if ($f['Key'] == 'PRI') {
                $has_pk = true;
                break;
            } else if ($f['Null'] == 'NO'
                && ($f['Key'] == 'MUL'
                || $f['Key'] == 'UNI')
            ) {
                $has_pk_candidates = true;
            }
        }
        if (!$has_pk && $has_pk_candidates) {
            // check whether we can promote some unique index to PRI
            $sql = "
                SELECT i.index_name, p.column_name
                FROM data_dictionary.indexes i
                    JOIN data_dictionary.index_parts p
                    USING (table_schema, table_name)
                WHERE i.table_schema = '" . PMA_Util::sqlAddSlashes($database) . "'
                    AND i.table_name = '" . PMA_Util::sqlAddSlashes($table) . "'
                    AND i.is_unique
                        AND NOT i.is_nullable";
            $fs = PMA_DBI_fetch_result($sql, 'index_name', null, $link);
            $fs = $fs ? array_shift($fs) : array();
            foreach ($fs as $f) {
                $fields[$f]['Key'] = 'PRI';
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
function PMA_DBI_get_column_names($database, $table, $link = null)
{
    $sql = PMA_DBI_get_columns_sql($database, $table);
    // We only need the 'Field' column which contains the table's column names
    $fields = array_keys(PMA_DBI_fetch_result($sql, 'Field', null, $link));

    if ( ! is_array($fields) || count($fields) == 0 ) {
        return null;
    }
    return $fields;
}

/**
* Returns SQL for fetching information on table indexes (SHOW INDEXES)
*
* @param string $database name of database
* @param string $table    name of the table whose indexes are to be retreived
* @param string $where    additional conditions for WHERE
*
* @return array   $indexes
*/
function PMA_DBI_get_table_indexes_sql($database, $table, $where = null)
{
    if (PMA_DRIZZLE) {
        $sql = "SELECT
                ip.table_name          AS `Table`,
                (NOT ip.is_unique)     AS Non_unique,
                ip.index_name          AS Key_name,
                ip.sequence_in_index+1 AS Seq_in_index,
                ip.column_name         AS Column_name,
                (CASE
                    WHEN i.index_type = 'BTREE' THEN 'A'
                    ELSE NULL END)     AS Collation,
                NULL                   AS Cardinality,
                compare_length         AS Sub_part,
                NULL                   AS Packed,
                ip.is_nullable         AS `Null`,
                i.index_type           AS Index_type,
                NULL                   AS Comment,
                i.index_comment        AS Index_comment
            FROM data_dictionary.index_parts ip
                LEFT JOIN data_dictionary.indexes i
                USING (table_schema, table_name, index_name)
            WHERE table_schema = '" . PMA_Util::sqlAddSlashes($database) . "'
                AND table_name = '" . PMA_Util::sqlAddSlashes($table) . "'
        ";
    } else {
        $sql = 'SHOW INDEXES FROM ' . PMA_Util::backquote($database) . '.'
            . PMA_Util::backquote($table);
    }
    if ($where) {
        $sql .= (PMA_DRIZZLE ? ' AND (' : ' WHERE (') . $where . ')';
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
function PMA_DBI_get_table_indexes($database, $table, $link = null)
{
    $sql = PMA_DBI_get_table_indexes_sql($database, $table);
    $indexes = PMA_DBI_fetch_result($sql, null, null, $link);

    if (! is_array($indexes) || count($indexes) < 1) {
        return array();
    }
    return $indexes;
}

/**
 * returns value of given mysql server variable
 *
 * @param string $var  mysql server variable name
 * @param int    $type PMA_DBI_GETVAR_SESSION|PMA_DBI_GETVAR_GLOBAL
 * @param mixed  $link mysql link resource|object
 *
 * @return mixed   value for mysql server variable
 */
function PMA_DBI_get_variable($var, $type = PMA_DBI_GETVAR_SESSION, $link = null)
{
    if ($link === null) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return false;
        }
    }

    switch ($type) {
    case PMA_DBI_GETVAR_SESSION:
        $modifier = ' SESSION';
        break;
    case PMA_DBI_GETVAR_GLOBAL:
        $modifier = ' GLOBAL';
        break;
    default:
        $modifier = '';
    }
    return PMA_DBI_fetch_value(
        'SHOW' . $modifier . ' VARIABLES LIKE \'' . $var . '\';', 0, 1, $link
    );
}

/**
 * Function called just after a connection to the MySQL database server has
 * been established. It sets the connection collation, and determins the
 * version of MySQL which is running.
 *
 * @param mixed   $link           mysql link resource|object
 * @param boolean $is_controluser whether link is for control user
 *
 * @return void
 */
function PMA_DBI_postConnect($link, $is_controluser = false)
{
    if (! defined('PMA_MYSQL_INT_VERSION')) {
        if (PMA_Util::cacheExists('PMA_MYSQL_INT_VERSION', true)) {
            define(
                'PMA_MYSQL_INT_VERSION',
                PMA_Util::cacheGet('PMA_MYSQL_INT_VERSION', true)
            );
            define(
                'PMA_MYSQL_MAJOR_VERSION',
                PMA_Util::cacheGet('PMA_MYSQL_MAJOR_VERSION', true)
            );
            define(
                'PMA_MYSQL_STR_VERSION',
                PMA_Util::cacheGet('PMA_MYSQL_STR_VERSION', true)
            );
            define(
                'PMA_MYSQL_VERSION_COMMENT',
                PMA_Util::cacheGet('PMA_MYSQL_VERSION_COMMENT', true)
            );
        } else {
            $version = PMA_DBI_fetch_single_row(
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
                define('PMA_MYSQL_VERSION_COMMENT', $version['@@version_comment']);
            } else {
                define('PMA_MYSQL_INT_VERSION', 50015);
                define('PMA_MYSQL_MAJOR_VERSION', 5);
                define('PMA_MYSQL_STR_VERSION', '5.00.15');
                define('PMA_MYSQL_VERSION_COMMENT', '');
            }
            PMA_Util::cacheSet(
                'PMA_MYSQL_INT_VERSION',
                PMA_MYSQL_INT_VERSION,
                true
            );
            PMA_Util::cacheSet(
                'PMA_MYSQL_MAJOR_VERSION',
                PMA_MYSQL_MAJOR_VERSION,
                true
            );
            PMA_Util::cacheSet(
                'PMA_MYSQL_STR_VERSION',
                PMA_MYSQL_STR_VERSION,
                true
            );
            PMA_Util::cacheSet(
                'PMA_MYSQL_VERSION_COMMENT',
                PMA_MYSQL_VERSION_COMMENT,
                true
            );
        }
        // detect Drizzle by version number:
        // <year>.<month>.<build number>(.<patch rev)
        define('PMA_DRIZZLE', PMA_MYSQL_MAJOR_VERSION >= 2009);
    }

    // Skip charsets for Drizzle
    if (!PMA_DRIZZLE) {
        if (! empty($GLOBALS['collation_connection'])) {
            PMA_DBI_query("SET CHARACTER SET 'utf8';", $link, PMA_DBI_QUERY_STORE);
            $set_collation_con_query = "SET collation_connection = '"
                . PMA_Util::sqlAddSlashes($GLOBALS['collation_connection']) . "';";
            PMA_DBI_query(
                $set_collation_con_query,
                $link,
                PMA_DBI_QUERY_STORE
            );
        } else {
            PMA_DBI_query(
                "SET NAMES 'utf8' COLLATE 'utf8_general_ci';",
                $link,
                PMA_DBI_QUERY_STORE
            );
        }
    }

    // Cache plugin list for Drizzle
    if (PMA_DRIZZLE && !PMA_Util::cacheExists('drizzle_engines', true)) {
        $sql = "SELECT p.plugin_name, m.module_library
            FROM data_dictionary.plugins p
                JOIN data_dictionary.modules m USING (module_name)
            WHERE p.plugin_type = 'StorageEngine'
                AND p.plugin_name NOT IN ('FunctionEngine', 'schema')
                AND p.is_active = 'YES'";
        $engines = PMA_DBI_fetch_result($sql, 'plugin_name', null, $link);
        PMA_Util::cacheSet('drizzle_engines', $engines, true);
    }
}

/**
 * returns a single value from the given result or query,
 * if the query or the result has more than one row or field
 * the first field of the first row is returned
 *
 * <code>
 * $sql = 'SELECT `name` FROM `user` WHERE `id` = 123';
 * $user_name = PMA_DBI_fetch_value($sql);
 * // produces
 * // $user_name = 'John Doe'
 * </code>
 *
 * @param string|mysql_result $result     query or mysql result
 * @param integer             $row_number row to fetch the value from,
 *                                        starting at 0, with 0 being default
 * @param integer|string      $field      field to fetch the value from,
 *                                        starting at 0, with 0 being default
 * @param resource            $link       mysql link
 *
 * @return mixed value of first field in first row from result
 *               or false if not found
 */
function PMA_DBI_fetch_value($result, $row_number = 0, $field = 0, $link = null)
{
    $value = false;

    if (is_string($result)) {
        $result = PMA_DBI_try_query($result, $link, PMA_DBI_QUERY_STORE, false);
    }

    // return false if result is empty or false
    // or requested row is larger than rows in result
    if (PMA_DBI_num_rows($result) < ($row_number + 1)) {
        return $value;
    }

    // if $field is an integer use non associative mysql fetch function
    if (is_int($field)) {
        $fetch_function = 'PMA_DBI_fetch_row';
    } else {
        $fetch_function = 'PMA_DBI_fetch_assoc';
    }

    // get requested row
    for ($i = 0; $i <= $row_number; $i++) {
        $row = $fetch_function($result);
    }
    PMA_DBI_free_result($result);

    // return requested field
    if (isset($row[$field])) {
        $value = $row[$field];
    }
    unset($row);

    return $value;
}

/**
 * returns only the first row from the result
 *
 * <code>
 * $sql = 'SELECT * FROM `user` WHERE `id` = 123';
 * $user = PMA_DBI_fetch_single_row($sql);
 * // produces
 * // $user = array('id' => 123, 'name' => 'John Doe')
 * </code>
 *
 * @param string|mysql_result $result query or mysql result
 * @param string              $type   NUM|ASSOC|BOTH
 *                                    returned array should either numeric
 *                                    associativ or booth
 * @param resource            $link   mysql link
 *
 * @return array|boolean first row from result
 *                       or false if result is empty
 */
function PMA_DBI_fetch_single_row($result, $type = 'ASSOC', $link = null)
{
    if (is_string($result)) {
        $result = PMA_DBI_try_query($result, $link, PMA_DBI_QUERY_STORE, false);
    }

    // return null if result is empty or false
    if (! PMA_DBI_num_rows($result)) {
        return false;
    }

    switch ($type) {
    case 'NUM' :
        $fetch_function = 'PMA_DBI_fetch_row';
        break;
    case 'ASSOC' :
        $fetch_function = 'PMA_DBI_fetch_assoc';
        break;
    case 'BOTH' :
    default :
        $fetch_function = 'PMA_DBI_fetch_array';
        break;
    }

    $row = $fetch_function($result);
    PMA_DBI_free_result($result);
    return $row;
}

/**
 * returns all rows in the resultset in one array
 *
 * <code>
 * $sql = 'SELECT * FROM `user`';
 * $users = PMA_DBI_fetch_result($sql);
 * // produces
 * // $users[] = array('id' => 123, 'name' => 'John Doe')
 *
 * $sql = 'SELECT `id`, `name` FROM `user`';
 * $users = PMA_DBI_fetch_result($sql, 'id');
 * // produces
 * // $users['123'] = array('id' => 123, 'name' => 'John Doe')
 *
 * $sql = 'SELECT `id`, `name` FROM `user`';
 * $users = PMA_DBI_fetch_result($sql, 0);
 * // produces
 * // $users['123'] = array(0 => 123, 1 => 'John Doe')
 *
 * $sql = 'SELECT `id`, `name` FROM `user`';
 * $users = PMA_DBI_fetch_result($sql, 'id', 'name');
 * // or
 * $users = PMA_DBI_fetch_result($sql, 0, 1);
 * // produces
 * // $users['123'] = 'John Doe'
 *
 * $sql = 'SELECT `name` FROM `user`';
 * $users = PMA_DBI_fetch_result($sql);
 * // produces
 * // $users[] = 'John Doe'
 *
 * $sql = 'SELECT `group`, `name` FROM `user`'
 * $users = PMA_DBI_fetch_result($sql, array('group', null), 'name');
 * // produces
 * // $users['admin'][] = 'John Doe'
 *
 * $sql = 'SELECT `group`, `name` FROM `user`'
 * $users = PMA_DBI_fetch_result($sql, array('group', 'name'), 'id');
 * // produces
 * // $users['admin']['John Doe'] = '123'
 * </code>
 *
 * @param string|mysql_result $result  query or mysql result
 * @param string|integer      $key     field-name or offset
 *                                     used as key for array
 * @param string|integer      $value   value-name or offset
 *                                     used as value for array
 * @param resource            $link    mysql link
 * @param mixed               $options query options
 *
 * @return array resultrows or values indexed by $key
 */
function PMA_DBI_fetch_result($result, $key = null, $value = null,
    $link = null, $options = 0
) {
    $resultrows = array();

    if (is_string($result)) {
        $result = PMA_DBI_try_query($result, $link, $options, false);
    }

    // return empty array if result is empty or false
    if (! $result) {
        return $resultrows;
    }

    $fetch_function = 'PMA_DBI_fetch_assoc';

    // no nested array if only one field is in result
    if (null === $key && 1 === PMA_DBI_num_fields($result)) {
        $value = 0;
        $fetch_function = 'PMA_DBI_fetch_row';
    }

    // if $key is an integer use non associative mysql fetch function
    if (is_int($key)) {
        $fetch_function = 'PMA_DBI_fetch_row';
    }

    if (null === $key && null === $value) {
        while ($row = $fetch_function($result)) {
            $resultrows[] = $row;
        }
    } elseif (null === $key) {
        while ($row = $fetch_function($result)) {
            $resultrows[] = $row[$value];
        }
    } elseif (null === $value) {
        if (is_array($key)) {
            while ($row = $fetch_function($result)) {
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
                $result_target = $row;
            }
        } else {
            while ($row = $fetch_function($result)) {
                $resultrows[$row[$key]] = $row;
            }
        }
    } else {
        if (is_array($key)) {
            while ($row = $fetch_function($result)) {
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
                $result_target = $row[$value];
            }
        } else {
            while ($row = $fetch_function($result)) {
                $resultrows[$row[$key]] = $row[$value];
            }
        }
    }

    PMA_DBI_free_result($result);
    return $resultrows;
}

/**
 * Get supported SQL compatibility modes
 *
 * @return array supported SQL compatibility modes
 */
function PMA_DBI_getCompatibilities()
{
    // Drizzle doesn't support compatibility modes
    if (PMA_DRIZZLE) {
        return array();
    }

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
 * @param resource $link mysql link resource
 *
 * @return array warnings
 */
function PMA_DBI_get_warnings($link = null)
{
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return array();
        }
    }

    return PMA_DBI_fetch_result('SHOW WARNINGS', null, null, $link);
}

/**
 * returns true (int > 0) if current user is superuser
 * otherwise 0
 *
 * @return bool Whether use is a superuser
 */
function PMA_isSuperuser()
{
    if (PMA_Util::cacheExists('is_superuser', true)) {
        return PMA_Util::cacheGet('is_superuser', true);
    }

    // when connection failed we don't have a $userlink
    if (isset($GLOBALS['userlink'])) {
        if (PMA_DRIZZLE) {
            // Drizzle has no authorization by default, so when no plugin is
            // enabled everyone is a superuser
            // Known authorization libraries: regex_policy, simple_user_policy
            // Plugins limit object visibility (dbs, tables, processes), we can
            // safely assume we always deal with superuser
            $result = true;
        } else {
            // check access to mysql.user table
            $result = (bool) PMA_DBI_try_query(
                'SELECT COUNT(*) FROM mysql.user',
                $GLOBALS['userlink'],
                PMA_DBI_QUERY_STORE
            );
        }
        PMA_Util::cacheSet('is_superuser', $result, true);
    } else {
        PMA_Util::cacheSet('is_superuser', false, true);
    }

    return PMA_Util::cacheGet('is_superuser', true);
}

/**
 * returns an array of PROCEDURE or FUNCTION names for a db
 *
 * @param string   $db    db name
 * @param string   $which PROCEDURE | FUNCTION
 * @param resource $link  mysql link
 *
 * @return array the procedure names or function names
 */
function PMA_DBI_get_procedures_or_functions($db, $which, $link = null)
{
    if (PMA_DRIZZLE) {
        // Drizzle doesn't support functions and procedures
        return array();
    }
    $shows = PMA_DBI_fetch_result('SHOW ' . $which . ' STATUS;', null, null, $link);
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
 * @param string   $db    db name
 * @param string   $which PROCEDURE | FUNCTION | EVENT | VIEW
 * @param string   $name  the procedure|function|event|view name
 * @param resource $link  mysql link
 *
 * @return string the definition
 */
function PMA_DBI_get_definition($db, $which, $name, $link = null)
{
    $returned_field = array(
        'PROCEDURE' => 'Create Procedure',
        'FUNCTION'  => 'Create Function',
        'EVENT'     => 'Create Event',
        'VIEW'      => 'Create View'
    );
    $query = 'SHOW CREATE ' . $which . ' '
        . PMA_Util::backquote($db) . '.'
        . PMA_Util::backquote($name);
    return(PMA_DBI_fetch_value($query, 0, $returned_field[$which]));
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
function PMA_DBI_get_triggers($db, $table = '', $delimiter = '//')
{
    if (PMA_DRIZZLE) {
        // Drizzle doesn't support triggers
        return array();
    }

    $result = array();
    if (! $GLOBALS['cfg']['Server']['DisableIS']) {
        // Note: in http://dev.mysql.com/doc/refman/5.0/en/faqs-triggers.html
        // their example uses WHERE TRIGGER_SCHEMA='dbname' so let's use this
        // instead of WHERE EVENT_OBJECT_SCHEMA='dbname'
        $query = 'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION'
            . ', EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT'
            . ', EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER'
            . ' FROM information_schema.TRIGGERS'
            . ' WHERE TRIGGER_SCHEMA= \'' . PMA_Util::sqlAddSlashes($db) . '\'';

        if (! empty($table)) {
            $query .= " AND EVENT_OBJECT_TABLE = '"
                . PMA_Util::sqlAddSlashes($table) . "';";
        }
    } else {
        $query = "SHOW TRIGGERS FROM " . PMA_Util::backquote($db);
        if (! empty($table)) {
            $query .= " LIKE '" . PMA_Util::sqlAddSlashes($table, true) . "';";
        }
    }

    if ($triggers = PMA_DBI_fetch_result($query)) {
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
            $one_result['full_trigger_name'] = PMA_Util::backquote(
                $trigger['TRIGGER_NAME']
            );
            $one_result['drop'] = 'DROP TRIGGER IF EXISTS '
                . $one_result['full_trigger_name'];
            $one_result['create'] = 'CREATE TRIGGER '
                . $one_result['full_trigger_name'] . ' '
                . $trigger['ACTION_TIMING']. ' '
                . $trigger['EVENT_MANIPULATION']
                . ' ON ' . PMA_Util::backquote($trigger['EVENT_OBJECT_TABLE'])
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
function PMA_DBI_formatError($error_number, $error_message)
{
    if (! empty($error_message)) {
        $error_message = PMA_DBI_convert_message($error_message);
    }

    $error_message = htmlspecialchars($error_message);

    $error = '#' . ((string) $error_number);

    if ($error_number == 2002) {
        $error .= ' - ' . $error_message;
        $error .= '<br />';
        $error .= __(
            'The server is not responding (or the local server\'s socket'
            . ' is not correctly configured).'
        );
    } elseif ($error_number == 2003) {
        $error .= ' - ' . $error_message;
        $error .= '<br />' . __('The server is not responding.');
    } elseif ($error_number == 1005) {
        if (strpos($error_message, 'errno: 13') !== false) {
            $error .= ' - ' . $error_message;
            $error .= '<br />'
                . __('Please check privileges of directory containing database.');
        } else {
            /* InnoDB contraints, see
             * http://dev.mysql.com/doc/refman/5.0/en/
             *  innodb-foreign-key-constraints.html
             */
            $error .= ' - ' . $error_message .
                ' (<a href="server_engines.php' .
                PMA_generate_common_url(
                    array('engine' => 'InnoDB', 'page' => 'Status')
                ) . '">' . __('Details…') . '</a>)';
        }
    } else {
        $error .= ' - ' . $error_message;
    }

    return $error;
}

/**
 * Checks whether given schema is a system schema: information_schema
 * (MySQL and Drizzle) or data_dictionary (Drizzle)
 *
 * @param string $schema_name           Name of schema (database) to test
 * @param bool   $test_for_mysql_schema Whether 'mysql' schema should
 *                                      be treated the same as IS and DD
 *
 * @return bool
 */
function PMA_is_system_schema($schema_name, $test_for_mysql_schema = false)
{
    return strtolower($schema_name) == 'information_schema'
            || (!PMA_DRIZZLE && strtolower($schema_name) == 'performance_schema')
            || (PMA_DRIZZLE && strtolower($schema_name) == 'data_dictionary')
            || ($test_for_mysql_schema && !PMA_DRIZZLE && $schema_name == 'mysql');
}

/**
 * Get regular expression which occur first inside the given sql query.
 *
 * @param Array $regex_array Comparing regular expressions.
 * @param String $query SQL query to be checked.
 *
 * @return String Matching regular expression.
 */
function PMA_getFirstOccurringRegularExpression($regex_array, $query)
{
    
    $minimum_first_occurence_index = null;
    $regex = null;
    
    for ($i = 0; $i < count($regex_array); $i++) {
        if (preg_match($regex_array[$i], $query, $matches, PREG_OFFSET_CAPTURE)) {
            
            if (is_null($minimum_first_occurence_index)
                || ($matches[0][1] < $minimum_first_occurence_index)
            ) {
                $regex = $regex_array[$i];
                $minimum_first_occurence_index = $matches[0][1];
            }
            
        }
    }
    
    return $regex;
    
}

?>
