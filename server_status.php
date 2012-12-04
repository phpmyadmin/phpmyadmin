<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays status variables with descriptions and some hints an optmizing
 *  + reset status variables
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/ServerStatusData.class.php';

/**
 * Ajax request
 */

if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');

    // real-time charting data
    if (isset($_REQUEST['chart_data'])) {
        switch($_REQUEST['type']) {
        // Process and Connections realtime chart
        case 'proc':
            $c = PMA_DBI_fetch_result(
                "SHOW GLOBAL STATUS WHERE Variable_name = 'Connections'", 0, 1
            );
            $result = PMA_DBI_query('SHOW PROCESSLIST');
            $num_procs = PMA_DBI_num_rows($result);

            $ret = array(
                'x'      => microtime(true) * 1000,
                'y_proc' => $num_procs,
                'y_conn' => $c['Connections']
            );

            exit(json_encode($ret));

        case 'queries': // Query realtime chart
            if (PMA_DRIZZLE) {
                $sql = "SELECT concat('Com_', variable_name), variable_value
                    FROM data_dictionary.GLOBAL_STATEMENTS
                    WHERE variable_value > 0
                      UNION
                    SELECT variable_name, variable_value
                    FROM data_dictionary.GLOBAL_STATUS
                    WHERE variable_name = 'Questions'";
                $queries = PMA_DBI_fetch_result($sql, 0, 1);
            } else {
                $queries = PMA_DBI_fetch_result(
                    "SHOW GLOBAL STATUS
                    WHERE
                        (Variable_name LIKE 'Com_%' OR Variable_name = 'Questions')
                        AND Value > 0", 0, 1
                );
            }
            cleanDeprecated($queries);
            // admin commands are not queries
            unset($queries['Com_admin_commands']);
            $questions = $queries['Questions'];
            unset($queries['Questions']);

            //$sum=array_sum($queries);
            $ret = array(
                'x'         => microtime(true) * 1000,
                'y'         => $questions,
                'pointInfo' => $queries
            );

            exit(json_encode($ret));

        case 'traffic': // Traffic realtime chart
            $traffic = PMA_DBI_fetch_result(
                "SHOW GLOBAL STATUS
                WHERE Variable_name = 'Bytes_received'
                    OR Variable_name = 'Bytes_sent'", 0, 1
            );

            $ret = array(
                'x'          => microtime(true) * 1000,
                'y_sent'     => $traffic['Bytes_sent'],
                'y_received' => $traffic['Bytes_received']
            );

            exit(json_encode($ret));

        case 'chartgrid': // Data for the monitor
            $ret = json_decode($_REQUEST['requiredData'], true);
            $statusVars = array();
            $serverVars = array();
            $sysinfo = $cpuload = $memory = 0;
            $pName = '';

            /* Accumulate all required variables and data */
            // For each chart
            foreach ($ret as $chart_id => $chartNodes) {
                // For each data series
                foreach ($chartNodes as $node_id => $nodeDataPoints) {
                    // For each data point in the series (usually just 1)
                    foreach ($nodeDataPoints as $point_id => $dataPoint) {
                        $pName = $dataPoint['name'];

                        switch ($dataPoint['type']) {
                        /* We only collect the status and server variables here to
                         * read them all in one query,
                         * and only afterwards assign them.
                         * Also do some white list filtering on the names
                        */
                        case 'servervar':
                            if (! preg_match('/[^a-zA-Z_]+/', $pName)) {
                                $serverVars[] = $pName;
                            }
                            break;

                        case 'statusvar':
                            if (! preg_match('/[^a-zA-Z_]+/', $pName)) {
                                $statusVars[] = $pName;
                            }
                            break;

                        case 'proc':
                            $result = PMA_DBI_query('SHOW PROCESSLIST');
                            $ret[$chart_id][$node_id][$point_id]['value']
                                = PMA_DBI_num_rows($result);
                            break;

                        case 'cpu':
                            if (!$sysinfo) {
                                include_once 'libraries/sysinfo.lib.php';
                                $sysinfo = PMA_getSysInfo();
                            }
                            if (!$cpuload) {
                                $cpuload = $sysinfo->loadavg();
                            }

                            if (PMA_getSysInfoOs() == 'Linux') {
                                $ret[$chart_id][$node_id][$point_id]['idle']
                                    = $cpuload['idle'];
                                $ret[$chart_id][$node_id][$point_id]['busy']
                                    = $cpuload['busy'];
                            } else {
                                $ret[$chart_id][$node_id][$point_id]['value']
                                    = $cpuload['loadavg'];
                            }

                            break;

                        case 'memory':
                            if (!$sysinfo) {
                                include_once 'libraries/sysinfo.lib.php';
                                $sysinfo = PMA_getSysInfo();
                            }
                            if (!$memory) {
                                $memory  = $sysinfo->memory();
                            }

                            $ret[$chart_id][$node_id][$point_id]['value']
                                = $memory[$pName];
                            break;
                        } /* switch */
                    } /* foreach */
                } /* foreach */
            } /* foreach */

            // Retrieve all required status variables
            if (count($statusVars)) {
                $statusVarValues = PMA_DBI_fetch_result(
                    "SHOW GLOBAL STATUS WHERE Variable_name='"
                    . implode("' OR Variable_name='", $statusVars) . "'",
                    0,
                    1
                );
            } else {
                $statusVarValues = array();
            }

            // Retrieve all required server variables
            if (count($serverVars)) {
                $serverVarValues = PMA_DBI_fetch_result(
                    "SHOW GLOBAL VARIABLES WHERE Variable_name='"
                    . implode("' OR Variable_name='", $serverVars) . "'",
                    0,
                    1
                );
            } else {
                $serverVarValues = array();
            }

            // ...and now assign them
            foreach ($ret as $chart_id => $chartNodes) {
                foreach ($chartNodes as $node_id => $nodeDataPoints) {
                    foreach ($nodeDataPoints as $point_id => $dataPoint) {
                        switch($dataPoint['type']) {
                        case 'statusvar':
                            $ret[$chart_id][$node_id][$point_id]['value']
                                = $statusVarValues[$dataPoint['name']];
                            break;
                        case 'servervar':
                            $ret[$chart_id][$node_id][$point_id]['value']
                                = $serverVarValues[$dataPoint['name']];
                            break;
                        }
                    }
                }
            }

            $ret['x'] = microtime(true) * 1000;

            exit(json_encode($ret));
        }
    }

    if (isset($_REQUEST['log_data'])) {
        if (PMA_MYSQL_INT_VERSION < 50106) {
            // Table logging is only available since 5.1.6
            exit('""');
        }

        $start = intval($_REQUEST['time_start']);
        $end = intval($_REQUEST['time_end']);

        if ($_REQUEST['type'] == 'slow') {
            $q = 'SELECT start_time, user_host, ';
            $q .= 'Sec_to_Time(Sum(Time_to_Sec(query_time))) as query_time, ';
            $q .= 'Sec_to_Time(Sum(Time_to_Sec(lock_time))) as lock_time, ';
            $q .= 'SUM(rows_sent) AS rows_sent, ';
            $q .= 'SUM(rows_examined) AS rows_examined, db, sql_text, ';
            $q .= 'COUNT(sql_text) AS \'#\' ';
            $q .= 'FROM `mysql`.`slow_log` ';
            $q .= 'WHERE start_time > FROM_UNIXTIME(' . $start . ') ';
            $q .= 'AND start_time < FROM_UNIXTIME(' . $end . ') GROUP BY sql_text';

            $result = PMA_DBI_try_query($q);

            $return = array('rows' => array(), 'sum' => array());
            $type = '';

            while ($row = PMA_DBI_fetch_assoc($result)) {
                $type = strtolower(
                    substr($row['sql_text'], 0, strpos($row['sql_text'], ' '))
                );

                switch($type) {
                case 'insert':
                case 'update':
                    //Cut off big inserts and updates, but append byte count instead
                    if (strlen($row['sql_text']) > 220) {
                        $implode_sql_text = implode(
                            ' ',
                            PMA_Util::formatByteDown(
                                strlen($row['sql_text']), 2, 2
                            )
                        );
                        $row['sql_text'] = substr($row['sql_text'], 0, 200)
                            . '... [' . $implode_sql_text . ']';
                    }
                    break;
                default:
                    break;
                }

                if (! isset($return['sum'][$type])) {
                    $return['sum'][$type] = 0;
                }
                $return['sum'][$type] += $row['#'];
                $return['rows'][] = $row;
            }

            $return['sum']['TOTAL'] = array_sum($return['sum']);
            $return['numRows'] = count($return['rows']);

            PMA_DBI_free_result($result);

            exit(json_encode($return));
        }

        if ($_REQUEST['type'] == 'general') {
            $limitTypes = '';
            if (isset($_REQUEST['limitTypes']) && $_REQUEST['limitTypes']) {
                $limitTypes
                    = 'AND argument REGEXP \'^(INSERT|SELECT|UPDATE|DELETE)\' ';
            }

            $q = 'SELECT TIME(event_time) as event_time, user_host, thread_id, ';
            $q .= 'server_id, argument, count(argument) as \'#\' ';
            $q .= 'FROM `mysql`.`general_log` ';
            $q .= 'WHERE command_type=\'Query\' ';
            $q .= 'AND event_time > FROM_UNIXTIME(' . $start . ') ';
            $q .= 'AND event_time < FROM_UNIXTIME(' . $end . ') ';
            $q .= $limitTypes . 'GROUP by argument'; // HAVING count > 1';

            $result = PMA_DBI_try_query($q);

            $return = array('rows' => array(), 'sum' => array());
            $type = '';
            $insertTables = array();
            $insertTablesFirst = -1;
            $i = 0;
            $removeVars = isset($_REQUEST['removeVariables'])
                && $_REQUEST['removeVariables'];

            while ($row = PMA_DBI_fetch_assoc($result)) {
                preg_match('/^(\w+)\s/', $row['argument'], $match);
                $type = strtolower($match[1]);

                if (! isset($return['sum'][$type])) {
                    $return['sum'][$type] = 0;
                }
                $return['sum'][$type] += $row['#'];

                switch($type) {
                case 'insert':
                    // Group inserts if selected
                    if ($removeVars
                        && preg_match(
                            '/^INSERT INTO (`|\'|"|)([^\s\\1]+)\\1/i',
                            $row['argument'], $matches
                        )
                    ) {
                        $insertTables[$matches[2]]++;
                        if ($insertTables[$matches[2]] > 1) {
                            $return['rows'][$insertTablesFirst]['#']
                                = $insertTables[$matches[2]];

                            // Add a ... to the end of this query to indicate that
                            // there's been other queries
                            $temp = $return['rows'][$insertTablesFirst]['argument'];
                            if ($temp[strlen($temp) - 1] != '.') {
                                $return['rows'][$insertTablesFirst]['argument']
                                    .= '<br/>...';
                            }

                            // Group this value, thus do not add to the result list
                            continue 2;
                        } else {
                            $insertTablesFirst = $i;
                            $insertTables[$matches[2]] += $row['#'] - 1;
                        }
                    }
                    // No break here

                case 'update':
                    // Cut off big inserts and updates,
                    // but append byte count therefor
                    if (strlen($row['argument']) > 220) {
                        $row['argument'] = substr($row['argument'], 0, 200)
                            . '... ['
                            .  implode(
                                ' ',
                                PMA_Util::formatByteDown(
                                    strlen($row['argument'])
                                ),
                                2,
                                2
                            )
                            . ']';
                    }
                    break;

                default:
                    break;
                }

                $return['rows'][] = $row;
                $i++;
            }

            $return['sum']['TOTAL'] = array_sum($return['sum']);
            $return['numRows'] = count($return['rows']);

            PMA_DBI_free_result($result);

            exit(json_encode($return));
        }
    }

    if (isset($_REQUEST['logging_vars'])) {
        if (isset($_REQUEST['varName']) && isset($_REQUEST['varValue'])) {
            $value = PMA_Util::sqlAddSlashes($_REQUEST['varValue']);
            if (! is_numeric($value)) {
                $value="'" . $value . "'";
            }

            if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])) {
                PMA_DBI_query(
                    'SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value
                );
            }

        }

        $loggingVars = PMA_DBI_fetch_result(
            'SHOW GLOBAL VARIABLES WHERE Variable_name IN'
            . ' ("general_log","slow_query_log","long_query_time","log_output")',
            0,
            1
        );
        exit(json_encode($loggingVars));
    }

    if (isset($_REQUEST['query_analyzer'])) {
        $return = array();

        if (strlen($_REQUEST['database'])) {
            PMA_DBI_select_db($_REQUEST['database']);
        }

        if ($profiling = PMA_Util::profilingSupported()) {
            PMA_DBI_query('SET PROFILING=1;');
        }

        // Do not cache query
        $query = preg_replace(
            '/^(\s*SELECT)/i',
            '\\1 SQL_NO_CACHE',
            $_REQUEST['query']
        );

        $result = PMA_DBI_try_query($query);
        $return['affectedRows'] = $GLOBALS['cached_affected_rows'];

        $result = PMA_DBI_try_query('EXPLAIN ' . $query);
        while ($row = PMA_DBI_fetch_assoc($result)) {
            $return['explain'][] = $row;
        }

        // In case an error happened
        $return['error'] = PMA_DBI_getError();

        PMA_DBI_free_result($result);

        if ($profiling) {
            $return['profiling'] = array();
            $result = PMA_DBI_try_query(
                'SELECT seq,state,duration FROM INFORMATION_SCHEMA.PROFILING'
                . ' WHERE QUERY_ID=1 ORDER BY seq'
            );
            while ($row = PMA_DBI_fetch_assoc($result)) {
                $return['profiling'][]= $row;
            }
            PMA_DBI_free_result($result);
        }

        exit(json_encode($return));
    }
}


/**
 * Replication library
 */
if (PMA_DRIZZLE) {
    $server_master_status = false;
    $server_slave_status = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

/**
 * JS Includes
 */
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();

$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('server_status.js');
$scripts->addFile('jquery/jquery-ui-1.8.16.custom.js');

/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $scripts->addFile('jqplot/excanvas.js');
}

$scripts->addFile('canvg/canvg.js');
// for charting
$scripts->addFile('jqplot/jquery.jqplot.js');
$scripts->addFile('jqplot/plugins/jqplot.pieRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasTextRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.canvasAxisLabelRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.dateAxisRenderer.js');
$scripts->addFile('jqplot/plugins/jqplot.highlighter.js');
$scripts->addFile('jqplot/plugins/jqplot.cursor.js');
$scripts->addFile('date.js');

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
 * Kills a selected process
 */
if (! empty($_REQUEST['kill'])) {
    if (PMA_DBI_try_query('KILL ' . $_REQUEST['kill'] . ';')) {
        $message = PMA_Message::success(__('Thread %s was successfully killed.'));
    } else {
        $message = PMA_Message::error(
            __(
                'phpMyAdmin was unable to kill thread %s.'
                . ' It probably has already been closed.'
            )
        );
    }
    $message->addParam($_REQUEST['kill']);
    //$message->display();
}



/**
 * get status from server
 */
$server_status = PMA_DBI_fetch_result('SHOW GLOBAL STATUS', 0, 1);
if (PMA_DRIZZLE) {
    // Drizzle doesn't put query statistics into variables, add it
    $sql = "SELECT concat('Com_', variable_name), variable_value
        FROM data_dictionary.GLOBAL_STATEMENTS";
    $statements = PMA_DBI_fetch_result($sql, 0, 1);
    $server_status = array_merge($server_status, $statements);
}

/**
 * for some calculations we require also some server settings
 */
$server_variables = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES', 0, 1);

/**
 * cleanup of some deprecated values
 */
cleanDeprecated($server_status);

/**
 * calculate some values
 */
// Key_buffer_fraction
if (isset($server_status['Key_blocks_unused'])
    && isset($server_variables['key_cache_block_size'])
    && isset($server_variables['key_buffer_size'])
) {
    $server_status['Key_buffer_fraction_%']
        = 100
        - $server_status['Key_blocks_unused']
        * $server_variables['key_cache_block_size']
        / $server_variables['key_buffer_size']
        * 100;
} elseif (isset($server_status['Key_blocks_used'])
        && isset($server_variables['key_buffer_size'])) {
    $server_status['Key_buffer_fraction_%']
        = $server_status['Key_blocks_used']
        * 1024
        / $server_variables['key_buffer_size'];
}

// Ratio for key read/write
if (isset($server_status['Key_writes'])
    && isset($server_status['Key_write_requests'])
    && $server_status['Key_write_requests'] > 0
) {
    $server_status['Key_write_ratio_%']
        = 100 * $server_status['Key_writes'] / $server_status['Key_write_requests'];
}

if (isset($server_status['Key_reads'])
    && isset($server_status['Key_read_requests'])
    && $server_status['Key_read_requests'] > 0
) {
    $server_status['Key_read_ratio_%']
        = 100 * $server_status['Key_reads'] / $server_status['Key_read_requests'];
}

// Threads_cache_hitrate
if (isset($server_status['Threads_created'])
    && isset($server_status['Connections'])
    && $server_status['Connections'] > 0
) {

    $server_status['Threads_cache_hitrate_%']
        = 100 - $server_status['Threads_created']
        / $server_status['Connections'] * 100;
}

/**
 * split variables in sections
 */
$allocations = array(
    // variable name => section
    // variable names match when they begin with the given string

    'Com_'              => 'com',
    'Innodb_'           => 'innodb',
    'Ndb_'              => 'ndb',
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
    'Queries'           => 'query',
    'Prepared_stmt_count' => 'query',

    'Select_'           => 'select',
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
    'Opened_files'      => 'files',
);

$sections = array(
    // section => section name (description)
    'com'           => 'Com',
    'query'         => __('SQL query'),
    'innodb'        => 'InnoDB',
    'ndb'           => 'NDB',
    'handler'       => __('Handler'),
    'qcache'        => __('Query cache'),
    'threads'       => __('Threads'),
    'binlog_cache'  => __('Binary log'),
    'created_tmp'   => __('Temporary data'),
    'delayed'       => __('Delayed inserts'),
    'key'           => __('Key cache'),
    'select'        => __('Joins'),
    'repl'          => __('Replication'),
    'sort'          => __('Sorting'),
    'table'         => __('Tables'),
    'tc'            => __('Transaction coordinator'),
    'files'         => __('Files'),
    'ssl'           => 'SSL',
    'other'         => __('Other')
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

//$links['threads'][__('Show processes')]
//    = 'server_processlist.php?' . PMA_generate_common_url();
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


// Variable to contain all com_ variables (query statistics)
$used_queries = array();

// Variable to map variable names to their respective section name
// (used for js category filtering)
$allocationMap = array();

// Variable to mark used sections
$categoryUsed = array();

// sort vars into arrays
foreach ($server_status as $name => $value) {
    $section_found = false;
    foreach ($allocations as $filter => $section) {
        if (strpos($name, $filter) !== false) {
            $allocationMap[$name] = $section;
            $categoryUsed[$section] = true;
            $section_found = true;
            if ($section == 'com' && $value > 0) {
                $used_queries[$name] = $value;
            }
            break; // Only exits inner loop
        }
    }
    if (!$section_found) {
        $allocationMap[$name] = 'other';
        $categoryUsed['other'] = true;
    }
}

if (PMA_DRIZZLE) {
    $used_queries = PMA_DBI_fetch_result(
        'SELECT * FROM data_dictionary.global_statements',
        0,
        1
    );
    unset($used_queries['admin_commands']);
} else {
    // admin commands are not queries (e.g. they include COM_PING,
    // which is excluded from $server_status['Questions'])
    unset($used_queries['Com_admin_commands']);
}

/* Ajax request refresh */
if (isset($_REQUEST['show']) && isset($_REQUEST['ajax_request'])) {
    switch($_REQUEST['show']) {
    case 'query_statistics':
        printQueryStatistics();
        exit();
    case 'server_traffic':
        printServerTraffic();
        exit();
    case 'variables_table':
        // Prints the variables table
        printVariablesTable();
        exit();

    default:
        break;
    }
}

$server_db_isLocal = strtolower($cfg['Server']['host']) == 'localhost'
                              || $cfg['Server']['host'] == '127.0.0.1'
                              || $cfg['Server']['host'] == '::1';

$input = '<input type="hidden" name="%s" value="%s" />';

echo '<form id="js_data" class="hide">';
printf($input, 'pma_token', $_SESSION[' PMA_token ']);
printf($input, 'url_query', str_replace('&amp;', '&', PMA_generate_common_url($db)));
printf($input, 'server_time_diff', 'new Date().getTime() - ' . (microtime(true) * 1000));
printf($input, 'server_os', PHP_OS);
printf($input, 'is_superuser', PMA_isSuperuser());
printf($input, 'server_db_isLocal', $server_db_isLocal);
echo '</form>';

echo '<div id="profiling_docu" class="hide">';
echo PMA_Util::showMySQLDocu('general-thread-states', 'general-thread-states');
echo '</div>';
echo '<div id="explain_docu" class="hide">';
echo PMA_Util::showMySQLDocu('explain-output', 'explain-output');
echo '</div>';

/**
 * start output
 */

 /**
 * Does the common work
 */
require 'libraries/server_common.inc.php';

echo '<div id="serverstatus">';

echo PMA_ServerStatusData::getMenuHtml();

echo '<div id="serverStatusTabs">';
echo '<ul>';
echo '<li><a href="#statustabs_traffic">' . __('Server') . '</a></li>';
echo '<li class="jsfeature"><a href="#statustabs_charting">'
    . __('Monitor') . '</a></li>';
echo '</ul>';

echo '<div id="statustabs_charting" class="jsfeature">';
printMonitor();
echo '</div>';

echo '</div>';
echo '</div>';

/**
 * Prints server traffic information
 *
 * @return void
 */
function printServerTraffic()
{
    global $server_status, $PMA_PHP_SELF;
    global $server_master_status, $server_slave_status, $replication_types;

    $hour_factor    = 3600 / $server_status['Uptime'];

    /**
     * starttime calculation
     */
    $start_time = PMA_DBI_fetch_value(
        'SELECT UNIX_TIMESTAMP() - ' . $server_status['Uptime']
    );

    echo '<h3>';

    echo sprintf(
        __('Network traffic since startup: %s'),
        implode(
            ' ',
            PMA_Util::formatByteDown(
                $server_status['Bytes_received'] + $server_status['Bytes_sent'],
                3,
                1
            )
        )
    );
    echo '</h3>';

    echo '<p>';

    printf(
        __('This MySQL server has been running for %1$s. It started up on %2$s.'),
        PMA_Util::timespanFormat($server_status['Uptime']),
        PMA_Util::localisedDate($start_time)
    ) . "\n";

    echo '</p>';

    if ($server_master_status || $server_slave_status) {
        echo '<p class="notice">';
        if ($server_master_status && $server_slave_status) {
            echo __('This MySQL server works as <b>master</b> and <b>slave</b> in <b>replication</b> process.');
        } elseif ($server_master_status) {
            echo __('This MySQL server works as <b>master</b> in <b>replication</b> process.');
        } elseif ($server_slave_status) {
            echo __('This MySQL server works as <b>slave</b> in <b>replication</b> process.');
        }
        echo ' ';
        echo __('For further information about replication status on the server, please visit the <a href="#replication">replication section</a>.');
        echo '</p>';
    }

    /*
     * if the server works as master or slave in replication process,
     * display useful information
     */
    if ($server_master_status || $server_slave_status) {
        echo '<hr class="clearfloat" />';

        echo '<h3><a name="replication"></a>' . __('Replication status') . '</h3>';

        foreach ($replication_types as $type) {
            if (${"server_{$type}_status"}) {
                PMA_replication_print_status_table($type);
            }
        }
        unset($types);
    }
    ?>

    <table id="serverstatustraffic" class="data noclick">
    <thead>
    <tr>
        <th colspan="2"><?php echo __('Traffic') . '&nbsp;' . PMA_Util::showHint(__('On a busy server, the byte counters may overrun, so those statistics as reported by the MySQL server may be incorrect.')); ?></th>
        <th>&oslash; <?php echo __('per hour'); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr class="odd">
        <th class="name"><?php echo __('Received'); ?></th>
        <td class="value"><?php echo
            implode(
                ' ',
                PMA_Util::formatByteDown(
                    $server_status['Bytes_received'], 3, 1
                )
            ); ?></td>
        <td class="value"><?php echo
            implode(
                ' ',
                PMA_Util::formatByteDown(
                    $server_status['Bytes_received'] * $hour_factor, 3, 1
                )
            ); ?></td>
    </tr>
    <tr class="even">
        <th class="name"><?php echo __('Sent'); ?></th>
        <td class="value"><?php echo
            implode(
                ' ',
                PMA_Util::formatByteDown(
                    $server_status['Bytes_sent'], 3, 1
                )
            ); ?></td>
        <td class="value"><?php echo
            implode(
                ' ',
                PMA_Util::formatByteDown(
                    $server_status['Bytes_sent'] * $hour_factor, 3, 1
                )
            ); ?></td>
    </tr>
    <tr class="odd">
        <th class="name"><?php echo __('Total'); ?></th>
        <td class="value"><?php echo
            implode(
                ' ',
                PMA_Util::formatByteDown(
                    $server_status['Bytes_received'] + $server_status['Bytes_sent'], 3, 1
                )
            ); ?></td>
        <td class="value"><?php echo
            implode(
                ' ',
                PMA_Util::formatByteDown(
                    ($server_status['Bytes_received'] + $server_status['Bytes_sent'])
                    * $hour_factor, 3, 1
                )
            ); ?></td>
    </tr>
    </tbody>
    </table>

    <table id="serverstatusconnections" class="data noclick">
    <thead>
    <tr>
        <th colspan="2"><?php echo __('Connections'); ?></th>
        <th>&oslash; <?php echo __('per hour'); ?></th>
        <th>%</th>
    </tr>
    </thead>
    <tbody>
    <tr class="odd">
        <th class="name"><?php echo __('max. concurrent connections'); ?></th>
        <td class="value"><?php echo
            PMA_Util::formatNumber($server_status['Max_used_connections'], 0); ?>  </td>
        <td class="value">--- </td>
        <td class="value">--- </td>
    </tr>
    <tr class="even">
        <th class="name"><?php echo __('Failed attempts'); ?></th>
        <td class="value"><?php echo
            PMA_Util::formatNumber($server_status['Aborted_connects'], 4, 1, true); ?></td>
        <td class="value"><?php echo
            PMA_Util::formatNumber(
                $server_status['Aborted_connects'] * $hour_factor, 4, 2, true
            ); ?></td>
        <td class="value"><?php echo
            $server_status['Connections'] > 0
            ? PMA_Util::formatNumber(
                $server_status['Aborted_connects'] * 100 / $server_status['Connections'],
                0, 2, true
            ) . '%'
            : '--- '; ?></td>
    </tr>
    <tr class="odd">
        <th class="name"><?php echo __('Aborted'); ?></th>
        <td class="value"><?php echo
            PMA_Util::formatNumber($server_status['Aborted_clients'], 4, 1, true); ?></td>
        <td class="value"><?php echo
            PMA_Util::formatNumber(
                $server_status['Aborted_clients'] * $hour_factor, 4, 2, true
            ); ?></td>
        <td class="value"><?php echo
            $server_status['Connections'] > 0
            ? PMA_Util::formatNumber(
                $server_status['Aborted_clients'] * 100 / $server_status['Connections'],
                0, 2, true
            ) . '%'
            : '--- '; ?></td>
    </tr>
    <tr class="even">
        <th class="name"><?php echo __('Total'); ?></th>
        <td class="value"><?php echo
            PMA_Util::formatNumber($server_status['Connections'], 4, 0); ?></td>
        <td class="value"><?php echo
            PMA_Util::formatNumber(
                $server_status['Connections'] * $hour_factor, 4, 2
            ); ?></td>
        <td class="value"><?php echo
            PMA_Util::formatNumber(100, 0, 2); ?>%</td>
    </tr>
    </tbody>
    </table>
    <?php

    $url_params = array();

    $show_full_sql = ! empty($_REQUEST['full']);
    if ($show_full_sql) {
        $url_params['full'] = 1;
        $full_text_link = 'server_status.php' . PMA_generate_common_url(array(), 'html', '?');
    } else {
        $full_text_link = 'server_status.php' . PMA_generate_common_url(array('full' => 1));
    }

    // This array contains display name and real column name of each
    // sortable column in the table
    $sortable_columns = array(
        array(
            'column_name' => __('ID'),
            'order_by_field' => 'Id'
        ),
        array(
            'column_name' => __('User'),
            'order_by_field' => 'User'
        ),
        array(
            'column_name' => __('Host'),
            'order_by_field' => 'Host'
        ),
        array(
            'column_name' => __('Database'),
            'order_by_field' => 'db'
        ),
        array(
            'column_name' => __('Command'),
            'order_by_field' => 'Command'
        ),
        array(
            'column_name' => __('Time'),
            'order_by_field' => 'Time'
        ),
        array(
            'column_name' => __('Status'),
            'order_by_field' => 'State'
        ),
        array(
            'column_name' => __('SQL query'),
            'order_by_field' => 'Info'
        )
    );
    $sortable_columns_count = count($sortable_columns);

    if (PMA_DRIZZLE) {
        $sql_query = "SELECT
                p.id       AS Id,
                p.username AS User,
                p.host     AS Host,
                p.db       AS db,
                p.command  AS Command,
                p.time     AS Time,
                p.state    AS State,
                " . ($show_full_sql ? 's.query' : 'left(p.info, ' . (int)$GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] . ')') . " AS Info
            FROM data_dictionary.PROCESSLIST p
                " . ($show_full_sql ? 'LEFT JOIN data_dictionary.SESSIONS s ON s.session_id = p.id' : '');
        if (!empty($_REQUEST['order_by_field'])
            && !empty($_REQUEST['sort_order'])
        ) {
            $sql_query .= ' ORDER BY p.' . $_REQUEST['order_by_field'] . ' ' . $_REQUEST['sort_order'];
        }
    } else {
        $sql_query = $show_full_sql
            ? 'SHOW FULL PROCESSLIST'
            : 'SHOW PROCESSLIST';
        if (!empty($_REQUEST['order_by_field'])
            && !empty($_REQUEST['sort_order'])
        ) {
            $sql_query = 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ORDER BY `'
                . $_REQUEST['order_by_field'] . '` ' . $_REQUEST['sort_order'];
        }
    }

    $result = PMA_DBI_query($sql_query);

    /**
     * Displays the page
     */
    echo '<table id="tableprocesslist" class="data clearfloat noclick sortable">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . __('Processes') . '</th>';

    foreach ($sortable_columns as $column) {

        $is_sorted = !empty($_REQUEST['order_by_field'])
            && !empty($_REQUEST['sort_order'])
            && ($_REQUEST['order_by_field'] == $column['order_by_field']);

        $column['sort_order'] = ($is_sorted
            && ($_REQUEST['sort_order'] == 'ASC'))
            ? 'DESC'
            : 'ASC';

        if ($is_sorted) {
            if ($_REQUEST['sort_order'] == 'ASC') {
               $asc_display_style = 'inline';
               $desc_display_style = 'none';
            } elseif ($_REQUEST['sort_order'] == 'DESC') {
                $desc_display_style = 'inline';
                $asc_display_style = 'none';
            }
        }

        echo '<th>';
        echo '<a href="server_status.php' . PMA_generate_common_url($column) . '" ';
        if ($is_sorted) {
            echo 'onmouseout="$(\'.soimg\').toggle()" '
                . 'onmouseover="$(\'.soimg\').toggle()"';
        }
        echo '>';

        echo $column['column_name'];

        if ($is_sorted) {
            echo '<img class="icon ic_s_desc soimg" alt="'
                . __('Descending') . '" title="" src="themes/dot.gif" '
                . 'style="display: ' . $desc_display_style . '" />';
            echo '<img class="icon ic_s_asc soimg hide" alt="'
                . __('Ascending') . '" title="" src="themes/dot.gif" '
                . 'style="display: ' . $asc_display_style . '" />';
        }

        echo '</a>';

        if (! PMA_DRIZZLE && (0 === --$sortable_columns_count)) {
            echo '<a href="' . $full_text_link . '" title="';
            if ($show_full_sql) {
                echo __('Truncate Shown Queries');
            } else {
                echo __('Show Full Queries');
            }
            echo '">';
            echo '<img src="' . $GLOBALS['pmaThemeImage']
                . 's_' . ($show_full_sql ? 'partial' : 'full') . 'text.png" '
                . 'alt="';
            if ($show_full_sql) {
                echo __('Truncate Shown Queries');
            } else {
                echo __('Show Full Queries');
            }
            echo '">';
            echo '</a>';
        }

        echo '</th>';
    }

    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    $odd_row = true;
    while ($process = PMA_DBI_fetch_assoc($result)) {

        // Array keys need to modify due to the way it has used
        // to display column values
        if (!empty($_REQUEST['order_by_field'])
            && !empty($_REQUEST['sort_order'])
        ) {
            foreach (array_keys($process) as $key) {
                $new_key = ucfirst(strtolower($key));
                $process[$new_key] = $process[$key];
                unset($process[$key]);
            }
        }

        $url_params['kill'] = $process['Id'];
        $kill_process = 'server_status.php' . PMA_generate_common_url($url_params);
        ?>
    <tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
        <td><a href="<?php echo $kill_process ; ?>"><?php echo __('Kill'); ?></a></td>
        <td class="value"><?php echo $process['Id']; ?></td>
        <td><?php echo $process['User']; ?></td>
        <td><?php echo $process['Host']; ?></td>
        <td><?php echo ((! isset($process['db']) || ! strlen($process['db'])) ? '<i>' . __('None') . '</i>' : $process['db']); ?></td>
        <td><?php echo $process['Command']; ?></td>
        <td class="value"><?php echo $process['Time']; ?></td>
        <td><?php echo (empty($process['State']) ? '---' : $process['State']); ?></td>
        <td>
        <?php
        if (empty($process['Info'])) {
            echo '---';
        } else {
            if (!$show_full_sql && strlen($process['Info']) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
                echo htmlspecialchars(substr($process['Info'], 0, $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'])) . '[...]';
            } else {
                echo PMA_SQP_formatHtml(PMA_SQP_parse($process['Info']));
            }
        }
        ?>
        </td>
    </tr>
        <?php
        $odd_row = ! $odd_row;
    }
    ?>
    </tbody>
    </table>
    <?php
}

/**
 * Prints html with monitor
 *
 * @return void
 */
function printMonitor()
{
    global $server_status, $server_db_isLocal;

?>
    <div class="tabLinks" style="display:none;">
        <a href="#pauseCharts">
            <?php echo PMA_Util::getImage('play.png'); ?>
            <?php echo __('Start Monitor'); ?>
        </a>
        <a href="#settingsPopup" class="popupLink" style="display:none;">
            <?php echo PMA_Util::getImage('s_cog.png'); ?>
            <?php echo __('Settings'); ?>
        </a>
        <?php
        if (! PMA_DRIZZLE) {
            ?>
        <a href="#monitorInstructionsDialog">
            <?php echo PMA_Util::getImage('b_help.png'); ?>
            <?php echo __('Instructions/Setup'); ?>
        </a>
        <?php
        }
        ?>
        <a href="#endChartEditMode" style="display:none;">
            <?php echo PMA_Util::getImage('s_okay.png'); ?>
            <?php echo __('Done rearranging/editing charts'); ?>
        </a>
    </div>

    <div class="popupContent settingsPopup">
        <a href="#addNewChart">
            <?php echo PMA_Util::getImage('b_chart.png'); ?>
            <?php echo __('Add chart'); ?>
        </a>
        <a href="#rearrangeCharts"><?php echo PMA_Util::getImage('b_tblops.png'); ?><?php echo __('Rearrange/edit charts'); ?></a>
        <div class="clearfloat paddingtop"></div>
        <div class="floatleft">
            <?php
            echo __('Refresh rate') . '<br />';
            echo PMA_getRefreshList('gridChartRefresh', 5, Array(2, 3, 4, 5, 10, 20, 40, 60, 120, 300, 600, 1200));
        ?><br />
        </div>
        <div class="floatleft">
            <?php echo __('Chart columns'); ?> <br />
            <select name="chartColumns">
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>5</option>
                <option>6</option>
                <option>7</option>
                <option>8</option>
                <option>9</option>
                <option>10</option>
            </select>
        </div>

        <div class="clearfloat paddingtop">
            <b><?php echo __('Chart arrangement'); ?></b> <?php echo PMA_Util::showHint(__('The arrangement of the charts is stored to the browsers local storage. You may want to export it if you have a complicated set up.')); ?><br/>
        <a href="#importMonitorConfig"><?php echo __('Import'); ?></a>&nbsp;&nbsp;<a href="#exportMonitorConfig"><?php echo __('Export'); ?></a>&nbsp;&nbsp;<a href="#clearMonitorConfig"><?php echo __('Reset to default'); ?></a>
        </div>
    </div>

    <div id="monitorInstructionsDialog" title="<?php echo __('Monitor Instructions'); ?>" style="display:none;">
        <?php echo __('The phpMyAdmin Monitor can assist you in optimizing the server configuration and track down time intensive queries. For the latter you will need to set log_output to \'TABLE\' and have either the slow_query_log or general_log enabled. Note however, that the general_log produces a lot of data and increases server load by up to 15%'); ?>
    <?php if (PMA_MYSQL_INT_VERSION < 50106) { ?>
        <p>
        <?php echo PMA_Util::getImage('s_attention.png'); ?>
        <?php
            echo __('Unfortunately your Database server does not support logging to table, which is a requirement for analyzing the database logs with phpMyAdmin. Logging to table is supported by MySQL 5.1.6 and onwards. You may still use the server charting features however.');
        ?>
        </p>
    <?php
    } else {
    ?>
        <p></p>
        <img class="ajaxIcon" src="<?php echo $GLOBALS['pmaThemeImage']; ?>ajax_clock_small.gif" alt="Loading" />
        <div class="ajaxContent"></div>
        <div class="monitorUse" style="display:none;">
            <p></p>
            <?php
                echo '<strong>';
                echo __('Using the monitor:');
                echo '</strong><p>';
                echo __('Your browser will refresh all displayed charts in a regular interval. You may add charts and change the refresh rate under \'Settings\', or remove any chart using the cog icon on each respective chart.');
                echo '</p><p>';
                echo __('To display queries from the logs, select the relevant time span on any chart by holding down the left mouse button and panning over the chart. Once confirmed, this will load a table of grouped queries, there you may click on any occuring SELECT statements to further analyze them.');
                echo '</p>';
            ?>
            <p>
            <?php echo PMA_Util::getImage('s_attention.png'); ?>
            <?php
                echo '<strong>';
                echo __('Please note:');
                echo '</strong><br />';
                echo __('Enabling the general_log may increase the server load by 5-15%. Also be aware that generating statistics from the logs is a load intensive task, so it is advisable to select only a small time span and to disable the general_log and empty its table once monitoring is not required any more.');
            ?>
            </p>
        </div>
    <?php
    }
    ?>
    </div>

    <div id="addChartDialog" title="<?php echo __('Add chart'); ?>" style="display:none;">
        <div id="tabGridVariables">
            <p><input type="text" name="chartTitle" value="<?php echo __('Chart Title'); ?>" /></p>

            <input type="radio" name="chartType" value="preset" id="chartPreset" />
            <label for="chartPreset"><?php echo __('Preset chart'); ?></label>
            <select name="presetCharts"></select><br/>

            <input type="radio" name="chartType" value="variable" id="chartStatusVar" checked="checked" />
            <label for="chartStatusVar"><?php echo __('Status variable(s)'); ?></label><br/>
            <div id="chartVariableSettings">
                <label for="chartSeries"><?php echo __('Select series:'); ?></label><br />
                <select id="chartSeries" name="varChartList" size="1">
                    <option><?php echo __('Commonly monitored'); ?></option>
                    <option>Processes</option>
                    <option>Questions</option>
                    <option>Connections</option>
                    <option>Bytes_sent</option>
                    <option>Bytes_received</option>
                    <option>Threads_connected</option>
                    <option>Created_tmp_disk_tables</option>
                    <option>Handler_read_first</option>
                    <option>Innodb_buffer_pool_wait_free</option>
                    <option>Key_reads</option>
                    <option>Open_tables</option>
                    <option>Select_full_join</option>
                    <option>Slow_queries</option>
                </select><br />
                <label for="variableInput"><?php echo __('or type variable name:'); ?> </label>
                <input type="text" name="variableInput" id="variableInput" />
                <p></p>
                <input type="checkbox" name="differentialValue" id="differentialValue" value="differential" checked="checked" />
                <label for="differentialValue"><?php echo __('Display as differential value'); ?></label><br />
                <input type="checkbox" id="useDivisor" name="useDivisor" value="1" />
                <label for="useDivisor"><?php echo __('Apply a divisor'); ?></label>
                <span class="divisorInput" style="display:none;">
                    <input type="text" name="valueDivisor" size="4" value="1" />
                    (<a href="#kibDivisor"><?php echo __('KiB'); ?></a>, <a href="#mibDivisor"><?php echo __('MiB'); ?></a>)
                </span><br />

                <input type="checkbox" id="useUnit" name="useUnit" value="1" />
                <label for="useUnit"><?php echo __('Append unit to data values'); ?></label>

                <span class="unitInput" style="display:none;">
                    <input type="text" name="valueUnit" size="4" value="" />
                </span>
                <p>
                    <a href="#submitAddSeries"><b><?php echo __('Add this series'); ?></b></a>
                    <span id="clearSeriesLink" style="display:none;">
                       | <a href="#submitClearSeries"><?php echo __('Clear series'); ?></a>
                    </span>
                </p>
                <?php echo __('Series in Chart:'); ?><br/>
                <span id="seriesPreview">
                <i><?php echo __('None'); ?></i>
                </span>
            </div>
        </div>
    </div>

    <!-- For generic use -->
    <div id="emptyDialog" title="Dialog" style="display:none;">
    </div>

    <?php
    if (! PMA_DRIZZLE) {
    ?>
    <div id="logAnalyseDialog" title="<?php echo __('Log statistics'); ?>" style="display:none;">
        <p> <?php echo __('Selected time range:'); ?>
        <input type="text" name="dateStart" class="datetimefield" value="" /> -
        <input type="text" name="dateEnd" class="datetimefield" value="" /></p>
        <input type="checkbox" id="limitTypes" value="1" checked="checked" />
        <label for="limitTypes">
            <?php echo __('Only retrieve SELECT,INSERT,UPDATE and DELETE Statements'); ?>
        </label>
        <br/>
        <input type="checkbox" id="removeVariables" value="1" checked="checked" />
        <label for="removeVariables">
            <?php echo __('Remove variable data in INSERT statements for better grouping'); ?>
        </label>

        <?php
        echo '<p>';
        echo __('Choose from which log you want the statistics to be generated from.');
        echo '</p><p>';
        echo __('Results are grouped by query text.');
        echo '</p>';
        ?>
    </div>

    <div id="queryAnalyzerDialog" title="<?php echo __('Query analyzer'); ?>" style="display:none;">
        <textarea id="sqlquery"> </textarea>
        <p></p>
        <div class="placeHolder"></div>
    </div>
    <?php
    }
    ?>

    <table class="clearfloat" id="chartGrid">

    </table>
    <div id="logTable">
        <br/>
    </div>

    <script type="text/javascript">
        variableNames = [ <?php
    $i=0;
    foreach ($server_status as $name=>$value) {
        if (is_numeric($value)) {
            if ($i++ > 0) {
                echo ", ";
            }
            echo "'" . $name . "'";
        }
    }
    ?> ];
    </script>
<?php
}

/**
 * Builds a <select> list for refresh rates
 *
 * @param string $name         Name of select
 * @param int    $defaultRate  Currently chosen rate
 * @param array  $refreshRates List of refresh rates
 *
 * @return HTML code with select
 */
function PMA_getRefreshList($name,
    $defaultRate = 5,
    $refreshRates = Array(1, 2, 5, 10, 20, 40, 60, 120, 300, 600)
) {
    $return = '<select name="' . $name . '" id="id_' . $name
        . '" class="refreshRate">';
    foreach ($refreshRates as $rate) {
        $selected = ($rate == $defaultRate)?' selected="selected"':'';
        $return .= '<option value="' . $rate . '"' . $selected . '>';
        if ($rate < 60) {
            $return .= sprintf(_ngettext('%d second', '%d seconds', $rate), $rate);
        } else {
            $rate = $rate / 60;
            $return .= sprintf(_ngettext('%d minute', '%d minutes', $rate), $rate);
        }
        $return .=  '</option>';
    }
    $return .= '</select>';
    return $return;
}

/**
 * Builds a <select> list for number of data points to be displayed
 *
 * @param string  $name         name of select
 * @param integer $defaultValue chosen value
 * @param array   $values       list of values
 *
 * @return string with html code
 */
function getDataPointsNumberList(
    $name, $defaultValue = 12, $values = Array(8, 10, 12, 15, 20, 25, 30, 40)
) {
    $html_output = '<select name="' . $name . '" id="id_' . $name
        . '" class="dataPointsNumber">';
    foreach ($values as $number) {
        $selected = ($number == $defaultValue)?' selected="selected"':'';
        $html_output .= '<option value="' . $number . '"' . $selected . '>'
            . sprintf(_ngettext('%d second', '%d points', $number), $number)
            . '</option>';
    }

    $html_output .= '</select>';
    return $html_output;
}

/**
 * cleanup of some deprecated values
 *
 * @param array &$server_status status array to process
 *
 * @return void
 */
function cleanDeprecated(&$server_status)
{
    $deprecated = array(
        'Com_prepare_sql' => 'Com_stmt_prepare',
        'Com_execute_sql' => 'Com_stmt_execute',
        'Com_dealloc_sql' => 'Com_stmt_close',
    );

    foreach ($deprecated as $old => $new) {
        if (isset($server_status[$old]) && isset($server_status[$new])) {
            unset($server_status[$old]);
        }
    }
}

?>
