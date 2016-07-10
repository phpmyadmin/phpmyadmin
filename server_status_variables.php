<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays a list of server status variables
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/ServerStatusData.class.php';
if (PMA_DRIZZLE) {
    $server_master_status = false;
    $server_slave_status = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

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

$ServerStatusData = new PMA_ServerStatusData();

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('server_status_variables.js');
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('server_status_sorter.js');

$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(getFilterHtml($ServerStatusData));
$response->addHTML(getLinkSuggestionsHtml($ServerStatusData));
$response->addHTML(getVariablesTableHtml($ServerStatusData));
$response->addHTML('</div>');

exit;

/**
 * Returns the html for the list filter
 *
 * @param Object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function getFilterHtml($ServerStatusData)
{
    $filterAlert = '';
    if (! empty($_REQUEST['filterAlert'])) {
        $filterAlert = ' checked="checked"';
    }
    $filterText = '';
    if (! empty($_REQUEST['filterText'])) {
        $filterText = htmlspecialchars($_REQUEST['filterText']);
    }
    $dontFormat = '';
    if (! empty($_REQUEST['dontFormat'])) {
        $dontFormat = ' checked="checked"';
    }

    $retval  = '';
    $retval .= '<fieldset id="tableFilter">';
    $retval .= '<legend>' . __('Filters') . '</legend>';
    $retval .= '<form action="server_status_variables.php?' . PMA_generate_common_url() . '">';
    $retval .= '<input type="submit" value="' . __('Refresh') . '" />';
    $retval .= '<div class="formelement">';
    $retval .= '<label for="filterText">' . __('Containing the word:') . '</label>';
    $retval .= '<input name="filterText" type="text" id="filterText" '
        . 'style="vertical-align: baseline;" value="' . $filterText . '" />';
    $retval .= '</div>';
    $retval .= '<div class="formelement">';
    $retval .= '<input' . $filterAlert . ' type="checkbox" name="filterAlert" id="filterAlert" />';
    $retval .= '<label for="filterAlert">';
    $retval .= __('Show only alert values');
    $retval .= '</label>';
    $retval .= '</div>';
    $retval .= '<div class="formelement">';
    $retval .= '<select id="filterCategory" name="filterCategory">';
    $retval .= '<option value="">' . __('Filter by category…') . '</option>';

    foreach ($ServerStatusData->sections as $section_id => $section_name) {
        if (isset($ServerStatusData->categoryUsed[$section_id])) {
            if (! empty($_REQUEST['filterCategory'])
                && $_REQUEST['filterCategory'] == $section_id
            ) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $retval .= '<option' . $selected . ' value="' . $section_id. '">';
            $retval .= htmlspecialchars($section_name) . '</option>';
        }
    }
    $retval .= '</select>';
    $retval .= '</div>';
    $retval .= '<div class="formelement">';
    $retval .= '<input' . $dontFormat . ' type="checkbox" name="dontFormat" id="dontFormat" />';
    $retval .= '<label for="dontFormat">';
    $retval .= __('Show unformatted values');
    $retval .= '</label>';
    $retval .= '</div>';
    $retval .= '</form>';
    $retval .= '</fieldset>';

    return $retval;
}

/**
 * Prints the suggestion links
 *
 * @param Object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function getLinkSuggestionsHtml($ServerStatusData)
{
    $retval  = '<div id="linkSuggestions" class="defaultLinks" style="display:none">';
    $retval .= '<p class="notice">' . __('Related links:');
    foreach ($ServerStatusData->links as $section_name => $section_links) {
        $retval .= '<span class="status_' . $section_name . '"> ';
        $i=0;
        foreach ($section_links as $link_name => $link_url) {
            if ($i > 0) {
                $retval .= ', ';
            }
            if ('doc' == $link_name) {
                $retval .= PMA_Util::showMySQLDocu($link_url, $link_url);
            } else {
                $retval .= '<a href="' . $link_url . '">' . $link_name . '</a>';
            }
            $i++;
        }
        $retval .= '</span>';
    }
    unset($link_url, $link_name, $i);
    $retval .= '</p>';
    $retval .= '</div>';

    return $retval;
}

/**
 * Returns a table with variables information
 *
 * @param Object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function getVariablesTableHtml($ServerStatusData)
{
    $retval  = '';
    $strShowStatus = getStatusVariablesDescriptions();
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

        'Qcache_free_blocks' => isset($ServerStatusData->server_status['Qcache_total_blocks'])
            ? $ServerStatusData->server_status['Qcache_total_blocks'] / 5 : 0,
        'Slow_launch_threads' => 0,

        // depends on Key_read_requests
        // normaly lower then 1:0.01
        'Key_reads' => isset($ServerStatusData->status['Key_read_requests'])
            ? (0.01 * $ServerStatusData->status['Key_read_requests']) : 0,
        // depends on Key_write_requests
        // normaly nearly 1:1
        'Key_writes' => isset($ServerStatusData->status['Key_write_requests'])
            ? (0.9 * $ServerStatusData->status['Key_write_requests']) : 0,

        'Key_buffer_fraction' => 0.5,

        // alert if more than 95% of thread cache is in use
        'Threads_cached' => isset($ServerStatusData->variables['thread_cache_size'])
            ? 0.95 * $ServerStatusData->variables['thread_cache_size'] : 0

        // higher is better
        // variable => min value
        //'Handler read key' => '> ',
    );

    $retval .= '<table class="data sortable noclick" id="serverstatusvariables">';
    $retval .= '<col class="namecol" />';
    $retval .= '<col class="valuecol" />';
    $retval .= '<col class="descrcol" />';
    $retval .= '<thead>';
    $retval .= '<tr>';
    $retval .= '<th>' . __('Variable') . '</th>';
    $retval .= '<th>' . __('Value') . '</th>';
    $retval .= '<th>' . __('Description') . '</th>';
    $retval .= '</tr>';
    $retval .= '</thead>';
    $retval .= '<tbody>';

    $odd_row = false;
    foreach ($ServerStatusData->status as $name => $value) {
        $odd_row = !$odd_row;
        $retval .= '<tr class="' . ($odd_row ? 'odd' : 'even')
            . (isset($ServerStatusData->allocationMap[$name])?' s_' . $ServerStatusData->allocationMap[$name] : '')
            . '">';

        $retval .= '<th class="name">';
        $retval .= htmlspecialchars(str_replace('_', ' ', $name));
        /* Fields containing % are calculated, they can not be described in MySQL documentation */
        if (strpos($name, '%') === false) {
            $retval .= PMA_Util::showMySQLDocu(
                'server-status-variables',
                'server-status-variables',
                false,
                'statvar_' . $name
            );
        }
        $retval .= '</th>';

        $retval .= '<td class="value"><span class="formatted">';
        if (isset($alerts[$name])) {
            if ($value > $alerts[$name]) {
                $retval .= '<span class="attention">';
            } else {
                $retval .= '<span class="allfine">';
            }
        }
        if ('%' === substr($name, -1, 1)) {
            $retval .= htmlspecialchars(PMA_Util::formatNumber($value, 0, 2)) . ' %';
        } elseif (strpos($name, 'Uptime') !== false) {
            $retval .= htmlspecialchars(
                PMA_Util::timespanFormat($value)
            );
        } elseif (is_numeric($value) && $value == (int) $value && $value > 1000) {
            $retval .= '<abbr title="'
                // makes available the raw value as a title
                . htmlspecialchars(PMA_Util::formatNumber($value, 0))
                . '">'
                . htmlspecialchars(PMA_Util::formatNumber($value, 3, 1));
        } elseif (is_numeric($value) && $value == (int) $value) {
            $retval .= htmlspecialchars(PMA_Util::formatNumber($value, 3, 0));
        } elseif (is_numeric($value)) {
            $retval .= htmlspecialchars(PMA_Util::formatNumber($value, 3, 1));
        } else {
            $retval .= htmlspecialchars($value);
        }
        if (isset($alerts[$name])) {
            $retval .= '</span>';
        }
        $retval .= '</span>';
        $retval .= '<span style="display:none;" class="original">';
        $retval .= htmlspecialchars($value);
        $retval .= '</span>';
        $retval .= '</td>';
        $retval .= '<td class="descr">';

        if (isset($strShowStatus[$name])) {
            $retval .= $strShowStatus[$name];
        }

        if (isset($ServerStatusData->links[$name])) {
            foreach ($ServerStatusData->links[$name] as $link_name => $link_url) {
                if ('doc' == $link_name) {
                    $retval .= PMA_Util::showMySQLDocu($link_url, $link_url);
                } else {
                    $retval .= ' <a href="' . $link_url . '">' . $link_name . '</a>';
                }
            }
            unset($link_url, $link_name);
        }
        $retval .= '</td>';
        $retval .= '</tr>';
    }
    $retval .= '</tbody>';
    $retval .= '</table>';

    return $retval;
}

/**
 * Returns a list of variable descriptions
 *
 * @return array
 */
function getStatusVariablesDescriptions()
{
    /**
     * Messages are built using the message name
     */
    return array(
        'Aborted_clients' => __(
            'The number of connections that were aborted because the client died'
            . ' without closing the connection properly.'
        ),
        'Aborted_connects' => __(
            'The number of failed attempts to connect to the MySQL server.'
        ),
        'Binlog_cache_disk_use' => __(
            'The number of transactions that used the temporary binary log cache'
            . ' but that exceeded the value of binlog_cache_size and used a'
            . ' temporary file to store statements from the transaction.'
        ),
        'Binlog_cache_use' => __(
            'The number of transactions that used the temporary binary log cache.'
        ),
        'Connections' => __(
            'The number of connection attempts (successful or not)'
            . ' to the MySQL server.'
        ),
        'Created_tmp_disk_tables' => __(
            'The number of temporary tables on disk created automatically by'
            . ' the server while executing statements. If'
            . ' Created_tmp_disk_tables is big, you may want to increase the'
            . ' tmp_table_size  value to cause temporary tables to be'
            . ' memory-based instead of disk-based.'
        ),
        'Created_tmp_files' => __(
            'How many temporary files mysqld has created.'
        ),
        'Created_tmp_tables' => __(
            'The number of in-memory temporary tables created automatically'
            . ' by the server while executing statements.'
        ),
        'Delayed_errors' => __(
            'The number of rows written with INSERT DELAYED for which some'
            . ' error occurred (probably duplicate key).'
        ),
        'Delayed_insert_threads' => __(
            'The number of INSERT DELAYED handler threads in use. Every'
            . ' different table on which one uses INSERT DELAYED gets'
            . ' its own thread.'
        ),
        'Delayed_writes' => __(
            'The number of INSERT DELAYED rows written.'
        ),
        'Flush_commands'  => __(
            'The number of executed FLUSH statements.'
        ),
        'Handler_commit' => __(
            'The number of internal COMMIT statements.'
        ),
        'Handler_delete' => __(
            'The number of times a row was deleted from a table.'
        ),
        'Handler_discover' => __(
            'The MySQL server can ask the NDB Cluster storage engine if it'
            . ' knows about a table with a given name. This is called discovery.'
            . ' Handler_discover indicates the number of time tables have been'
            . ' discovered.'
        ),
        'Handler_read_first' => __(
            'The number of times the first entry was read from an index. If this'
            . ' is high, it suggests that the server is doing a lot of full'
            . ' index scans; for example, SELECT col1 FROM foo, assuming that'
            . ' col1 is indexed.'
        ),
        'Handler_read_key' => __(
            'The number of requests to read a row based on a key. If this is'
            . ' high, it is a good indication that your queries and tables'
            . ' are properly indexed.'
        ),
        'Handler_read_next' => __(
            'The number of requests to read the next row in key order. This is'
            . ' incremented if you are querying an index column with a range'
            . ' constraint or if you are doing an index scan.'
        ),
        'Handler_read_prev' => __(
            'The number of requests to read the previous row in key order.'
            . ' This read method is mainly used to optimize ORDER BY … DESC.'
        ),
        'Handler_read_rnd' => __(
            'The number of requests to read a row based on a fixed position.'
            . ' This is high if you are doing a lot of queries that require'
            . ' sorting of the result. You probably have a lot of queries that'
            . ' require MySQL to scan whole tables or you have joins that'
            . ' don\'t use keys properly.'
        ),
        'Handler_read_rnd_next' => __(
            'The number of requests to read the next row in the data file.'
            . ' This is high if you are doing a lot of table scans. Generally'
            . ' this suggests that your tables are not properly indexed or that'
            . ' your queries are not written to take advantage of the indexes'
            . ' you have.'
        ),
        'Handler_rollback' => __(
            'The number of internal ROLLBACK statements.'
        ),
        'Handler_update' => __(
            'The number of requests to update a row in a table.'
        ),
        'Handler_write' => __(
            'The number of requests to insert a row in a table.'
        ),
        'Innodb_buffer_pool_pages_data' => __(
            'The number of pages containing data (dirty or clean).'
        ),
        'Innodb_buffer_pool_pages_dirty' => __(
            'The number of pages currently dirty.'
        ),
        'Innodb_buffer_pool_pages_flushed' => __(
            'The number of buffer pool pages that have been requested'
            . ' to be flushed.'
        ),
        'Innodb_buffer_pool_pages_free' => __(
            'The number of free pages.'
        ),
        'Innodb_buffer_pool_pages_latched' => __(
            'The number of latched pages in InnoDB buffer pool. These are pages'
            . ' currently being read or written or that can\'t be flushed or'
            . ' removed for some other reason.'
        ),
        'Innodb_buffer_pool_pages_misc' => __(
            'The number of pages busy because they have been allocated for'
            . ' administrative overhead such as row locks or the adaptive'
            . ' hash index. This value can also be calculated as'
            . ' Innodb_buffer_pool_pages_total - Innodb_buffer_pool_pages_free'
            . ' - Innodb_buffer_pool_pages_data.'
        ),
        'Innodb_buffer_pool_pages_total' => __(
            'Total size of buffer pool, in pages.'
        ),
        'Innodb_buffer_pool_read_ahead_rnd' => __(
            'The number of "random" read-aheads InnoDB initiated. This happens'
            . ' when a query is to scan a large portion of a table but in'
            . ' random order.'
        ),
        'Innodb_buffer_pool_read_ahead_seq' => __(
            'The number of sequential read-aheads InnoDB initiated. This'
            . ' happens when InnoDB does a sequential full table scan.'
        ),
        'Innodb_buffer_pool_read_requests' => __(
            'The number of logical read requests InnoDB has done.'
        ),
        'Innodb_buffer_pool_reads' => __(
            'The number of logical reads that InnoDB could not satisfy'
            . ' from buffer pool and had to do a single-page read.'
        ),
        'Innodb_buffer_pool_wait_free' => __(
            'Normally, writes to the InnoDB buffer pool happen in the'
            . ' background. However, if it\'s necessary to read or create a page'
            . ' and no clean pages are available, it\'s necessary to wait for'
            . ' pages to be flushed first. This counter counts instances of'
            . ' these waits. If the buffer pool size was set properly, this'
            . ' value should be small.'
        ),
        'Innodb_buffer_pool_write_requests' => __(
            'The number writes done to the InnoDB buffer pool.'
        ),
        'Innodb_data_fsyncs' => __(
            'The number of fsync() operations so far.'
        ),
        'Innodb_data_pending_fsyncs' => __(
            'The current number of pending fsync() operations.'
        ),
        'Innodb_data_pending_reads' => __(
            'The current number of pending reads.'
        ),
        'Innodb_data_pending_writes' => __(
            'The current number of pending writes.'
        ),
        'Innodb_data_read' => __(
            'The amount of data read so far, in bytes.'
        ),
        'Innodb_data_reads' => __(
            'The total number of data reads.'
        ),
        'Innodb_data_writes' => __(
            'The total number of data writes.'
        ),
        'Innodb_data_written' => __(
            'The amount of data written so far, in bytes.'
        ),
        'Innodb_dblwr_pages_written' => __(
            'The number of pages that have been written for'
            . ' doublewrite operations.'
        ),
        'Innodb_dblwr_writes' => __(
            'The number of doublewrite operations that have been performed.'
        ),
        'Innodb_log_waits' => __(
            'The number of waits we had because log buffer was too small and'
            . ' we had to wait for it to be flushed before continuing.'
        ),
        'Innodb_log_write_requests' => __(
            'The number of log write requests.'
        ),
        'Innodb_log_writes' => __(
            'The number of physical writes to the log file.'
        ),
        'Innodb_os_log_fsyncs' => __(
            'The number of fsync() writes done to the log file.'
        ),
        'Innodb_os_log_pending_fsyncs' => __(
            'The number of pending log file fsyncs.'
        ),
        'Innodb_os_log_pending_writes' => __(
            'Pending log file writes.'
        ),
        'Innodb_os_log_written' => __(
            'The number of bytes written to the log file.'
        ),
        'Innodb_pages_created' => __(
            'The number of pages created.'
        ),
        'Innodb_page_size' => __(
            'The compiled-in InnoDB page size (default 16KB). Many values are'
            . ' counted in pages; the page size allows them to be easily'
            . ' converted to bytes.'
        ),
        'Innodb_pages_read' => __(
            'The number of pages read.'
        ),
        'Innodb_pages_written' => __(
            'The number of pages written.'
        ),
        'Innodb_row_lock_current_waits' => __(
            'The number of row locks currently being waited for.'
        ),
        'Innodb_row_lock_time_avg' => __(
            'The average time to acquire a row lock, in milliseconds.'
        ),
        'Innodb_row_lock_time' => __(
            'The total time spent in acquiring row locks, in milliseconds.'
        ),
        'Innodb_row_lock_time_max' => __(
            'The maximum time to acquire a row lock, in milliseconds.'
        ),
        'Innodb_row_lock_waits' => __(
            'The number of times a row lock had to be waited for.'
        ),
        'Innodb_rows_deleted' => __(
            'The number of rows deleted from InnoDB tables.'
        ),
        'Innodb_rows_inserted' => __(
            'The number of rows inserted in InnoDB tables.'
        ),
        'Innodb_rows_read' => __(
            'The number of rows read from InnoDB tables.'
        ),
        'Innodb_rows_updated' => __(
            'The number of rows updated in InnoDB tables.'
        ),
        'Key_blocks_not_flushed' => __(
            'The number of key blocks in the key cache that have changed but'
            . ' haven\'t yet been flushed to disk. It used to be known as'
            . ' Not_flushed_key_blocks.'
        ),
        'Key_blocks_unused' => __(
            'The number of unused blocks in the key cache. You can use this'
            . ' value to determine how much of the key cache is in use.'
        ),
        'Key_blocks_used' => __(
            'The number of used blocks in the key cache. This value is a'
            . ' high-water mark that indicates the maximum number of blocks'
            . ' that have ever been in use at one time.'
        ),
        'Key_buffer_fraction_%' => __(
            'Percentage of used key cache (calculated value)'
        ),
        'Key_read_requests' => __(
            'The number of requests to read a key block from the cache.'
        ),
        'Key_reads' => __(
            'The number of physical reads of a key block from disk. If Key_reads'
            . ' is big, then your key_buffer_size value is probably too small.'
            . ' The cache miss rate can be calculated as'
            . ' Key_reads/Key_read_requests.'
        ),
        'Key_read_ratio_%' => __(
            'Key cache miss calculated as rate of physical reads compared'
            . ' to read requests (calculated value)'
        ),
        'Key_write_requests' => __(
            'The number of requests to write a key block to the cache.'
        ),
        'Key_writes' => __(
            'The number of physical writes of a key block to disk.'
        ),
        'Key_write_ratio_%' => __(
            'Percentage of physical writes compared'
            . ' to write requests (calculated value)'
        ),
        'Last_query_cost' => __(
            'The total cost of the last compiled query as computed by the query'
            . ' optimizer. Useful for comparing the cost of different query'
            . ' plans for the same query. The default value of 0 means that'
            . ' no query has been compiled yet.'
        ),
        'Max_used_connections' => __(
            'The maximum number of connections that have been in use'
            . ' simultaneously since the server started.'
        ),
        'Not_flushed_delayed_rows' => __(
            'The number of rows waiting to be written in INSERT DELAYED queues.'
        ),
        'Opened_tables' => __(
            'The number of tables that have been opened. If opened tables is'
            . ' big, your table cache value is probably too small.'
        ),
        'Open_files' => __(
            'The number of files that are open.'
        ),
        'Open_streams' => __(
            'The number of streams that are open (used mainly for logging).'
        ),
        'Open_tables' => __(
            'The number of tables that are open.'
        ),
        'Qcache_free_blocks' => __(
            'The number of free memory blocks in query cache. High numbers can'
            . ' indicate fragmentation issues, which may be solved by issuing'
            . ' a FLUSH QUERY CACHE statement.'
        ),
        'Qcache_free_memory' => __(
            'The amount of free memory for query cache.'
        ),
        'Qcache_hits' => __(
            'The number of cache hits.'
        ),
        'Qcache_inserts' => __(
            'The number of queries added to the cache.'
        ),
        'Qcache_lowmem_prunes' => __(
            'The number of queries that have been removed from the cache to'
            . ' free up memory for caching new queries. This information can'
            . ' help you tune the query cache size. The query cache uses a'
            . ' least recently used (LRU) strategy to decide which queries'
            . ' to remove from the cache.'
        ),
        'Qcache_not_cached' => __(
            'The number of non-cached queries (not cachable, or not cached'
            . ' due to the query_cache_type setting).'
        ),
        'Qcache_queries_in_cache' => __(
            'The number of queries registered in the cache.'
        ),
        'Qcache_total_blocks' => __(
            'The total number of blocks in the query cache.'
        ),
        'Rpl_status' => __(
            'The status of failsafe replication (not yet implemented).'
        ),
        'Select_full_join' => __(
            'The number of joins that do not use indexes. If this value is'
            . ' not 0, you should carefully check the indexes of your tables.'
        ),
        'Select_full_range_join' => __(
            'The number of joins that used a range search on a reference table.'
        ),
        'Select_range_check' => __(
            'The number of joins without keys that check for key usage after'
            . ' each row. (If this is not 0, you should carefully check the'
            . ' indexes of your tables.)'
        ),
        'Select_range' => __(
            'The number of joins that used ranges on the first table. (It\'s'
            . ' normally not critical even if this is big.)'
        ),
        'Select_scan' => __(
            'The number of joins that did a full scan of the first table.'
        ),
        'Slave_open_temp_tables' => __(
            'The number of temporary tables currently'
            . ' open by the slave SQL thread.'
        ),
        'Slave_retried_transactions' => __(
            'Total (since startup) number of times the replication slave SQL'
            . ' thread has retried transactions.'
        ),
        'Slave_running' => __(
            'This is ON if this server is a slave that is connected to a master.'
        ),
        'Slow_launch_threads' => __(
            'The number of threads that have taken more than slow_launch_time'
            . ' seconds to create.'
        ),
        'Slow_queries' => __(
            'The number of queries that have taken more than long_query_time'
            . ' seconds.'
        ),
        'Sort_merge_passes' => __(
            'The number of merge passes the sort algorithm has had to do.'
            . ' If this value is large, you should consider increasing the'
            . ' value of the sort_buffer_size system variable.'
        ),
        'Sort_range' => __(
            'The number of sorts that were done with ranges.'
        ),
        'Sort_rows' => __(
            'The number of sorted rows.'
        ),
        'Sort_scan' => __(
            'The number of sorts that were done by scanning the table.'
        ),
        'Table_locks_immediate' => __(
            'The number of times that a table lock was acquired immediately.'
        ),
        'Table_locks_waited' => __(
            'The number of times that a table lock could not be acquired'
            . ' immediately and a wait was needed. If this is high, and you have'
            . ' performance problems, you should first optimize your queries,'
            . ' and then either split your table or tables or use replication.'
        ),
        'Threads_cached' => __(
            'The number of threads in the thread cache. The cache hit rate can'
            . ' be calculated as Threads_created/Connections. If this value is'
            . ' red you should raise your thread_cache_size.'
        ),
        'Threads_connected' => __(
            'The number of currently open connections.'
        ),
        'Threads_created' => __(
            'The number of threads created to handle connections. If'
            . ' Threads_created is big, you may want to increase the'
            . ' thread_cache_size value. (Normally this doesn\'t give a notable'
            . ' performance improvement if you have a good thread'
            . ' implementation.)'
        ),
        'Threads_cache_hitrate_%' => __(
            'Thread cache hit rate (calculated value)'
        ),
        'Threads_running' => __(
            'The number of threads that are not sleeping.'
        )
    );
}

?>
