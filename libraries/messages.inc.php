<?php
/* $Id$ */
/**
 * Messages for phpMyAdmin.
 *
 * This file is here for easy transition to Gettext. You should not add any
 * new messages here, use instead gettext directly in your template/PHP
 * file.
 */

if (!function_exists('__')) {
    die('Bad invocation!');
}

/* We use only utf-8 */
$charset = 'utf-8';

/* l10n: Text direction, use either ltr or rtl */
$text_dir = __('ltr');

$strLatexContent = __('Content of table __TABLE__');
$strLatexContinued = __('(continued)');
$strLatexStructure = __('Structure of table __TABLE__');

$strPrivDescAllPrivileges = __('Includes all privileges except GRANT.');
$strPrivDescAlter = __('Allows altering the structure of existing tables.');
$strPrivDescAlterRoutine = __('Allows altering and dropping stored routines.');
$strPrivDescCreateDb = __('Allows creating new databases and tables.');
$strPrivDescCreateRoutine = __('Allows creating stored routines.');
$strPrivDescCreateTbl = __('Allows creating new tables.');
$strPrivDescCreateTmpTable = __('Allows creating temporary tables.');
$strPrivDescCreateUser = __('Allows creating, dropping and renaming user accounts.');
$strPrivDescCreateView = __('Allows creating new views.');
$strPrivDescDelete = __('Allows deleting data.');
$strPrivDescDropDb = __('Allows dropping databases and tables.');
$strPrivDescDropTbl = __('Allows dropping tables.');
$strPrivDescEvent = __('Allows to set up events for the event scheduler');
$strPrivDescExecute = __('Allows executing stored routines.');
$strPrivDescFile = __('Allows importing data from and exporting data into files.');
$strPrivDescGrant = __('Allows adding users and privileges without reloading the privilege tables.');
$strPrivDescIndex = __('Allows creating and dropping indexes.');
$strPrivDescInsert = __('Allows inserting and replacing data.');
$strPrivDescLockTables = __('Allows locking tables for the current thread.');
$strPrivDescMaxConnections = __('Limits the number of new connections the user may open per hour.');
$strPrivDescMaxQuestions = __('Limits the number of queries the user may send to the server per hour.');
$strPrivDescMaxUpdates = __('Limits the number of commands that change any table or database the user may execute per hour.');
$strPrivDescMaxUserConnections = __('Limits the number of simultaneous connections the user may have.');
$strPrivDescProcess = __('Allows viewing processes of all users');
$strPrivDescReferences = __('Has no effect in this MySQL version.');
$strPrivDescReload = __('Allows reloading server settings and flushing the server\'s caches.');
$strPrivDescReplClient = __('Allows the user to ask where the slaves / masters are.');
$strPrivDescReplSlave = __('Needed for the replication slaves.');
$strPrivDescSelect = __('Allows reading data.');
$strPrivDescShowDb = __('Gives access to the complete list of databases.');
$strPrivDescShowView = __('Allows performing SHOW CREATE VIEW queries.');
$strPrivDescShutdown = __('Allows shutting down the server.');
$strPrivDescSuper = __('Allows connecting, even if maximum number of connections is reached; required for most administrative operations like setting global variables or killing threads of other users.');
$strPrivDescTrigger = __('Allows creating and dropping triggers');
$strPrivDescUpdate = __('Allows changing data.');
$strPrivDescUsage = __('No privileges.');

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
?>
