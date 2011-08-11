<?php
/* DO NOT EDIT! */
/* This is automatically generated file from libraries/advisory_rules.txt */

echo __('Uptime below one day');
echo __('Uptime is less than 1 day, performance tuning may not be accurate.');
echo __('To have more accurate averages it is recommended to let the server run for longer than a day before running this analyzer');
printf(__('The uptime is only %s'), 0);

echo __('Questions below 1,000');
echo __('Fewer than 1,000 questions have been run against this server. The recommendations may not be accurate.');
echo __('Let the server run for a longer time until it has executed a greater amount of queries.');
printf(__('Current amount of Questions: %s'), 0);

echo __('% slow queries');
echo __('There is a lot of slow queries compared to the overall amount of Queries.');
echo __('You might want to increase {long_query_time} or optimize the queries listed in the slow query log');
printf(__('The slow query rate should be below 5%%, your value is %s%%.'), 0);

echo __('slow query rate');
echo __('There is a high percentage of slow queries compared to the server uptime.');
echo __('You might want to increase {long_query_time} or optimize the queries listed in the slow query log');
printf(__('You have a slow query rate of %s per hour, you should have less than 1%% per hour.'), 0);

echo __('Long query time');
echo __('long_query_time is set to 10 seconds or more, thus only slow queries that take above 10 seconds are logged.');
echo __('It is suggested to set {long_query_time} to a lower value, depending on your enviroment. Usually a value of 1-5 seconds is suggested.');
printf(__('long_query_time is currently set to %ds.'), 0);

echo __('Slow query logging');
echo __('The slow query log is disabled.');
echo __('Enable slow query logging by setting {log_slow_queries} to \'ON\'. This will help troubleshooting badly performing queries.');
echo __('log_slow_queries is set to \'OFF\'');

echo __('Release Series');
echo __('The MySQL server version less then 5.1.');
echo __('You should upgrade, as MySQL 5.1 has improved performance, and MySQL 5.5 even more so.');
printf(__('Current version: %s'), 0);

echo __('Minor Version');
echo __('Version less than 5.1.30 (the first GA release of 5.1).');
echo __('You should upgrade, as recent versions of MySQL 5.1 have improved performance and MySQL 5.5 even more so.');
printf(__('Current version: %s'), 0);

echo __('Minor Version');
echo __('Version less than 5.5.8 (the first GA release of 5.5).');
echo __('You should upgrade, to a stable version of MySQL 5.5');
printf(__('Current version: %s'), 0);

echo __('Distribution');
echo __('Version is compiled from source, not a MySQL official binary.');
echo __('If you did not compile from source, you may be using a package modified by a distribution. The MySQL manual only is accurate for official MySQL binaries, not any package distributions (such as RedHat, Debian/Ubuntu etc).');
echo __('\'source\' found in version_comment');

echo __('Distribution');
echo __('The MySQL manual only is accurate for official MySQL binaries.');
echo __('Percona documentation is at http://www.percona.com/docs/wiki/');
echo __('\'percona\' found in version_comment');

echo __('MySQL Architecture');
echo __('MySQL is not compiled as a 64-bit package.');
echo __('Your memory capacity is above 3 GiB (assuming the Server is on localhost), so MySQL might not be able to access all of your memory. You might want to consider installing the 64-bit version of MySQL.');
printf(__('Available memory on this host: %s'), 0);

echo __('Query cache disabled');
echo __('The query cache is not enabled.');
echo __('The query cache is known to greatly improve performance if configured correctly. Enable it by setting {query_cache_size} to a 2 digit MiB value and setting {query_cache_type} to \'ON\'. <b>Note:</b> If you are using memcached, ignore this recommendation.');
echo __('query_cache_size is set to 0 or query_cache_type is set to \'OFF\'');

echo __('memcached usage');
echo __('Suboptimal caching method.');
echo __('You are using the MySQL Query cache with a fairly high traffic database. It might be worth considering to use <a href=\"http://dev.mysql.com/doc/refman/5.1/en/ha-memcached.html\">memcached</a> instead of the MySQL Query cache, especially if you have multiple slaves.');
printf(__('The query cache is enabled and the server receives %d queries per second. This rule fires if there is more than 100 queries per second.'), 0);

echo __('Query cache efficiency (%)');
echo __('Query cache not running efficiently, it has a low hit rate.');
echo __('Consider increasing {query_cache_limit}.');
printf(__('The current query cache hit rate of %s%% is below 20%%'), 0);

echo __('Query Cache usage');
echo __('Less than 80% of the query cache is being utilized.');
echo __('This might be caused by {query_cache_limit} being too low. Flushing the query cache might help as well.');
printf(__('The current ratio of free query cache memory to total query cache size is %s%%. It should be above 80%%'), 0);

echo __('Query cache fragmentation');
echo __('The query cache is considerably fragmented.');
echo __('Severe fragmentation is likely to (further) increase Qcache_lowmem_prunes. This might be caused by many Query cache low memory prunes due to {query_cache_size} being too small. For a immediate but short lived fix you can flush the query cache (might lock the query cache for a long time). Carefully adjusting {query_cache_min_res_unit} to a lower value might help too, e.g. you can set it to the average size of your queries in the cache using this formula: (query_cache_size - qcache_free_memory) / qcache_queries_in_cache');
printf(__('The cache is currently fragmented by %s%% , with 100%% fragmentation meaning that the query cache is an alternating pattern of free and used blocks. This value should be below 20%%.'), 0);

echo __('Query cache low memory prunes');
echo __('Cached queries are removed due to low query cache memory from the query cache.');
echo __('You might want to increase {query_cache_size}, however keep in mind that the overhead of maintaining the cache is likely to increase with its size, so do this in small increments and monitor the results.');
printf(__('The ratio of removed queries to inserted queries is %s%%. The lower this value is, the better (This rules firing limit: 0.1%)'), 0);

echo __('Query cache max size');
echo __('The query cache size is above 128 MiB. Big query caches may cause significant overhead that is required to maintain the cache.');
echo __('Depending on your enviroment, it might be performance increasing to reduce this value.');
printf(__('Current query cache size: %s'), 0);

echo __('Query cache min result size');
echo __('The max size of the result set in the query cache is the default of 1 MiB.');
echo __('Changing {query_cache_limit} (usually by increasing) may increase efficiency. This variable determines the maximum size a query result may have to be inserted into the query cache. If there are many query results above 1 MiB that are well cacheable (many reads, little writes) then increasing {query_cache_limit} will increase efficiency. Whereas in the case of many query results being above 1 MiB that are not very well cacheable (often invalidated due to table updates) increasing {query_cache_limit} might reduce efficiency.');
echo __('query_cache_limit is set to 1 MiB');

echo __('% sorts that cause temporary tales');
echo __('Too many sorts are causing temporary tables.');
echo __('Consider increasing sort_buffer_size and/or read_rnd_buffer_size, depending on your system memory limits');
printf(__('%s%% of all sorts cause temporary tables, this value should be lower than 10%%.'), 0);

echo __('rate of sorts that cause temporary tables');
echo __('Too many sorts are causing temporary tables.');
echo __('Consider increasing sort_buffer_size and/or read_rnd_buffer_size, depending on your system memory limits');
printf(__('Temporary tables average: %s, this value should be less than 1 per hour.'), 0);

echo __('Sort rows');
echo __('There are lots of rows being sorted.');
echo __('While there is nothing wrong with a high amount of row sorting, you might want to make sure that the queries which require a lot of sorting use indexed fields in the ORDER BY clause, as this will result in much faster sorting');
printf(__('Sorted rows average: %s'), 0);

echo __('rate of joins without indexes');
echo __('There are too many joins without indexes.');
echo __('This means that joins are doing full table scans. Adding indexes for the fields being used in the join conditions will greatly speed up table joins');
printf(__('Table joins average: %s, this value should be less than 1 per hour'), 0);

echo __('rate of reading first index entry');
echo __('The rate of reading the first index entry is high.');
echo __('This usually indicates frequent full index scans. Full index scans are faster than table scans but require lots of cpu cycles in big tables, if those tables that have or had high volumes of UPDATEs and DELETEs, running \'OPTIMIZE TABLE\' might reduce the amount of and/or speed up full index scans. Other than that full index scans can only be reduced by rewriting queries.');
printf(__('Index scans average: %s, this value should be less than 1 per hour'), 0);

echo __('rate of reading fixed position');
echo __('The rate of reading data from a fixed position is high.');
echo __('This indicates many queries need to sort results and/or do a full table scan, including join queries that do not use indexes. Add indexes where applicable.');
printf(__('Rate of reading fixed position average: %s, this value should be less than 1 per hour'), 0);

echo __('rate of reading next table row');
echo __('The rate of reading the next table row is high.');
echo __('This indicates many queries are doing full table scans. Add indexes where applicable.');
printf(__('Rate of reading next table row: %s, this value should be less than 1 per hour'), 0);

echo __('tmp_table_size vs. max_heap_table_size');
echo __('tmp_table_size and max_heap_table_size are not the same.');
echo __('If you have deliberatly changed one of either: The server uses the lower value of either to determine the maximum size of in-memory tables. So if you wish to increse the in-memory table limit you will have to increase the other value as well.');
printf(__('Current values are tmp_table_size: %s, max_heap_table_size: %s'), 0);

echo __('% temp disk tables');
echo __('Many temporary tables are being written to disk instead of being kept in memory.');
echo __('Increasing {max_heap_table_size} and {tmp_table_size} might help. However some temporary tables are always being written to disk, independent of the value of these variables. To elminiate these you will have to rewrite your queries to avoid those conditions (Within a temprorary table: Presence of a BLOB or TEXT column or presence of a column bigger than 512 bytes) as mentioned in the beginning of an <a href=\"http://www.facebook.com/note.php?note_id=10150111255065841&comments\">Article by the Pythian Group</a>');
printf(__('%s%% of all temporary tables are being written to disk, this value should be below 25%%'), 0);

echo __('temp disk rate');
echo __('Many temporary tables are being written to disk instead of being kept in memory.');
echo __('Increasing {max_heap_table_size} and {tmp_table_size} might help. However some temporary tables are always being written to disk, independent of the value of these variables. To elminiate these you will have to rewrite your queries to avoid those conditions (Within a temprorary table: Presence of a BLOB or TEXT column or presence of a column bigger than 512 bytes) as mentioned in in the <a href=\"http://dev.mysql.com/doc/refman/5.0/en/internal-temporary-tables.html\">MySQL Documentation</a>');
printf(__('Rate of temporay tables being written to disk: %s, this value should be less than 1 per hour'), 0);

echo __('MyISAM key buffer size');
echo __('Key buffer is not initialized. No MyISAM indexes will be cached.');
echo __('Set {key_buffer_size} depending on the size of your MyISAM indexes. 64M is a good start.');
echo __('key_buffer_size is 0');

echo __('max % MyISAM key buffer ever used');
echo __('MyISAM key buffer (index cache) % used is low.');
echo __('You may need to decrease the size of {key_buffer_size}, re-examine your tables to see if indexes have been removed, or examine queries and expectations about what indexes are being used.');
printf(__('max %% MyISAM key buffer ever used: %s, this value should be above 95%%'), 0);

echo __('% MyISAM key buffer used');
echo __('MyISAM key buffer (index cache) % used is low.');
echo __('You may need to decrease the size of {key_buffer_size}, re-examine your tables to see if indexes have been removed, or examine queries and expectations about what indexes are being used.');
printf(__('%% MyISAM key buffer used: %s, this value should be above 95%%'), 0);

echo __('% index reads from memory');
echo __('The % of indexes that use the MyISAM key buffer is low.');
echo __('You may need to increase {key_buffer_size}.');
printf(__('Index reads from memory: %s%%, this value should be above 95%%'), 0);

echo __('rate of table open');
echo __('The rate of opening tables is high.');
echo __('Opening tables requires disk I/O which is costly. Increasing {table_open_cache} might avoid this.');
printf(__('Opened table rate: %s, this value should be less than 10 per hour'), 0);

echo __('% open files');
echo __('The number of open files is approaching the max number of open files.  You may get a \"Too many open files\" error.');
echo __('Consider increasing {open_files_limit}, and check the error log when restarting after changing open_files_limit.');
printf(__('The number of opened files is at %s%% of the limit. It should be below 85%%'), 0);

echo __('rate of open files');
echo __('The rate of opening files is high.');
echo __('Consider increasing {open_files_limit}, and check the error log when restarting after changing open_files_limit.');
printf(__('Opened files rate: %s, this value should be less than 5 per hour'), 0);

echo __('Immediate table locks %');
echo __('Too many table locks were not granted immediately.');
echo __('Optimize queries and/or use InnoDB to reduce lock wait.');
printf(__('Immediate table locks: %s%%, this value should be above 95%%'), 0);

echo __('Table lock wait rate');
echo __('Too many table locks were not granted immediately.');
echo __('Optimize queries and/or use InnoDB to reduce lock wait.');
printf(__('Table lock wait rate: %s, this value should be less than 1 per hour'), 0);

echo __('thread cache');
echo __('Thread cache is disabled, resulting in more overhead from new connections to MySQL.');
echo __('Enable the thread cache by setting {thread_cache_size} > 0.');
echo __('The thread cache is set to 0');

echo __('thread cache hit rate %');
echo __('Thread cache is not efficient.');
echo __('Increase {thread_cache_size}.');
printf(__('Thread cache hitrate: %s%%, this value should be above 80%%'), 0);

echo __('Threads that are slow to launch');
echo __('There are too many threads that are slow to launch.');
echo __('This generally happens in case of general system overload as it is pretty simple operations. You might want to monitor your system load carefully.');
printf(__('%s thread(s) took longer than %s seconds to start, it should be 0'), 0);

echo __('Slow launch time');
echo __('Slow_launch_threads is above 2s');
echo __('Set slow_launch_time to 1s or 2s to correctly count threads that are slow to launch');
printf(__('slow_launch_time is set to %s'), 0);

echo __('% connections used');
echo __('The maximum amount of used connnections is getting close to the value of max_connections.');
echo __('Increase max_connections, or decrease wait_timeout so that connections that do not close database handlers properly get killed sooner. Make sure the code closes database handlers properly.');
printf(__('Max_used_connections is at %s%% of max_connections, it should be below 80%%'), 0);

echo __('% aborted connections');
echo __('Too many connections are aborted.');
echo __('Connections are usually aborted when they cannot be authorized. <a href=\"http://www.mysqlperformanceblog.com/2008/08/23/how-to-track-down-the-source-of-aborted_connects/\">This article</a> might help you track down the source.');
printf(__('%s%% of all connections are aborted. This value should be below 1%%'), 0);

echo __('rate of aborted connections');
echo __('Too many connections are aborted');
echo __('Connections are usually aborted when they cannot be authorized. <a href=\"http://www.mysqlperformanceblog.com/2008/08/23/how-to-track-down-the-source-of-aborted_connects/\">This article</a> might help you track down the source.');
printf(__('Aborted connections rate is at %s, this value should be less than 1 per hour'), 0);

echo __('% aborted clients');
echo __('Too many clients are aborted.');
echo __('Clients are usually aborted when they did not close their connection to MySQL properly. This can be due to network issues or code not closing a database handler properly. Check your network and code.');
printf(__('%s%% of all clients are aborted. This value should be below 2%%'), 0);

echo __('rate of aborted clients');
echo __('Too many clients are aborted.');
echo __('Clients are usually aborted when they did not close their connection to MySQL properly. This can be due to network issues or code not closing a database handler properly. Check your network and code.');
printf(__('Aborted client rate is at %s, this value should be less than 1 per hour'), 0);

echo __('Is InnoDB disabled?');
echo __('You do not have InnoDB enabled.');
echo __('InnoDB is usually the better choice for table engines.');
echo __('have_innodb is set to \'value\'');

echo __('% InnoDB log size');
echo __('The InnoDB log file size is not an appropriate size, in relation to the InnoDB buffer pool.');
echo __('Especiallay one a system with a lot of writes to InnoDB tables you shoud set innodb_log_file_size to 25% of {innodb_buffer_pool_size}. However the bigger this value, the longer the recovery time will be when database crashes, so this value should not be set much higher than 256 MiB. Please note however that you cannot simply change the value of this variable. You need to shutdown the server, remove the InnoDB log files, set the new value in my.cnf, start the server, then check the error logs if everything went fine. See also <a href=\"http://mysqldatabaseadministration.blogspot.com/2007/01/increase-innodblogfilesize-proper-way.html\">this blog entry</a>');
printf(__('Your InnoDB log size is at %s%% in relation to the InnoDB buffer pool size, it should not be below 20%%'), 0);

echo __('Max InnoDB log size');
echo __('The InnoDB log file size is inadequately large.');
echo __('It is usually sufficient to set innodb_log_file_size to 25% of the size of {innodb_buffer_pool_size}. A very innodb_log_file_size slows down the recovery time after a database crash considerably. See also <a href=\"http://www.mysqlperformanceblog.com/2006/07/03/choosing-proper-innodb_log_file_size/\">this Article</a>. You need to shutdown the server, remove the InnoDB log files, set the new value in my.cnf, start the server, then check the error logs if everything went fine. See also <a href=\"http://mysqldatabaseadministration.blogspot.com/2007/01/increase-innodblogfilesize-proper-way.html\">this blog entry</a>');
printf(__('Your absolute InnoD log size is %s MiB'), 0);

echo __('InnoDB buffer pool size');
echo __('Your InnoDB buffer pool is fairly small.');
echo __('The InnoDB buffer pool has a profound impact on perfomance for InnoDB tables. Assign all your remaining memory to this buffer. For database servers that use solely InnoDB as storage engine and have no other services (e.g. a web server) running, you may set this as high as 80% of your available memory. If that is not the case, you need to carefully assess the memory consumption of your other services and non-InnoDB-Tables and set this variable accordingly. If it is set too high, your system will start swapping, which decreases performance significantly. See also <a href=\"http://www.mysqlperformanceblog.com/2007/11/03/choosing-innodb_buffer_pool_size/\">this article</a>');
echo __('You are currently using %s% of your memory for the InnoDB buffer pool. This rule fires if you are assigning less than 60%, however this might be perfectly adequate for your system if you don\'t have much InnoDB tables or other services running on the same machine.');

echo __('MyISAM concurrent inserts');
echo __('Enable concurrent_insert by setting it to 1');
echo __('Setting {concurrent_insert} to 1 reduces contention between readers and writers for a given table. See also <a href=\"http://dev.mysql.com/doc/refman/5.0/en/concurrent-inserts.html\">MySQL Documentation</a>');
echo __('concurrent_insert is set to 0');
