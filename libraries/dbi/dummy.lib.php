<?php
/**
 * Fake database driver for testing purposes
 *
 * It has hardcoded results for given queries what makes easy to use it
 * in testsuite. Feel free to include other queries which your test will
 * need.
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Dummy
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Array of queries this "driver" supports
 */
$GLOBALS['dummy_queries'] = array(
    array('query' => 'SELECT 1', 'result' => array(array('1'))),
    array(
        'query' => 'SHOW STORAGE ENGINES',
        'result' => array(
            array('Engine' => 'dummy', 'Support' => 'YES', 'Comment' => 'dummy comment'),
            array('Engine' => 'dummy2', 'Support' => 'NO', 'Comment' => 'dummy2 comment'),
        )
    ),
    array(
        'query' => 'SHOW STATUS WHERE Variable_name LIKE \'Innodb\\_buffer\\_pool\\_%\' OR Variable_name = \'Innodb_page_size\';',
        'result' => array(
            array('Innodb_buffer_pool_pages_data', 0),
            array('Innodb_buffer_pool_pages_dirty', 0),
            array('Innodb_buffer_pool_pages_flushed', 0),
            array('Innodb_buffer_pool_pages_free', 0),
            array('Innodb_buffer_pool_pages_misc', 0),
            array('Innodb_buffer_pool_pages_total', 4096),
            array('Innodb_buffer_pool_read_ahead_rnd', 0),
            array('Innodb_buffer_pool_read_ahead', 0),
            array('Innodb_buffer_pool_read_ahead_evicted', 0),
            array('Innodb_buffer_pool_read_requests', 64),
            array('Innodb_buffer_pool_reads', 32),
            array('Innodb_buffer_pool_wait_free', 0),
            array('Innodb_buffer_pool_write_requests', 64),
            array('Innodb_page_size', 16384),
        )
    ),
    array(
        'query' => 'SHOW INNODB STATUS;',
        'result' => false,
    ),
    array(
        'query' => 'SELECT @@innodb_version;',
        'result' => array(
            array('1.1.8'),
        )
    ),
    array(
        'query' => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_per_table\';',
        'result' => array(
            array('innodb_file_per_table', 'OFF'),
        )
    ),
    array(
        'query' => 'SHOW GLOBAL VARIABLES LIKE \'innodb_file_format\';',
        'result' => array(
            array('innodb_file_format', 'Antelope'),
        )
    ),
    array(
        'query' => 'SHOW VARIABLES LIKE \'collation_server\'',
        'result' => array(
            array('collation_server', 'utf8_general_ci'),
        )
    ),
    array(
        'query' => 'SHOW VARIABLES LIKE \'language\';',
        'result' => array(),
    ),
    array(
        'query' => 'SHOW TABLES FROM `pma_test`;',
        'result' => array(
            array('table1'),
            array('table2'),
        )
    ),
    array(
        'query' => 'SHOW TABLES FROM `pmadb`',
        'result' => array(
            array('column_info'),
        )
    ),
    array(
        'query' => 'SHOW COLUMNS FROM `pma_test`.`table1`',
        'columns' => array(
            'Field', 'Type', 'Null', 'Key', 'Default', 'Extra'
        ),
        'result' => array(
            array('i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'),
            array('o', 'int(11)', 'NO', 'MUL', 'NULL', ''),
        )
    ),
    array(
        'query' => 'SHOW COLUMNS FROM `pma_test`.`table2`',
        'columns' => array(
            'Field', 'Type', 'Null', 'Key', 'Default', 'Extra'
        ),
        'result' => array(
            array('i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment'),
            array('o', 'int(11)', 'NO', 'MUL', 'NULL', ''),
        )
    ),
    array(
        'query' => 'SHOW INDEXES FROM `pma_test`.`table1`',
        'result' => array(),
    ),
    array(
        'query' => 'SHOW INDEXES FROM `pma_test`.`table2`',
        'result' => array(),
    ),
    array(
        'query' => 'SHOW COLUMNS FROM `pma`.`table1`',
        'columns' => array(
            'Field', 'Type', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'
        ),
        'result' => array(
            array('i', 'int(11)', 'NO', 'PRI', 'NULL', 'auto_increment', 'select,insert,update,references', ''),
            array('o', 'varchar(100)', 'NO', 'MUL', 'NULL', '', 'select,insert,update,references', ''),
        )
    ),
    array(
        'query' => 'SELECT * FROM information_schema.CHARACTER_SETS',
        'columns' => array('CHARACTER_SET_NAME', 'DEFAULT_COLLATE_NAME', 'DESCRIPTION', 'MAXLEN'),
        'result' => array(
            array('utf8', 'utf8_general_ci', 'UTF-8 Unicode', 3),
        )
    ),
    array(
        'query' => 'SELECT * FROM information_schema.COLLATIONS',
        'columns' => array(
            'COLLATION_NAME', 'CHARACTER_SET_NAME', 'ID', 'IS_DEFAULT', 'IS_COMPILED', 'SORTLEN'
        ),
        'result' => array(
            array('utf8_general_ci', 'utf8', 33, 'Yes', 'Yes', 1),
            array('utf8_bin', 'utf8', 83, '', 'Yes', 1),
        )
    ),
    array(
        'query' => 'SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=\'pma_test\' AND `TABLE_TYPE`=\'BASE TABLE\'',
        'result' => array(),
    ),
    array(
        'query' => 'SELECT upper(plugin_name) f FROM data_dictionary.plugins WHERE plugin_name IN (\'MYSQL_PASSWORD\',\'ROT13\') AND plugin_type = \'Function\' AND is_active',
        'columns' => array('f'),
        'result' => array(array('ROT13')),
    ),
    array(
        'query' => 'SELECT `column_name`, `mimetype`, `transformation`, `transformation_options` FROM `pmadb`.`column_info` WHERE `db_name` = \'pma_test\' AND `table_name` = \'table1\' AND ( `mimetype` != \'\' OR `transformation` != \'\' OR `transformation_options` != \'\')',
        'columns' => array('column_name', 'mimetype', 'transformation', 'transformation_options'),
        'result' => array(
            array('o', 'text/plain', 'sql'),
        )
    ),
);

/**
 * Current database.
 */
$GLOBALS['dummy_db'] = '';

/* Some basic setup for dummy driver */
$GLOBALS['userlink'] = 1;
$GLOBALS['controllink'] = 2;
$GLOBALS['cfg']['DBG']['sql'] = false;
if (! defined('PMA_DRIZZLE')) {
    define('PMA_DRIZZLE', 0);
}


/**
 * Run the multi query and output the results
 *
 * @param mysqli $link  mysqli object
 * @param string $query multi query statement to execute
 *
 * @return boolean false always false since mysql extention not support
 *                       for multi query executions
 */
function PMA_DBI_real_multi_query($link, $query)
{
    return false;
}

/**
 * connects to the database server
 *
 * @param string $user                 mysql user name
 * @param string $password             mysql user password
 * @param bool   $is_controluser       whether this is a control user connection
 * @param array  $server               host/port/socket/persistent
 * @param bool   $auxiliary_connection (when true, don't go back to login if
 *                                     connection fails)
 *
 * @return mixed false on error or a mysqli object on success
 */
function PMA_DBI_connect(
    $user, $password, $is_controluser = false, $server = null,
    $auxiliary_connection = false
) {
    return true;
}

/**
 * selects given database
 *
 * @param string   $dbname name of db to select
 * @param resource $link   mysql link resource
 *
 * @return bool
 */
function PMA_DBI_select_db($dbname, $link = null)
{
    $GLOBALS['dummy_db'] = $dbname;
    return true;
}

/**
 * runs a query and returns the result
 *
 * @param string   $query   query to run
 * @param resource $link    mysql link resource
 * @param int      $options query options
 *
 * @return mixed
 */
function PMA_DBI_real_query($query, $link = null, $options = 0)
{
    $query = trim(preg_replace('/  */', ' ', str_replace("\n", ' ', $query)));
    for ($i = 0; $i < count($GLOBALS['dummy_queries']); $i++) {
        if ($GLOBALS['dummy_queries'][$i]['query'] == $query) {
            $GLOBALS['dummy_queries'][$i]['pos'] = 0;
            if (is_array($GLOBALS['dummy_queries'][$i]['result'])) {
                return $i;
            } else {
                return false;
            }
        }
    }
    echo "Not supported query: $query\n";
    return false;
}

/**
 * returns result data from $result
 *
 * @param resource $result result  MySQL result
 *
 * @return array
 */
function PMA_DBI_fetch_any($result)
{
    $query_data = $GLOBALS['dummy_queries'][$result];
    if ($query_data['pos'] >= count($query_data['result'])) {
        return false;
    }
    $ret = $query_data['result'][$query_data['pos']];
    $GLOBALS['dummy_queries'][$result]['pos'] += 1;
    return $ret;
}

/**
 * returns array of rows with associative and numeric keys from $result
 *
 * @param resource $result result  MySQL result
 *
 * @return array
 */
function PMA_DBI_fetch_array($result)
{
    $data = PMA_DBI_fetch_any($result);
    if (is_array($data) && isset($GLOBALS['dummy_queries'][$result]['columns'])) {
        foreach ($data as $key => $val) {
            $data[$GLOBALS['dummy_queries'][$result]['columns'][$key]] = $val;
        }
        return $data;
    }
    return $data;
}

/**
 * returns array of rows with associative keys from $result
 *
 * @param resource $result MySQL result
 *
 * @return array
 */
function PMA_DBI_fetch_assoc($result)
{
    $data = PMA_DBI_fetch_any($result);
    if (is_array($data) && isset($GLOBALS['dummy_queries'][$result]['columns'])) {
        $ret = array();
        foreach ($data as $key => $val) {
            $ret[$GLOBALS['dummy_queries'][$result]['columns'][$key]] = $val;
        }
        return $ret;
    }
    return $data;
}

/**
 * returns array of rows with numeric keys from $result
 *
 * @param resource $result MySQL result
 *
 * @return array
 */
function PMA_DBI_fetch_row($result)
{
    $data = PMA_DBI_fetch_any($result);
    return $data;
}

/**
 * Adjusts the result pointer to an arbitrary row in the result
 *
 * @param resource $result database result
 * @param integer  $offset offset to seek
 *
 * @return bool true on success, false on failure
 */
function PMA_DBI_data_seek($result, $offset)
{
    if ($offset > count($GLOBALS['dummy_queries'][$i]['result'])) {
        return false;
    }
    $GLOBALS['dummy_queries'][$i]['pos'] = $offset;
    return true;
}

/**
 * Frees memory associated with the result
 *
 * @param resource $result database result
 */
function PMA_DBI_free_result($result)
{
    return;
}

/**
 * Check if there are any more query results from a multi query
 *
 * @return bool false
 */
function PMA_DBI_more_results()
{
    return false;
}

/**
 * Prepare next result from multi_query
 *
 * @return boo false
 */
function PMA_DBI_next_result()
{
    return false;
}

/**
 * returns the number of rows returned by last query
 *
 * @param resource $result MySQL result
 *
 * @return string|int
 */
function PMA_DBI_num_rows($result)
{
    if (!is_bool($result)) {
        return count($GLOBALS['dummy_queries'][$result]['result']);
    } else {
        return 0;
    }
}

/**
 * returns the number of rows affected by last query
 *
 * @param resource $link           the mysql object
 * @param bool     $get_from_cache whether to retrieve from cache
 *
 * @return string|int
 */
function PMA_DBI_affected_rows($link = null, $get_from_cache = true)
{
    return 0;
}

/**
 * return number of fields in given $result
 *
 * @param resource $result MySQL result
 *
 * @return int  field count
 */
function PMA_DBI_num_fields($result)
{
    if (isset($GLOBALS['dummy_queries'][$result]['columns'])) {
        return count($GLOBALS['dummy_queries'][$result]['columns']);
    } else {
        return 0;
    }
}

/**
 * returns last error message or false if no errors occured
 *
 * @param resource $link mysql link
 *
 * @return string|bool $error or false
 */
function PMA_DBI_getError($link = null)
{
    return false;
}
