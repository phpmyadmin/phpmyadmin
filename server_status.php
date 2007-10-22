<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays status variables with descriptions and some hints an optmizing
 *  + reset status variables
 *
 * @version $Id$
 */

/**
 *
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}
require_once './libraries/common.inc.php';

/**
 * Does the common work
 */
require './libraries/server_common.inc.php';


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';


/**
 * Displays the sub-page heading
 */
echo '<div id="serverstatus">' . "\n";
echo '<h2>' . "\n"
   . ($GLOBALS['cfg']['MainPageIconic']
       ? '<img class="icon" src="' . $GLOBALS['pmaThemeImage'] .
         's_status.png" width="16" height="16" alt="" />'
       : '')
   . $strServerStatus . "\n"
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
if (PMA_MYSQL_INT_VERSION >= 50002) {
    $server_status = PMA_DBI_fetch_result('SHOW GLOBAL STATUS', 0, 1);
} else {
    $server_status = PMA_DBI_fetch_result('SHOW STATUS', 0, 1);
}


/**
 * for some calculations we require also some server settings
 */
if (PMA_MYSQL_INT_VERSION >= 40003) {
    $server_variables = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES', 0, 1);
} else {
    $server_variables = PMA_DBI_fetch_result('SHOW VARIABLES', 0, 1);
}


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
    'Innodb_'           => 'innodb',
    'Ndb_'              => 'ndb',
    'Ssl_'              => 'ssl',
    'Handler_'          => 'handler',
    'Qcache_'           => 'qcache',
    'Threads_'          => 'threads',
    'Slow_launch_threads' => 'threads',

    'Binlog_cache_'     => 'binlog_cache',
    'Created_tmp_'      => 'created_tmp',
    'Key_'              => 'key',

    'Delayed_'          => 'delayed',
    'Not_flushed_delayed_rows' => 'delayed',

    'Flush_commands'    => 'query',
    'Last_query_cost'   => 'query',
    'Slow_queries'      => 'query',

    'Select_'           => 'select',
    'Sort_'             => 'sort',

    'Open_tables'       => 'table',
    'Opened_tables'     => 'table',
    'Table_locks_'      => 'table',

    'Rpl_status'        => 'repl',
    'Slave_'            => 'repl',

    'Tc_'               => 'tc',
);

$sections = array(
    // section => section name (description)
    'com'           => array('title' => ''),
    'query'         => array('title' => $strSQLQuery),
    'innodb'        => array('title' => 'InnoDB'),
    'ndb'           => array('title' => 'NDB'),
    'ssl'           => array('title' => 'SSL'),
    'handler'       => array('title' => $strHandler),
    'qcache'        => array('title' => $strQueryCache),
    'threads'       => array('title' => $strThreads),
    'binlog_cache'  => array('title' => $strBinaryLog),
    'created_tmp'   => array('title' => $strTempData),
    'delayed'       => array('title' => $strServerStatusDelayedInserts),
    'key'           => array('title' => $strKeyCache),
    'select'        => array('title' => $strJoins),
    'repl'          => array('title' => $strReplication),
    'sort'          => array('title' => $strSorting),
    'table'         => array('title' => $strNumTables),
    'tc'            => array('title' => $strTransactionCoordinator),
);


/**
 * define some needfull links/commands
 */
// variable or section name => (name => url)
$links = array();

$links['table'][$strFlushTables]
    = $PMA_PHP_SELF . '?flush=TABLES&amp;' . PMA_generate_common_url();
$links['table'][$strShowOpenTables]
    = 'sql.php?sql_query=' . urlencode('SHOW OPEN TABLES') .
      '&amp;goto=server_status.php&amp;' . PMA_generate_common_url();

$links['repl'][$strShowSlaveHosts]
    = 'sql.php?sql_query=' . urlencode('SHOW SLAVE HOSTS') .
      '&amp;goto=server_status.php&amp;' . PMA_generate_common_url();
$links['repl'][$strShowSlaveStatus]
    = 'sql.php?sql_query=' . urlencode('SHOW SLAVE STATUS') .
      '&amp;goto=server_status.php&amp;' . PMA_generate_common_url();
$links['repl']['doc'] = 'replication';

$links['qcache'][$strFlushQueryCache]
    = $PMA_PHP_SELF . '?flush=' . urlencode('QUERY CACHE') . '&amp;' .
      PMA_generate_common_url();
$links['qcache']['doc'] = 'query_cache';

$links['threads'][$strMySQLShowProcess]
    = 'server_processlist.php?' . PMA_generate_common_url();
$links['threads']['doc'] = 'mysql_threads';

$links['key']['doc'] = 'myisam_key_cache';

$links['binlog_cache']['doc'] = 'binary_log';

$links['Slow_queries']['doc'] = 'slow_query_log';

$links['innodb'][$strServerTabVariables]
    = 'server_engines.php?engine=InnoDB&amp;' . PMA_generate_common_url();
$links['innodb'][$strInnodbStat]
    = 'server_engines.php?engine=InnoDB&amp;page=Status&amp;' .
      PMA_generate_common_url();
$links['innodb']['doc'] = 'innodb';


// sort status vars into arrays
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
<div id="statuslinks">
    <a href="<?php echo
        $PMA_PHP_SELF . '?' . PMA_generate_common_url(); ?>"
       ><?php echo $strRefresh; ?></a>
    <a href="<?php echo
        $PMA_PHP_SELF . '?flush=STATUS&amp;' . PMA_generate_common_url(); ?>"
       ><?php echo $strShowStatusReset; ?></a>
       <?php echo PMA_showMySQLDocu('server_status_variables','server_status_variables'); ?>
</div>

<p>
<?php
echo sprintf($strServerStatusUptime,
    PMA_timespanFormat($server_status['Uptime']),
    PMA_localisedDate($start_time)) . "\n";
?>
</p>

<div id="sectionlinks">
<?php
foreach ($sections as $section_name => $section) {
    if (! empty($section['vars']) && ! empty($section['title'])) {
        echo '<a href="' . $PMA_PHP_SELF . '?' .
             PMA_generate_common_url() . '#' . $section_name . '">' .
             $section['title'] . '</a>' . "\n";
    }
}
?>
</div>

<h3><?php echo $strServerTrafficNotes; ?></h3>

<table id="serverstatustraffic" class="data">
<thead>
<tr>
    <th colspan="2"><?php echo $strTraffic . '&nbsp;' . PMA_showHint($strStatisticsOverrun); ?></th>
    <th>&oslash; <?php echo $strPerHour; ?></th>
</tr>
</thead>
<tbody>
<tr class="odd">
    <th class="name"><?php echo $strReceived; ?></th>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown($server_status['Bytes_received'], 4)); ?></td>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                $server_status['Bytes_received'] * $hour_factor, 4)); ?></td>
</tr>
<tr class="even">
    <th class="name"><?php echo $strSent; ?></th>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown($server_status['Bytes_sent'], 4)); ?></td>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                $server_status['Bytes_sent'] * $hour_factor, 4)); ?></td>
</tr>
<tr class="odd">
    <th class="name"><?php echo $strTotalUC; ?></th>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                $server_status['Bytes_received'] + $server_status['Bytes_sent'], 4)
        ); ?></td>
    <td class="value"><?php echo
        implode(' ',
            PMA_formatByteDown(
                ($server_status['Bytes_received'] + $server_status['Bytes_sent'])
                * $hour_factor, 4)
        ); ?></td>
</tr>
</tbody>
</table>

<table id="serverstatusconnections" class="data">
<thead>
<tr>
    <th colspan="2"><?php echo $strConnections; ?></th>
    <th>&oslash; <?php echo $strPerHour; ?></th>
    <th>%</th>
</tr>
</thead>
<tbody>
<tr class="odd">
    <th class="name"><?php echo $strMaxConnects; ?></th>
    <td class="value"><?php echo
        PMA_formatNumber($server_status['Max_used_connections'], 0); ?>  </td>
    <td class="value">--- </td>
    <td class="value">--- </td>
</tr>
<tr class="even">
    <th class="name"><?php echo $strFailedAttempts; ?></th>
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
<tr class="odd">
    <th class="name"><?php echo $strAbortedClients; ?></th>
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
<tr class="even">
    <th class="name"><?php echo $strTotalUC; ?></th>
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

<hr class="clearfloat" />

<h3><?php echo
    sprintf($strQueryStatistics,
        PMA_formatNumber($server_status['Questions'], 0)); ?></h3>

<table id="serverstatusqueriessummary" class="data">
<thead>
<tr>
    <th><?php echo $strTotalUC; ?></th>
    <th>&oslash; <?php echo $strPerHour; ?></th>
    <th>&oslash; <?php echo $strPerMinute; ?></th>
    <th>&oslash; <?php echo $strPerSecond; ?></th>
</tr>
</thead>
<tbody>
<tr class="odd">
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

<div id="serverstatusqueriesdetails">
<?php
// number of tables to split values into
$tables         = 2;
$rows_per_table = (int) ceil(count($sections['com']['vars']) / $tables);
$current_table  = 0;
$odd_row        = true;
$countRows      = 0;
$perc_factor    = 100 / ($server_status['Questions'] - $server_status['Connections']);
foreach ($sections['com']['vars'] as $name => $value) {
    $current_table++;
    if ($countRows === 0 || $countRows === $rows_per_table) {
        $odd_row = true;
        if ($countRows === $rows_per_table) {
            echo '    </tbody>' . "\n";
            echo '    </table>' . "\n";
        }
?>
    <table id="serverstatusqueriesdetails<?php echo $current_table; ?>" class="data">
    <col class="namecol" />
    <col class="valuecol" span="3" />
    <thead>
        <tr><th colspan="2"><?php echo $strQueryType; ?></th>
            <th>&oslash; <?php echo $strPerHour; ?></th>
            <th>%</th>
        </tr>
    </thead>
    <tbody>
<?php
    } else {
        $odd_row = !$odd_row;
    }
    $countRows++;

// For the percentage column, use Questions - Connections, because
// the number of connections is not an item of the Query types
// but is included in Questions. Then the total of the percentages is 100.
    $name = str_replace('Com_', '', $name);
    $name = str_replace('_', ' ', $name);
?>
        <tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
            <th class="name"><?php echo htmlspecialchars($name); ?></th>
            <td class="value"><?php echo PMA_formatNumber($value, 4, 0); ?></td>
            <td class="value"><?php echo
                PMA_formatNumber($value * $hour_factor, 4, 2); ?></td>
            <td class="value"><?php echo
                PMA_formatNumber($value * $perc_factor, 0, 2); ?>%</td>
        </tr>
<?php
}
?>
    </tbody>
    </table>
</div>

<div id="serverstatussection">
<?php
//Unset used variables
unset(
    $tables, $rows_per_table, $current_table, $countRows, $perc_factor,
    $hour_factor, $sections['com'],
    $server_status['Aborted_clients'], $server_status['Aborted_connects'],
    $server_status['Max_used_connections'], $server_status['Bytes_received'],
    $server_status['Bytes_sent'], $server_status['Connections'],
    $server_status['Questions'], $server_status['Uptime']
);

foreach ($sections as $section_name => $section) {
    if (! empty($section['vars'])) {
?>
    <table class="data" id="serverstatussection<?php echo $section_name; ?>">
    <caption class="tblHeaders">
        <a class="top"
           href="<?php echo $PMA_PHP_SELF . '?' .
                 PMA_generate_common_url() . '#serverstatus'; ?>"
           name="<?php echo $section_name; ?>"><?php echo $strPos1; ?>
            <?php echo
                ($GLOBALS['cfg']['MainPageIconic']
              ? '<img src="' . $GLOBALS['pmaThemeImage'] .
                's_asc.png" width="11" height="9" align="middle" alt="" />'
              : ''); ?>
        </a>
<?php
if (! empty($section['title'])) {
    echo $section['title'];
}
?>
    </caption>
    <col class="namecol" />
    <col class="valuecol" />
    <col class="descrcol" />
    <thead>
        <tr>
            <th><?php echo $strVar; ?></th>
            <th><?php echo $strValue; ?></th>
            <th><?php echo $strDescription; ?></th>
        </tr>
    </thead>
<?php
        if (! empty($links[$section_name])) {
?>
    <tfoot>
        <tr class="tblFooters">
            <th colspan="3" class="tblFooters">
<?php
            foreach ($links[$section_name] as $link_name => $link_url) {
                if ('doc' == $link_name) {
                    echo PMA_showMySQLDocu($link_url, $link_url);
                } else {
                    echo '<a href="' . $link_url . '">' . $link_name . '</a>' . "\n";
                }
            }
            unset($link_url, $link_name);
?>
            </th>
        </tr>
    </tfoot>
<?php
        }
?>
    <tbody>
<?php
        $odd_row = false;
        foreach ($section['vars'] as $name => $value) {
            $odd_row = !$odd_row;
?>
        <tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
            <th class="name"><?php echo htmlspecialchars($name); ?></th>
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
            } elseif (is_numeric($value) && $value == (int) $value) {
                echo PMA_formatNumber($value, 4, 0);
            } elseif (is_numeric($value)) {
                echo PMA_formatNumber($value, 4, 2);
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
        }
        unset($name, $value);
?>
    </tbody>
    </table>
<?php
    }
}
unset($section_name, $section, $sections, $server_status, $odd_row, $alerts);
?>
</div>
</div>
<?php


/**
 * Sends the footer
 */
require_once './libraries/footer.inc.php';
?>
