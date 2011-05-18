<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays status variables with descriptions and some hints an optmizing
 *  + reset status variables
 *
 * @package phpMyAdmin
 */

/**
 * no need for variables importing
 * @ignore
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}
require_once './libraries/common.inc.php';

/**
 * Chart generation
 */
require_once './libraries/chart.lib.php';

/** 
 * Ajax request
 */
if (isset($_REQUEST["query_chart_ajax"])) {
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1977 05:00:00 GMT"); // Date in the past
	
	exit(createQueryChart());
}

/**
 * JS Includes
 */
 
$GLOBALS['js_include'][] = 'pMap.js';
$GLOBALS['js_include'][] = 'server_status.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';

/**
 * Does the common work
 */
require './libraries/server_common.inc.php';


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';

/**
 * Replication library
 */
require './libraries/replication.inc.php';
require_once './libraries/replication_gui.lib.php';

/**
 * Messages are built using the message name
 */
$strShowStatusBinlog_cache_disk_useDescr = __('The number of transactions that used the temporary binary log cache but that exceeded the value of binlog_cache_size and used a temporary file to store statements from the transaction.');
$strShowStatusBinlog_cache_useDescr = __('The number of transactions that used the temporary binary log cache.');
$strShowStatusCreated_tmp_disk_tablesDescr = __('The number of temporary tables on disk created automatically by the server while executing statements. If Created_tmp_disk_tables is big, you may want to increase the tmp_table_size  value to cause temporary tables to be memory-based instead of disk-based.');
$strShowStatusCreated_tmp_filesDescr = __('How many temporary files mysqld has created.');
$strShowStatusCreated_tmp_tablesDescr = __('The number of in-memory temporary tables created automatically by the server while executing statements.');
$strShowStatusDelayed_errorsDescr = __('The number of rows written with INSERT DELAYED for which some error occurred (probably duplicate key).');
$strShowStatusDelayed_insert_threadsDescr = __('The number of INSERT DELAYED handler threads in use. Every different table on which one uses INSERT DELAYED gets its own thread.');
$strShowStatusDelayed_writesDescr = __('The number of INSERT DELAYED rows written.');
$strShowStatusFlush_commandsDescr  = __('The number of executed FLUSH statements.');
$strShowStatusHandler_commitDescr = __('The number of internal COMMIT statements.');
$strShowStatusHandler_deleteDescr = __('The number of times a row was deleted from a table.');
$strShowStatusHandler_discoverDescr = __('The MySQL server can ask the NDB Cluster storage engine if it knows about a table with a given name. This is called discovery. Handler_discover indicates the number of time tables have been discovered.');
$strShowStatusHandler_read_firstDescr = __('The number of times the first entry was read from an index. If this is high, it suggests that the server is doing a lot of full index scans; for example, SELECT col1 FROM foo, assuming that col1 is indexed.');
$strShowStatusHandler_read_keyDescr = __('The number of requests to read a row based on a key. If this is high, it is a good indication that your queries and tables are properly indexed.');
$strShowStatusHandler_read_nextDescr = __('The number of requests to read the next row in key order. This is incremented if you are querying an index column with a range constraint or if you are doing an index scan.');
$strShowStatusHandler_read_prevDescr = __('The number of requests to read the previous row in key order. This read method is mainly used to optimize ORDER BY ... DESC.');
$strShowStatusHandler_read_rndDescr = __('The number of requests to read a row based on a fixed position. This is high if you are doing a lot of queries that require sorting of the result. You probably have a lot of queries that require MySQL to scan whole tables or you have joins that don\'t use keys properly.');
$strShowStatusHandler_read_rnd_nextDescr = __('The number of requests to read the next row in the data file. This is high if you are doing a lot of table scans. Generally this suggests that your tables are not properly indexed or that your queries are not written to take advantage of the indexes you have.');
$strShowStatusHandler_rollbackDescr = __('The number of internal ROLLBACK statements.');
$strShowStatusHandler_updateDescr = __('The number of requests to update a row in a table.');
$strShowStatusHandler_writeDescr = __('The number of requests to insert a row in a table.');
$strShowStatusInnodb_buffer_pool_pages_dataDescr = __('The number of pages containing data (dirty or clean).');
$strShowStatusInnodb_buffer_pool_pages_dirtyDescr = __('The number of pages currently dirty.');
$strShowStatusInnodb_buffer_pool_pages_flushedDescr = __('The number of buffer pool pages that have been requested to be flushed.');
$strShowStatusInnodb_buffer_pool_pages_freeDescr = __('The number of free pages.');
$strShowStatusInnodb_buffer_pool_pages_latchedDescr = __('The number of latched pages in InnoDB buffer pool. These are pages currently being read or written or that can\'t be flushed or removed for some other reason.');
$strShowStatusInnodb_buffer_pool_pages_miscDescr = __('The number of pages busy because they have been allocated for administrative overhead such as row locks or the adaptive hash index. This value can also be calculated as Innodb_buffer_pool_pages_total - Innodb_buffer_pool_pages_free - Innodb_buffer_pool_pages_data.');
$strShowStatusInnodb_buffer_pool_pages_totalDescr = __('Total size of buffer pool, in pages.');
$strShowStatusInnodb_buffer_pool_read_ahead_rndDescr = __('The number of "random" read-aheads InnoDB initiated. This happens when a query is to scan a large portion of a table but in random order.');
$strShowStatusInnodb_buffer_pool_read_ahead_seqDescr = __('The number of sequential read-aheads InnoDB initiated. This happens when InnoDB does a sequential full table scan.');
$strShowStatusInnodb_buffer_pool_read_requestsDescr = __('The number of logical read requests InnoDB has done.');
$strShowStatusInnodb_buffer_pool_readsDescr = __('The number of logical reads that InnoDB could not satisfy from buffer pool and had to do a single-page read.');
$strShowStatusInnodb_buffer_pool_wait_freeDescr = __('Normally, writes to the InnoDB buffer pool happen in the background. However, if it\'s necessary to read or create a page and no clean pages are available, it\'s necessary to wait for pages to be flushed first. This counter counts instances of these waits. If the buffer pool size was set properly, this value should be small.');
$strShowStatusInnodb_buffer_pool_write_requestsDescr = __('The number writes done to the InnoDB buffer pool.');
$strShowStatusInnodb_data_fsyncsDescr = __('The number of fsync() operations so far.');
$strShowStatusInnodb_data_pending_fsyncsDescr = __('The current number of pending fsync() operations.');
$strShowStatusInnodb_data_pending_readsDescr = __('The current number of pending reads.');
$strShowStatusInnodb_data_pending_writesDescr = __('The current number of pending writes.');
$strShowStatusInnodb_data_readDescr = __('The amount of data read so far, in bytes.');
$strShowStatusInnodb_data_readsDescr = __('The total number of data reads.');
$strShowStatusInnodb_data_writesDescr = __('The total number of data writes.');
$strShowStatusInnodb_data_writtenDescr = __('The amount of data written so far, in bytes.');
$strShowStatusInnodb_dblwr_pages_writtenDescr = __('The number of pages that have been written for doublewrite operations.');
$strShowStatusInnodb_dblwr_writesDescr = __('The number of doublewrite operations that have been performed.');
$strShowStatusInnodb_log_waitsDescr = __('The number of waits we had because log buffer was too small and we had to wait for it to be flushed before continuing.');
$strShowStatusInnodb_log_write_requestsDescr = __('The number of log write requests.');
$strShowStatusInnodb_log_writesDescr = __('The number of physical writes to the log file.');
$strShowStatusInnodb_os_log_fsyncsDescr = __('The number of fsync() writes done to the log file.');
$strShowStatusInnodb_os_log_pending_fsyncsDescr = __('The number of pending log file fsyncs.');
$strShowStatusInnodb_os_log_pending_writesDescr = __('Pending log file writes.');
$strShowStatusInnodb_os_log_writtenDescr = __('The number of bytes written to the log file.');
$strShowStatusInnodb_pages_createdDescr = __('The number of pages created.');
$strShowStatusInnodb_page_sizeDescr = __('The compiled-in InnoDB page size (default 16KB). Many values are counted in pages; the page size allows them to be easily converted to bytes.');
$strShowStatusInnodb_pages_readDescr = __('The number of pages read.');
$strShowStatusInnodb_pages_writtenDescr = __('The number of pages written.');
$strShowStatusInnodb_row_lock_current_waitsDescr = __('The number of row locks currently being waited for.');
$strShowStatusInnodb_row_lock_time_avgDescr = __('The average time to acquire a row lock, in milliseconds.');
$strShowStatusInnodb_row_lock_timeDescr = __('The total time spent in acquiring row locks, in milliseconds.');
$strShowStatusInnodb_row_lock_time_maxDescr = __('The maximum time to acquire a row lock, in milliseconds.');
$strShowStatusInnodb_row_lock_waitsDescr = __('The number of times a row lock had to be waited for.');
$strShowStatusInnodb_rows_deletedDescr = __('The number of rows deleted from InnoDB tables.');
$strShowStatusInnodb_rows_insertedDescr = __('The number of rows inserted in InnoDB tables.');
$strShowStatusInnodb_rows_readDescr = __('The number of rows read from InnoDB tables.');
$strShowStatusInnodb_rows_updatedDescr = __('The number of rows updated in InnoDB tables.');
$strShowStatusKey_blocks_not_flushedDescr = __('The number of key blocks in the key cache that have changed but haven\'t yet been flushed to disk. It used to be known as Not_flushed_key_blocks.');
$strShowStatusKey_blocks_unusedDescr = __('The number of unused blocks in the key cache. You can use this value to determine how much of the key cache is in use.');
$strShowStatusKey_blocks_usedDescr = __('The number of used blocks in the key cache. This value is a high-water mark that indicates the maximum number of blocks that have ever been in use at one time.');
$strShowStatusKey_read_requestsDescr = __('The number of requests to read a key block from the cache.');
$strShowStatusKey_readsDescr = __('The number of physical reads of a key block from disk. If Key_reads is big, then your key_buffer_size value is probably too small. The cache miss rate can be calculated as Key_reads/Key_read_requests.');
$strShowStatusKey_write_requestsDescr = __('The number of requests to write a key block to the cache.');
$strShowStatusKey_writesDescr = __('The number of physical writes of a key block to disk.');
$strShowStatusLast_query_costDescr = __('The total cost of the last compiled query as computed by the query optimizer. Useful for comparing the cost of different query plans for the same query. The default value of 0 means that no query has been compiled yet.');
$strShowStatusNot_flushed_delayed_rowsDescr = __('The number of rows waiting to be written in INSERT DELAYED queues.');
$strShowStatusOpened_tablesDescr = __('The number of tables that have been opened. If opened tables is big, your table cache value is probably too small.');
$strShowStatusOpen_filesDescr = __('The number of files that are open.');
$strShowStatusOpen_streamsDescr = __('The number of streams that are open (used mainly for logging).');
$strShowStatusOpen_tablesDescr = __('The number of tables that are open.');
$strShowStatusQcache_free_blocksDescr = __('The number of free memory blocks in query cache.');
$strShowStatusQcache_free_memoryDescr = __('The amount of free memory for query cache.');
$strShowStatusQcache_hitsDescr = __('The number of cache hits.');
$strShowStatusQcache_insertsDescr = __('The number of queries added to the cache.');
$strShowStatusQcache_lowmem_prunesDescr = __('The number of queries that have been removed from the cache to free up memory for caching new queries. This information can help you tune the query cache size. The query cache uses a least recently used (LRU) strategy to decide which queries to remove from the cache.');
$strShowStatusQcache_not_cachedDescr = __('The number of non-cached queries (not cachable, or not cached due to the query_cache_type setting).');
$strShowStatusQcache_queries_in_cacheDescr = __('The number of queries registered in the cache.');
$strShowStatusQcache_total_blocksDescr = __('The total number of blocks in the query cache.');
$strShowStatusReset = _pgettext('$strShowStatusReset', 'Reset');
$strShowStatusRpl_statusDescr = __('The status of failsafe replication (not yet implemented).');
$strShowStatusSelect_full_joinDescr = __('The number of joins that do not use indexes. If this value is not 0, you should carefully check the indexes of your tables.');
$strShowStatusSelect_full_range_joinDescr = __('The number of joins that used a range search on a reference table.');
$strShowStatusSelect_range_checkDescr = __('The number of joins without keys that check for key usage after each row. (If this is not 0, you should carefully check the indexes of your tables.)');
$strShowStatusSelect_rangeDescr = __('The number of joins that used ranges on the first table. (It\'s normally not critical even if this is big.)');
$strShowStatusSelect_scanDescr = __('The number of joins that did a full scan of the first table.');
$strShowStatusSlave_open_temp_tablesDescr = __('The number of temporary tables currently open by the slave SQL thread.');
$strShowStatusSlave_retried_transactionsDescr = __('Total (since startup) number of times the replication slave SQL thread has retried transactions.');
$strShowStatusSlave_runningDescr = __('This is ON if this server is a slave that is connected to a master.');
$strShowStatusSlow_launch_threadsDescr = __('The number of threads that have taken more than slow_launch_time seconds to create.');
$strShowStatusSlow_queriesDescr = __('The number of queries that have taken more than long_query_time seconds.');
$strShowStatusSort_merge_passesDescr = __('The number of merge passes the sort algorithm has had to do. If this value is large, you should consider increasing the value of the sort_buffer_size system variable.');
$strShowStatusSort_rangeDescr = __('The number of sorts that were done with ranges.');
$strShowStatusSort_rowsDescr = __('The number of sorted rows.');
$strShowStatusSort_scanDescr = __('The number of sorts that were done by scanning the table.');
$strShowStatusTable_locks_immediateDescr = __('The number of times that a table lock was acquired immediately.');
$strShowStatusTable_locks_waitedDescr = __('The number of times that a table lock could not be acquired immediately and a wait was needed. If this is high, and you have performance problems, you should first optimize your queries, and then either split your table or tables or use replication.');
$strShowStatusThreads_cachedDescr = __('The number of threads in the thread cache. The cache hit rate can be calculated as Threads_created/Connections. If this value is red you should raise your thread_cache_size.');
$strShowStatusThreads_connectedDescr = __('The number of currently open connections.');
$strShowStatusThreads_createdDescr = __('The number of threads created to handle connections. If Threads_created is big, you may want to increase the thread_cache_size value. (Normally this doesn\'t give a notable performance improvement if you have a good thread implementation.)');
$strShowStatusThreads_runningDescr = __('The number of threads that are not sleeping.');

/**
 * Displays the sub-page heading
 */
echo '<div id="serverstatus">' . "\n";
echo '<h2>' . "\n"
   . ($GLOBALS['cfg']['MainPageIconic']
       ? '<img class="icon" src="' . $GLOBALS['pmaThemeImage'] .
         's_status.png" width="16" height="16" alt="" />'
       : '')
   . __('Runtime Information') . "\n"
   . '</h2>' . "\n";


/**
 * flush status variables if requested
 */
if (isset($_REQUEST['flush'])) {
    $_flush_commands = array(
        'STATUS',
        'TABLES',
        'QUERY CACHE',
    );

    if (in_array($_REQUEST['flush'], $_flush_commands)) {
        PMA_DBI_query('FLUSH ' . $_REQUEST['flush'] . ';');
    }
    unset($_flush_commands);
}


/**
 * get status from server
 */
$server_status = PMA_DBI_fetch_result('SHOW GLOBAL STATUS', 0, 1);

/**
 * for some calculations we require also some server settings
 */
$server_variables = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES', 0, 1);

/**
 * starttime calculation
 */
$start_time = PMA_DBI_fetch_value(
    'SELECT UNIX_TIMESTAMP() - ' . $server_status['Uptime']);


/**
 * cleanup some deprecated values
 */
$deprecated = array(
    'Com_prepare_sql' => 'Com_stmt_prepare',
    'Com_execute_sql' => 'Com_stmt_execute',
    'Com_dealloc_sql' => 'Com_stmt_close',
);

foreach ($deprecated as $old => $new) {
    if (isset($server_status[$old])
      && isset($server_status[$new])) {
        unset($server_status[$old]);
    }
}
unset($deprecated);


/**
 * calculate some values
 */
// Key_buffer_fraction
if (isset($server_status['Key_blocks_unused'])
  && isset($server_variables['key_cache_block_size'])
  && isset($server_variables['key_buffer_size'])) {
    $server_status['Key_buffer_fraction_%'] =
        100
      - $server_status['Key_blocks_unused']
      * $server_variables['key_cache_block_size']
      / $server_variables['key_buffer_size']
      * 100;
} elseif (
     isset($server_status['Key_blocks_used'])
  && isset($server_variables['key_buffer_size'])) {
    $server_status['Key_buffer_fraction_%'] =
        $server_status['Key_blocks_used']
      * 1024
      / $server_variables['key_buffer_size'];
  }

// Ratio for key read/write
if (isset($server_status['Key_writes'])
    && isset($server_status['Key_write_requests'])
    && $server_status['Key_write_requests'] > 0)
        $server_status['Key_write_ratio_%'] = 100 * $server_status['Key_writes'] / $server_status['Key_write_requests'];

if (isset($server_status['Key_reads'])
    && isset($server_status['Key_read_requests'])
    && $server_status['Key_read_requests'] > 0)
        $server_status['Key_read_ratio_%'] = 100 * $server_status['Key_reads'] / $server_status['Key_read_requests'];

// Threads_cache_hitrate
if (isset($server_status['Threads_created'])
  && isset($server_status['Connections'])
  && $server_status['Connections'] > 0) {
    $server_status['Threads_cache_hitrate_%'] =
        100
      - $server_status['Threads_created']
      / $server_status['Connections']
      * 100;
}

// Format Uptime_since_flush_status : show as days, hours, minutes, seconds
if (isset($server_status['Uptime_since_flush_status'])) {
    $server_status['Uptime_since_flush_status'] = PMA_timespanFormat($server_status['Uptime_since_flush_status']);
}

/**
 * define some alerts
 */
// name => max value before alert
$alerts = array(
    // lower is better
    // variable => max value
    'Aborted_clients' => 0,
    'Aborted_connects' => 0,

    'Binlog_cache_disk_use' => 0,

    'Created_tmp_disk_tables' => 0,

    'Handler_read_rnd' => 0,
    'Handler_read_rnd_next' => 0,

    'Innodb_buffer_pool_pages_dirty' => 0,
    'Innodb_buffer_pool_reads' => 0,
    'Innodb_buffer_pool_wait_free' => 0,
    'Innodb_log_waits' => 0,
    'Innodb_row_lock_time_avg' => 10, // ms
    'Innodb_row_lock_time_max' => 50, // ms
    'Innodb_row_lock_waits' => 0,

    'Slow_queries' => 0,
    'Delayed_errors' => 0,
    'Select_full_join' => 0,
    'Select_range_check' => 0,
    'Sort_merge_passes' => 0,
    'Opened_tables' => 0,
    'Table_locks_waited' => 0,
    'Qcache_lowmem_prunes' => 0,
    'Slow_launch_threads' => 0,

    // depends on Key_read_requests
    // normaly lower then 1:0.01
    'Key_reads' => (0.01 * $server_status['Key_read_requests']),
    // depends on Key_write_requests
    // normaly nearly 1:1
    'Key_writes' => (0.9 * $server_status['Key_write_requests']),

    'Key_buffer_fraction' => 0.5,

    // alert if more than 95% of thread cache is in use
    'Threads_cached' => 0.95 * $server_variables['thread_cache_size']

    // higher is better
    // variable => min value
    //'Handler read key' => '> ',
);


/**
 * split variables in sections
 */
$allocations = array(
    // variable name => section

    'Com_'              => 'com',
   /* 'Innodb_'           => 'innodb',
    'Ndb_'              => 'ndb',
    'Handler_'          => 'handler',
    'Qcache_'           => 'qcache',
    'Threads_'          => 'threads',
    'Slow_launch_threads' => 'threads',

    'Binlog_cache_'     => 'binlog_cache',
    'Created_tmp_'      => 'created_tmp',
    'Key_'              => 'key',

    'Delayed_'          => 'delayed',
    'Not_flushed_delayed_rows' => 'delayed',*/

    'Flush_commands'    => 'query',
    'Last_query_cost'   => 'query',
    'Slow_queries'      => 'query',
    'Queries'           => 'query',
    'Prepared_stmt_count' => 'query',

   /* 'Select_'           => 'select',
    'Sort_'             => 'sort',

    'Open_tables'       => 'table',
    'Opened_tables'     => 'table',
    'Open_table_definitions' => 'table',
    'Opened_table_definitions' => 'table',
    'Table_locks_'      => 'table',

    'Rpl_status'        => 'repl',
    'Slave_'            => 'repl',

    'Tc_'               => 'tc',

    'Ssl_'              => 'ssl',

    'Open_files'        => 'files',
    'Open_streams'      => 'files',
    'Opened_files'      => 'files',*/
);

$sections = array(
    // section => section name (description)
    'com'           => array('title' => ''),
    /*'query'         => array('title' => __('SQL query')),
    'innodb'        => array('title' => 'InnoDB'),
    'ndb'           => array('title' => 'NDB'),
    'handler'       => array('title' => __('Handler')),
    'qcache'        => array('title' => __('Query cache')),
    'threads'       => array('title' => __('Threads')),
    'binlog_cache'  => array('title' => __('Binary log')),
    'created_tmp'   => array('title' => __('Temporary data')),
    'delayed'       => array('title' => __('Delayed inserts')),
    'key'           => array('title' => __('Key cache')),
    'select'        => array('title' => __('Joins')),
    'repl'          => array('title' => __('Replication')),
    'sort'          => array('title' => __('Sorting')),
    'table'         => array('title' => __('Tables')),
    'tc'            => array('title' => __('Transaction coordinator')),
    'files'         => array('title' => __('Files')),
    'ssl'           => array('title' => 'SSL'),*/
);

/**
 * define some needfull links/commands
 */
// variable or section name => (name => url)
$links = array();

$links['table'][__('Flush (close) all tables')]
    = $PMA_PHP_SELF . '?flush=TABLES&amp;' . PMA_generate_common_url();
$links['table'][__('Show open tables')]
    = 'sql.php?sql_query=' . urlencode('SHOW OPEN TABLES') .
      '&amp;goto=server_status.php&amp;' . PMA_generate_common_url();

if ($server_master_status) {
  $links['repl'][__('Show slave hosts')]
    = 'sql.php?sql_query=' . urlencode('SHOW SLAVE HOSTS') .
      '&amp;goto=server_status.php&amp;' . PMA_generate_common_url();
  $links['repl'][__('Show master status')] = '#replication_master';
}
if ($server_slave_status) {
  $links['repl'][__('Show slave status')] = '#replication_slave';
}

$links['repl']['doc'] = 'replication';

$links['qcache'][__('Flush query cache')]
    = $PMA_PHP_SELF . '?flush=' . urlencode('QUERY CACHE') . '&amp;' .
      PMA_generate_common_url();
$links['qcache']['doc'] = 'query_cache';

$links['threads'][__('Show processes')]
    = 'server_processlist.php?' . PMA_generate_common_url();
$links['threads']['doc'] = 'mysql_threads';

$links['key']['doc'] = 'myisam_key_cache';

$links['binlog_cache']['doc'] = 'binary_log';

$links['Slow_queries']['doc'] = 'slow_query_log';

$links['innodb'][__('Variables')]
    = 'server_engines.php?engine=InnoDB&amp;' . PMA_generate_common_url();
$links['innodb'][__('InnoDB Status')]
    = 'server_engines.php?engine=InnoDB&amp;page=Status&amp;' .
      PMA_generate_common_url();
$links['innodb']['doc'] = 'innodb';


// sort com_ and query_ vars into arrays
foreach ($server_status as $name => $value) {
    if (isset($allocations[$name])) {
        $sections[$allocations[$name]]['vars'][$name] = $value;
        unset($server_status[$name]);
    } else {
        foreach ($allocations as $filter => $section) {
            if (preg_match('/^' . $filter . '/', $name)
              && isset($server_status[$name])) {
                unset($server_status[$name]);
                $sections[$section]['vars'][$name] = $value;
            }
        }
    }
}

unset($name, $value, $filter, $section, $allocations);

// rest
$sections['all']['vars'] =& $server_status;

$hour_factor    = 3600 / $server_status['Uptime'];

/**
 * start output
 */
?>
<!--<div id="statuslinks">
    <a href="<?php echo
        $PMA_PHP_SELF . '?' . PMA_generate_common_url(); ?>"
       ><?php echo __('Refresh'); ?></a>
    <a href="<?php echo
        $PMA_PHP_SELF . '?flush=STATUS&amp;' . PMA_generate_common_url(); ?>"
       ><?php echo _pgettext('for Show status', 'Reset'); ?></a>
       <?php echo PMA_showMySQLDocu('server_status_variables','server_status_variables'); ?>
</div>
-->

<!--<div id="sectionlinks">
<?php
/*foreach ($sections as $section_name => $section) {
    if (! empty($section['vars']) && ! empty($section['title'])) {
        echo '<a href="' . $PMA_PHP_SELF . '?' .
             PMA_generate_common_url() . '#' . $section_name . '">' .
             $section['title'] . '</a>' . "\n";
    }
}*/
?>
</div>-->

<div id="statusTabs">
	<ul>
		<li><a href="#statusTabs1">Server traffic</a></li>
		<li><a href="#statusTabs2">Query statistics</a></li>
		<li><a href="#statusTabs3">Status variables</a></li>
	</ul>
	
	<div id="statusTabs1">
<p>
<?php
echo sprintf(__('This MySQL server has been running for %s. It started up on %s.'),
    PMA_timespanFormat($server_status['Uptime']),
    PMA_localisedDate($start_time)) . "\n";
?>
</p>

<?php
if ($server_master_status || $server_slave_status) {
    echo '<p>';
    if ($server_master_status && $server_slave_status) {
        echo __('This MySQL server works as <b>master</b> and <b>slave</b> in <b>replication</b> process.');
    } elseif ($server_master_status) {
        echo __('This MySQL server works as <b>master</b> in <b>replication</b> process.');
    } elseif ($server_slave_status) {
        echo __('This MySQL server works as <b>slave</b> in <b>replication</b> process.');
    }
    echo __('For further information about replication status on the server, please visit the <a href=#replication>replication section</a>.');
    echo '</p>';
}
?>

<h3><?php echo __('<b>Server traffic</b>: These tables show the network traffic statistics of this MySQL server since its startup.'); ?></h3>

<table id="serverstatustraffic" class="data">
<thead>
<tr>
    <th colspan="2"><?php echo __('Traffic') . '&nbsp;' . PMA_showHint(__('On a busy server, the byte counters may overrun, so those statistics as reported by the MySQL server may be incorrect.')); ?></th>
    <th>&oslash; <?php echo __('per hour'); ?></th>
</tr>
</thead>
<tbody>
<tr class="noclick odd">
    <th class="name"><?php echo __('Received'); ?></th>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown($server_status['Bytes_received'], 2, 1)); ?></td>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                $server_status['Bytes_received'] * $hour_factor, 2, 1)); ?></td>
</tr>
<tr class="noclick even">
    <th class="name"><?php echo __('Sent'); ?></th>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown($server_status['Bytes_sent'], 2, 1)); ?></td>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                $server_status['Bytes_sent'] * $hour_factor, 2, 1)); ?></td>
</tr>
<tr class="noclick odd">
    <th class="name"><?php echo __('Total'); ?></th>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                $server_status['Bytes_received'] + $server_status['Bytes_sent'], 2, 1)
        ); ?></td>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                ($server_status['Bytes_received'] + $server_status['Bytes_sent'])
                * $hour_factor, 2, 1)
        ); ?></td>
</tr>
</tbody>
</table>
	

<table id="serverstatusconnections" class="data">
<thead>
<tr>
    <th colspan="2"><?php echo __('Connections'); ?></th>
    <th>&oslash; <?php echo __('per hour'); ?></th>
    <th>%</th>
</tr>
</thead>
<tbody>
<tr class="noclick odd">
    <th class="name"><?php echo __('max. concurrent connections'); ?></th>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Max_used_connections'], 0); ?>  </td>
    <td class="value">--- </td>
    <td class="value">--- </td>
</tr>
<tr class="noclick even">
    <th class="name"><?php echo __('Failed attempts'); ?></th>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Aborted_connects'], 4, 0); ?></td>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Aborted_connects'] * $hour_factor,
            4, 2); ?></td>
    <td class="value"><?php echo
        $server_status['Connections'] > 0
      ? PMA_formatNumber(
            $server_status['Aborted_connects'] * 100 / $server_status['Connections'],
            0, 2) . '%'
      : '--- '; ?></td>
</tr>
<tr class="noclick odd">
    <th class="name"><?php echo __('Aborted'); ?></th>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Aborted_clients'], 4, 0); ?></td>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Aborted_clients'] * $hour_factor,
            4, 2); ?></td>
    <td class="value"><?php echo
        $server_status['Connections'] > 0
      ? PMA_formatNumber(
            $server_status['Aborted_clients'] * 100 / $server_status['Connections'],
            0, 2) . '%'
      : '--- '; ?></td>
</tr>
<tr class="noclick even">
    <th class="name"><?php echo __('Total'); ?></th>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Connections'], 4, 0); ?></td>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Connections'] * $hour_factor,
            4, 2); ?></td>
    <td class="value"><?php echo
        PMA_formatNumber(100, 0, 2); ?>%</td>
</tr>
</tbody>
</table>

	</div>
	<div id="statusTabs2">
	

<h3 id="serverstatusqueries"><?php echo
    sprintf(__('<b>Query statistics</b>: Since its startup, %s queries have been sent to the server.'),
        PMA_formatNumber($server_status['Questions'], 0));
    echo PMA_showMySQLDocu('server-status-variables', 'server-status-variables', false, 'statvar_Questions');
    ?></h3>

<table id="serverstatusqueriessummary" class="data">
<thead>
<tr>
    <th><?php echo __('Total'); ?></th>
    <th>&oslash; <?php echo __('per hour'); ?></th>
    <th>&oslash; <?php echo __('per minute'); ?></th>
    <th>&oslash; <?php echo __('per second'); ?></th>
</tr>
</thead>
<tbody>
<tr class="noclick odd">
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Questions'], 4, 0); ?></td>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Questions'] * $hour_factor,
            3, 2); ?></td>
    <td class="value"><?php echo
        PMA_formatNumber(
            $server_status['Questions'] * 60 / $server_status['Uptime'],
            3, 2); ?></td>
    <td class="value"><?php echo
        PMA_formatNumber(
            $server_status['Questions'] / $server_status['Uptime'],
            3, 2); ?></td>
</tr>
</tbody>
</table>

<?php

$used_queries = $sections['com']['vars'];
// reverse sort by value to show most used statements first
arsort($used_queries);
// remove all zero values from the end
// variable empty for Drizzle
if ($used_queries) {
    while (end($used_queries) == 0) {
        array_pop($used_queries);
    }
}

// number of tables to split values into
$tables         = 3;
$max_rows_per_table = (int) ceil(count($used_queries) / $tables);
$current_table  = 0;
$odd_row        = true;
$count_displayed_rows      = 0;
$perc_factor    = 100 / ($server_status['Questions'] - $server_status['Connections']);

?>
    <table id="serverstatusqueriesdetails" class="data">
    <col class="namecol" />
    <col class="valuecol" span="3" />
    <thead>
        <tr><th colspan="2"><?php echo __('Query type'); ?></th>
            <th>&oslash; <?php echo __('per hour'); ?></th>
            <th>%</th>
        </tr>
    </thead>
    <tbody>

<?php
foreach ($used_queries as $name => $value) {
    $current_table++;
    $odd_row = !$odd_row;

// For the percentage column, use Questions - Connections, because
// the number of connections is not an item of the Query types
// but is included in Questions. Then the total of the percentages is 100.
    $name = str_replace('Com_', '', $name);
    $name = str_replace('_', ' ', $name);
?>
        <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
            <th class="name"><?php echo htmlspecialchars($name); ?></th>
            <td class="value"><?php echo PMA_formatNumber($value, 4, 0); ?></td>
            <td class="value"><?php echo
                PMA_formatNumber($value * $hour_factor, 3, 3); ?></td>
            <td class="value"><?php echo
                PMA_formatNumber($value * $perc_factor, 0, 2); ?>%</td>
        </tr>
<?php
}
?>
    </tbody>
    </table>

<?php if ($used_queries): ?>
<div id="serverstatusquerieschart">
<?php
    if (empty($_REQUEST["query_chart"])) {
        echo '<a href="' . $PMA_PHP_SELF . '?' . $url_query
            . '&amp;query_chart=1#serverstatusqueries"'
            . 'title="' . __('Show query chart') . '">['
            . __('Show query chart') . ']</a>';
        PMA_Message::notice( __('Note: Generating the query chart can take a long time.'))->display();
    } else {
        echo createQueryChart($used_queries);
    }
?>
</div>
<?php endif; ?>

	</div>
	<div id="statusTabs3">


<div id="serverstatusvars">
<fieldset style="display:none;">
<legend>Filters</legend>
<div class="formelement">
    <label for="filterText">Containing the word:</label>
    <input name="filterText" type="text" id="filterText" style="vertical-align: baseline;" />
</div>
<div class="formelement">
    <input type="checkbox" name="filterAlert" id="filterAlert">
    <label for="filterAlert">Show only alert values</label> 
</div>
</fieldset>
<table class="data" id="serverstatusvariables">
    <col class="namecol" />
    <col class="valuecol" />
    <col class="descrcol" />
    <thead>
        <tr>
            <th><?php echo __('Variable'); ?></th>
            <th><?php echo __('Value'); ?></th>
            <th><?php echo __('Description'); ?></th>
        </tr>
    </thead>
    <!--<tfoot>
        <tr class="tblFooters">
            <th colspan="3" class="tblFooters">
            </th>
        </tr>
    </tfoot>-->	
    <tbody>

<?php
//Unset used variables
unset(
    $tables, $max_rows_per_table, $current_table, $count_displayed_rows, $perc_factor,
    $hour_factor, $sections['com'],
    $server_status['Aborted_clients'], $server_status['Aborted_connects'],
    $server_status['Max_used_connections'], $server_status['Bytes_received'],
    $server_status['Bytes_sent'], $server_status['Connections'],
    $server_status['Questions'], $server_status['Uptime'],
    $used_queries
);

$odd_row = false;
foreach ($server_status as $name => $value) {
        $odd_row = !$odd_row;
?>
        <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
            <th class="name"><?php echo htmlspecialchars($name) . PMA_showMySQLDocu('server-status-variables', 'server-status-variables', false, 'statvar_' . $name); ?>
            </th>
            <td class="value"><?php
            if (isset($alerts[$name])) {
                if ($value > $alerts[$name]) {
                    echo '<span class="attention">';
                } else {
                    echo '<span class="allfine">';
                }
            }
            if ('%' === substr($name, -1, 1)) {
                echo PMA_formatNumber($value, 0, 2) . ' %';
            } elseif (is_numeric($value) && $value == (int) $value && $value > 1000) {
                echo PMA_formatNumber($value, 3, 1);
            } elseif (is_numeric($value) && $value == (int) $value) {
                echo PMA_formatNumber($value, 4, 0);
            } elseif (is_numeric($value)) {
                echo PMA_formatNumber($value, 3, 1);
            } else {
                echo htmlspecialchars($value);
            }
            if (isset($alerts[$name])) {
                echo '</span>';
            }
            ?></td>
            <td class="descr">
            <?php
            if (isset($GLOBALS['strShowStatus' . $name . 'Descr'])) {
                echo $GLOBALS['strShowStatus' . $name . 'Descr'];
            }

            if (isset($links[$name])) {
                foreach ($links[$name] as $link_name => $link_url) {
                    if ('doc' == $link_name) {
                        echo PMA_showMySQLDocu($link_url, $link_url);
                    } else {
                        echo ' <a href="' . $link_url . '">' . $link_name . '</a>' .
                        "\n";
                    }
                }
                unset($link_url, $link_name);
            }
            ?>
            </td>
        </tr>
<?php
    unset($name, $value);
?>
<?php
}

unset($section_name, $section, $sections, $server_status, $odd_row, $alerts);
?>
</tbody>
</table>
</div>
</div>
<?php
/* if the server works as master or slave in replication process, display useful information */
if ($server_master_status || $server_slave_status)
{
?>
  <hr class="clearfloat" />

  <h3><a name="replication"></a><?php echo __('Replication status'); ?></h3>
<?php

    foreach ($replication_types as $type)
    {
        if (${"server_{$type}_status"}) {
            PMA_replication_print_status_table($type);
        }
    }
    unset($types);
}
?>

</div>

<?php

function createQueryChart($com_vars=FALSE) {
	if(!$com_vars) 
		$com_vars = PMA_DBI_fetch_result("SHOW GLOBAL STATUS LIKE 'Com\_%'", 0, 1);
		
	arsort($com_vars);
	
	$merge_minimum = array_sum($com_vars) * 0.005;
	$merged_value = 0;
	
    // remove zero values from the end, as well as merge together every value that is below 0.5%
    // variable empty for Drizzle
    if ($com_vars) {
        while (($last_element=end($com_vars)) <= $merge_minimum) {
            array_pop($com_vars);
			$merged_value += $last_element;
        }
		
		$com_vars['Other'] = $merged_value;
        return PMA_chart_status($com_vars);
    }
	
	return '';
}

/**
 * Sends the footer
 */
require './libraries/footer.inc.php';
?>
