<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays status variables with descriptions and some hints an optmizing
 *  + reset status variables
 *
 * @package PhpMyAdmin
 */

/**
 * no need for variables importing
 * @ignore
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}

if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
    $GLOBALS['is_header_sent'] = true;
}

require_once './libraries/common.inc.php';

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
            $c = PMA_DBI_fetch_result("SHOW GLOBAL STATUS WHERE Variable_name = 'Connections'", 0, 1);
            $result = PMA_DBI_query('SHOW PROCESSLIST');
            $num_procs = PMA_DBI_num_rows($result);

            $ret = array(
                'x'      => microtime(true) * 1000,
                'y_proc' => $num_procs,
                'y_conn' => $c['Connections']
            );

            exit(json_encode($ret));

        // Query realtime chart
        case 'queries':
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
                    WHERE (Variable_name LIKE 'Com_%' OR Variable_name = 'Questions')
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

        // Traffic realtime chart
        case 'traffic':
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

        // Data for the monitor
        case 'chartgrid':
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
                         * read them all in one query, and only afterwards assign them.
                         * Also do some white list filtering on the names
                        */
                        case 'servervar':
                            if (!preg_match('/[^a-zA-Z_]+/', $pName)) {
                                $serverVars[] = $pName;
                            }
                            break;

                        case 'statusvar':
                            if (!preg_match('/[^a-zA-Z_]+/', $pName)) {
                                $statusVars[] = $pName;
                            }
                            break;

                        case 'proc':
                            $result = PMA_DBI_query('SHOW PROCESSLIST');
                            $ret[$chart_id][$node_id][$point_id]['value'] = PMA_DBI_num_rows($result);
                            break;

                        case 'cpu':
                            if (!$sysinfo) {
                                include_once 'libraries/sysinfo.lib.php';
                                $sysinfo = getSysInfo();
                            }
                            if (!$cpuload) {
                                $cpuload = $sysinfo->loadavg();
                            }

                            if (PHP_OS == 'Linux') {
                                $ret[$chart_id][$node_id][$point_id]['idle'] = $cpuload['idle'];
                                $ret[$chart_id][$node_id][$point_id]['busy'] = $cpuload['busy'];
                            } else
                                $ret[$chart_id][$node_id][$point_id]['value'] = $cpuload['loadavg'];

                            break;

                        case 'memory':
                            if (!$sysinfo) {
                                include_once 'libraries/sysinfo.lib.php';
                                $sysinfo = getSysInfo();
                            }
                            if (!$memory) {
                                $memory  = $sysinfo->memory();
                            }

                            $ret[$chart_id][$node_id][$point_id]['value'] = $memory[$pName];
                            break;
                        } /* switch */
                    } /* foreach */
                } /* foreach */
            } /* foreach */

            // Retrieve all required status variables
            if (count($statusVars)) {
                $statusVarValues = PMA_DBI_fetch_result(
                    "SHOW GLOBAL STATUS
                    WHERE Variable_name='" . implode("' OR Variable_name='", $statusVars) . "'", 0, 1
                );
            } else {
                $statusVarValues = array();
            }

            // Retrieve all required server variables
            if (count($serverVars)) {
                $serverVarValues = PMA_DBI_fetch_result(
                    "SHOW GLOBAL VARIABLES
                    WHERE Variable_name='" . implode("' OR Variable_name='", $serverVars) . "'", 0, 1
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
                            $ret[$chart_id][$node_id][$point_id]['value'] = $statusVarValues[$dataPoint['name']];
                            break;
                        case 'servervar':
                            $ret[$chart_id][$node_id][$point_id]['value'] = $serverVarValues[$dataPoint['name']];
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
            /* FIXME: why this? */
            exit('""');
        }

        $start = intval($_REQUEST['time_start']);
        $end = intval($_REQUEST['time_end']);

        if ($_REQUEST['type'] == 'slow') {
            $q = 'SELECT start_time, user_host, Sec_to_Time(Sum(Time_to_Sec(query_time))) as query_time, Sec_to_Time(Sum(Time_to_Sec(lock_time))) as lock_time, '.
                 'SUM(rows_sent) AS rows_sent, SUM(rows_examined) AS rows_examined, db, sql_text, COUNT(sql_text) AS \'#\' '.
                 'FROM `mysql`.`slow_log` WHERE start_time > FROM_UNIXTIME(' . $start . ') '.
                 'AND start_time < FROM_UNIXTIME(' . $end . ') GROUP BY sql_text';

            $result = PMA_DBI_try_query($q);

            $return = array('rows' => array(), 'sum' => array());
            $type = '';

            while ($row = PMA_DBI_fetch_assoc($result)) {
                $type = strtolower(substr($row['sql_text'], 0, strpos($row['sql_text'], ' ')));

                switch($type) {
                case 'insert':
                case 'update':
                    // Cut off big inserts and updates, but append byte count therefor
                    if (strlen($row['sql_text']) > 220) {
                        $row['sql_text'] = substr($row['sql_text'], 0, 200)
                            . '... ['
                            .  implode(' ', PMA_formatByteDown(strlen($row['sql_text']), 2, 2))
                            . ']';
                    }
                    break;
                default:
                    break;
                }

                if (!isset($return['sum'][$type])) {
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
            $limitTypes = (isset($_REQUEST['limitTypes']) && $_REQUEST['limitTypes'])
                            ? 'AND argument REGEXP \'^(INSERT|SELECT|UPDATE|DELETE)\' ' : '';

            $q = 'SELECT TIME(event_time) as event_time, user_host, thread_id, server_id, argument, count(argument) as \'#\' '.
                 'FROM `mysql`.`general_log` WHERE command_type=\'Query\' '.
                 'AND event_time > FROM_UNIXTIME(' . $start . ') AND event_time < FROM_UNIXTIME(' . $end . ') '.
                 $limitTypes . 'GROUP by argument'; // HAVING count > 1';

            $result = PMA_DBI_try_query($q);

            $return = array('rows' => array(), 'sum' => array());
            $type = '';
            $insertTables = array();
            $insertTablesFirst = -1;
            $i = 0;
            $removeVars = isset($_REQUEST['removeVariables']) && $_REQUEST['removeVariables'];

            while ($row = PMA_DBI_fetch_assoc($result)) {
                preg_match('/^(\w+)\s/', $row['argument'], $match);
                $type = strtolower($match[1]);

                if (!isset($return['sum'][$type])) {
                    $return['sum'][$type] = 0;
                }
                $return['sum'][$type] += $row['#'];

                switch($type) {
                case 'insert':
                    // Group inserts if selected
                    if ($removeVars && preg_match('/^INSERT INTO (`|\'|"|)([^\s\\1]+)\\1/i', $row['argument'], $matches)) {
                        $insertTables[$matches[2]]++;
                        if ($insertTables[$matches[2]] > 1) {
                            $return['rows'][$insertTablesFirst]['#'] = $insertTables[$matches[2]];

                            // Add a ... to the end of this query to indicate that there's been other queries
                            if ($return['rows'][$insertTablesFirst]['argument'][strlen($return['rows'][$insertTablesFirst]['argument'])-1] != '.') {
                                $return['rows'][$insertTablesFirst]['argument'] .= '<br/>...';
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
                    // Cut off big inserts and updates, but append byte count therefor
                    if (strlen($row['argument']) > 220) {
                        $row['argument'] = substr($row['argument'], 0, 200)
                            . '... ['
                            .  implode(' ', PMA_formatByteDown(strlen($row['argument'])), 2, 2)
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
            $value = PMA_sqlAddslashes($_REQUEST['varValue']);
            if (!is_numeric($value)) {
                $value="'" . $value . "'";
            }

            if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])) {
                PMA_DBI_query('SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value);
            }

        }

        $loggingVars = PMA_DBI_fetch_result('SHOW GLOBAL VARIABLES WHERE Variable_name IN ("general_log","slow_query_log","long_query_time","log_output")', 0, 1);
        exit(json_encode($loggingVars));
    }

    if (isset($_REQUEST['query_analyzer'])) {
        $return = array();

        if (strlen($_REQUEST['database'])) {
            PMA_DBI_select_db($_REQUEST['database']);
        }

        if ($profiling = PMA_profilingSupported()) {
            PMA_DBI_query('SET PROFILING=1;');
        }

        // Do not cache query
        $query = preg_replace('/^(\s*SELECT)/i', '\\1 SQL_NO_CACHE', $_REQUEST['query']);

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
            $result = PMA_DBI_try_query('SELECT seq,state,duration FROM INFORMATION_SCHEMA.PROFILING WHERE QUERY_ID=1 ORDER BY seq');
            while ($row = PMA_DBI_fetch_assoc($result)) {
                $return['profiling'][]= $row;
            }
            PMA_DBI_free_result($result);
        }

        exit(json_encode($return));
    }

    if (isset($_REQUEST['advisor'])) {
        include 'libraries/Advisor.class.php';
        $advisor = new Advisor();
        exit(json_encode($advisor->run()));
    }
}


/**
 * Replication library
 */
if (PMA_DRIZZLE) {
    $server_master_status = false;
    $server_slave_status = false;
} else {
    include_once './libraries/replication.inc.php';
    include_once './libraries/replication_gui.lib.php';
}

/**
 * JS Includes
 */

$GLOBALS['js_include'][] = 'server_status.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'jquery/jquery.tablesorter.js';
$GLOBALS['js_include'][] = 'jquery/jquery.cookie.js'; // For tab persistence
// Charting
$GLOBALS['js_include'][] = 'highcharts/highcharts.js';
/* Files required for chart exporting */
$GLOBALS['js_include'][] = 'highcharts/exporting.js';
/* < IE 9 doesn't support canvas natively */
if (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER < 9) {
    $GLOBALS['js_include'][] = 'canvg/flashcanvas.js';
}
$GLOBALS['js_include'][] = 'canvg/canvg.js';

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
if (!empty($_REQUEST['kill'])) {
    if (PMA_DBI_try_query('KILL ' . $_REQUEST['kill'] . ';')) {
        $message = PMA_Message::success(__('Thread %s was successfully killed.'));
    } else {
        $message = PMA_Message::error(__('phpMyAdmin was unable to kill thread %s. It probably has already been closed.'));
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
    $server_status['Key_write_ratio_%'] = 100 * $server_status['Key_writes'] / $server_status['Key_write_requests'];
}

if (isset($server_status['Key_reads'])
    && isset($server_status['Key_read_requests'])
    && $server_status['Key_read_requests'] > 0
) {
    $server_status['Key_read_ratio_%'] = 100 * $server_status['Key_reads'] / $server_status['Key_read_requests'];
}

// Threads_cache_hitrate
if (isset($server_status['Threads_created'])
    && isset($server_status['Connections'])
    && $server_status['Connections'] > 0
) {

    $server_status['Threads_cache_hitrate_%']
        = 100 - $server_status['Threads_created'] / $server_status['Connections'] * 100;
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

PMA_AddJSVar(
    'pma_token',
    $_SESSION[' PMA_token ']
);
PMA_AddJSVar(
    'url_query',
    str_replace('&amp;', '&', PMA_generate_common_url($db))
);
PMA_AddJSVar(
    'server_time_diff',
    'new Date().getTime() - ' . (microtime(true) * 1000),
    false
);
PMA_AddJSVar(
    'server_os',
    PHP_OS
);
PMA_AddJSVar(
    'is_superuser',
    PMA_isSuperuser()
);
PMA_AddJSVar(
    'server_db_isLocal',
    $server_db_isLocal
);
PMA_AddJSVar(
    'profiling_docu',
    PMA_showMySQLDocu('general-thread-states', 'general-thread-states')
);
PMA_AddJSVar(
    'explain_docu',
    PMA_showMySQLDocu('explain-output', 'explain-output')
);

/**
 * start output
 */

 /**
 * Does the common work
 */
require './libraries/server_common.inc.php';



/**
 * Displays the links
 */
require './libraries/server_links.inc.php';

?>
<div id="serverstatus">
    <h2><?php
/**
 * Displays the sub-page heading
 */
if ($GLOBALS['cfg']['MainPageIconic']) {
    echo PMA_getImage('s_status.png');
}

echo __('Runtime Information');

?></h2>
    <div id="serverStatusTabs">
        <ul>
            <li><a href="#statustabs_traffic"><?php echo __('Server'); ?></a></li>
            <li><a href="#statustabs_queries"><?php echo __('Query statistics'); ?></a></li>
            <li><a href="#statustabs_allvars"><?php echo __('All status variables'); ?></a></li>
            <li class="jsfeature"><a href="#statustabs_charting"><?php echo __('Monitor'); ?></a></li>
            <li class="jsfeature"><a href="#statustabs_advisor"><?php echo __('Advisor'); ?></a></li>
        </ul>

        <div id="statustabs_traffic" class="clearfloat">
            <div class="buttonlinks jsfeature">
                <a class="tabRefresh" href="<?php echo $PMA_PHP_SELF . '?show=server_traffic&amp;' . PMA_generate_common_url(); ?>" >
                    <img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" alt="ajax clock" style="display: none;" />
                    <?php echo __('Refresh'); ?>
                </a>
                <span class="refreshList" style="display:none;">
                    <label for="id_trafficChartRefresh"><?php echo __('Refresh rate: '); ?></label>
                    <?php refreshList('trafficChartRefresh'); ?>
                </span>

                <a class="tabChart livetrafficLink" href="#">
                    <?php echo __('Live traffic chart'); ?>
                </a>
                <a class="tabChart liveconnectionsLink" href="#">
                    <?php echo __('Live conn./process chart'); ?>
                </a>
            </div>
            <div class="tabInnerContent">
                <?php printServerTraffic(); ?>
            </div>
        </div>
        <div id="statustabs_queries" class="clearfloat">
            <div class="buttonlinks jsfeature">
                <a class="tabRefresh"  href="<?php echo $PMA_PHP_SELF . '?show=query_statistics&amp;' . PMA_generate_common_url(); ?>" >
                    <img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" alt="ajax clock" style="display: none;" />
                    <?php echo __('Refresh'); ?>
                </a>
                <span class="refreshList" style="display:none;">
                    <label for="id_queryChartRefresh"><?php echo __('Refresh rate: '); ?></label>
                       <?php refreshList('queryChartRefresh'); ?>
                </span>
                <a class="tabChart livequeriesLink" href="#">
                    <?php echo __('Live query chart'); ?>
                </a>
            </div>
            <div class="tabInnerContent">
                <?php printQueryStatistics(); ?>
            </div>
        </div>
        <div id="statustabs_allvars" class="clearfloat">
            <fieldset id="tableFilter" class="jsfeature">
                <div class="buttonlinks">
                    <a class="tabRefresh" href="<?php echo $PMA_PHP_SELF . '?show=variables_table&amp;' . PMA_generate_common_url(); ?>" >
                        <img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" alt="ajax clock" style="display: none;" />
                        <?php echo __('Refresh'); ?>
                    </a>
                </div>
                <legend><?php echo __('Filters'); ?></legend>
                <div class="formelement">
                    <label for="filterText"><?php echo __('Containing the word:'); ?></label>
                    <input name="filterText" type="text" id="filterText" style="vertical-align: baseline;" />
                </div>
                <div class="formelement">
                    <input type="checkbox" name="filterAlert" id="filterAlert" />
                    <label for="filterAlert"><?php echo __('Show only alert values'); ?></label>
                </div>
                <div class="formelement">
                    <select id="filterCategory" name="filterCategory">
                        <option value=''><?php echo __('Filter by category...'); ?></option>
                <?php
                        foreach ($sections as $section_id => $section_name) {
                            if (isset($categoryUsed[$section_id])) {
                ?>
                                <option value='<?php echo $section_id; ?>'><?php echo $section_name; ?></option>
                <?php
                            }
                        }
                ?>
                    </select>
                </div>
                <div class="formelement">
                    <input type="checkbox" name="dontFormat" id="dontFormat" />
                    <label for="dontFormat"><?php echo __('Show unformatted values'); ?></label>
                </div>
            </fieldset>
            <div id="linkSuggestions" class="defaultLinks" style="display:none">
                <p class="notice"><?php echo __('Related links:'); ?>
                <?php
                foreach ($links as $section_name => $section_links) {
                    echo '<span class="status_' . $section_name . '"> ';
                    $i=0;
                    foreach ($section_links as $link_name => $link_url) {
                        if ($i > 0) {
                            echo ', ';
                        }
                        if ('doc' == $link_name) {
                            echo PMA_showMySQLDocu($link_url, $link_url);
                        } else {
                            echo '<a href="' . $link_url . '">' . $link_name . '</a>';
                        }
                        $i++;
                    }
                    echo '</span>';
                }
                unset($link_url, $link_name, $i);
                ?>
                </p>
            </div>
            <div class="tabInnerContent">
                <?php printVariablesTable(); ?>
            </div>
        </div>

        <div id="statustabs_charting" class="jsfeature">
            <?php printMonitor(); ?>
        </div>

        <div id="statustabs_advisor" class="jsfeature">
            <div class="tabLinks">
                <?php echo PMA_getImage('play.png'); ?> <a href="#startAnalyzer"><?php echo __('Run analyzer'); ?></a>
                <?php echo PMA_getImage('b_help.png'); ?> <a href="#openAdvisorInstructions"><?php echo __('Instructions'); ?></a>
            </div>
            <div class="tabInnerContent clearfloat">
            </div>
            <div id="advisorInstructionsDialog" style="display:none;">
            <?php
            echo '<p>';
            echo __('The Advisor system can provide recommendations on server variables by analyzing the server status variables.');
            echo '</p> <p>';
            echo __('Do note however that this system provides recommendations based on simple calculations and by rule of thumb which may not necessarily apply to your system.');
            echo '</p> <p>';
            echo __('Prior to changing any of the configuration, be sure to know what you are changing (by reading the documentation) and how to undo the change. Wrong tuning can have a very negative effect on performance.');
            echo '</p> <p>';
            echo __('The best way to tune your system would be to change only one setting at a time, observe or benchmark your database, and undo the change if there was no clearly measurable improvement.');
            echo '</p>';
            ?>
            </div>
        </div>
    </div>
</div>

<?php

function printQueryStatistics()
{
    global $server_status, $used_queries, $url_query, $PMA_PHP_SELF;

    $hour_factor   = 3600 / $server_status['Uptime'];

    $total_queries = array_sum($used_queries);

    ?>
    <h3 id="serverstatusqueries">
        <?php
        /* l10n: Questions is the name of a MySQL Status variable */
        echo sprintf(__('Questions since startup: %s'), PMA_formatNumber($total_queries, 0)) . ' ';
        echo PMA_showMySQLDocu('server-status-variables', 'server-status-variables', false, 'statvar_Questions');
        ?>
        <br />
        <span>
        <?php
        echo '&oslash; ' . __('per hour') . ': ';
        echo PMA_formatNumber($total_queries * $hour_factor, 0);
        echo '<br />';

        echo '&oslash; ' . __('per minute') . ': ';
        echo PMA_formatNumber($total_queries * 60 / $server_status['Uptime'], 0);
        echo '<br />';

        if ($total_queries / $server_status['Uptime'] >= 1) {
            echo '&oslash; ' . __('per second') . ': ';
            echo PMA_formatNumber($total_queries / $server_status['Uptime'], 0);
        }
        ?>
        </span>
    </h3>
    <?php

    // reverse sort by value to show most used statements first
    arsort($used_queries);

    $odd_row        = true;
    $count_displayed_rows = 0;
    $perc_factor    = 100 / $total_queries; //(- $server_status['Connections']);

    ?>

        <table id="serverstatusqueriesdetails" class="data sortable noclick">
        <col class="namecol" />
        <col class="valuecol" span="3" />
        <thead>
            <tr><th><?php echo __('Statements'); ?></th>
                <th><?php
                    /* l10n: # = Amount of queries */
                    echo __('#');
                    ?>
                </th>
                <th>&oslash; <?php echo __('per hour'); ?></th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>

    <?php
    $chart_json = array();
    $query_sum = array_sum($used_queries);
    $other_sum = 0;
    foreach ($used_queries as $name => $value) {
        $odd_row = !$odd_row;

        // For the percentage column, use Questions - Connections, because
        // the number of connections is not an item of the Query types
        // but is included in Questions. Then the total of the percentages is 100.
        $name = str_replace(array('Com_', '_'), array('', ' '), $name);

        // Group together values that make out less than 2% into "Other", but only if we have more than 6 fractions already
        if ($value < $query_sum * 0.02 && count($chart_json)>6) {
            $other_sum += $value;
        } else {
            $chart_json[$name] = $value;
        }
    ?>
            <tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
                <th class="name"><?php echo htmlspecialchars($name); ?></th>
                <td class="value"><?php echo htmlspecialchars(PMA_formatNumber($value, 5, 0, true)); ?></td>
                <td class="value"><?php echo
                    htmlspecialchars(PMA_formatNumber($value * $hour_factor, 4, 1, true)); ?></td>
                <td class="value"><?php echo
                    htmlspecialchars(PMA_formatNumber($value * $perc_factor, 0, 2)); ?>%</td>
            </tr>
    <?php
    }
    ?>
        </tbody>
        </table>

        <div id="serverstatusquerieschart">
            <span style="display:none;">
        <?php
            if ($other_sum > 0) {
                $chart_json[__('Other')] = $other_sum;
            }

            echo json_encode($chart_json);
        ?>
            </span>
        </div>
        <?php
}

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

    ?>
    <h3><?php
    echo sprintf(
        __('Network traffic since startup: %s'),
        implode(' ', PMA_formatByteDown($server_status['Bytes_received'] + $server_status['Bytes_sent'], 3, 1))
    );
    ?>
    </h3>

    <p>
    <?php
    echo sprintf(
        __('This MySQL server has been running for %1$s. It started up on %2$s.'),
        PMA_timespanFormat($server_status['Uptime']),
        PMA_localisedDate($start_time)
    ) . "\n";
    ?>
    </p>

    <?php
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

    /* if the server works as master or slave in replication process, display useful information */
    if ($server_master_status || $server_slave_status) {
    ?>
      <hr class="clearfloat" />

      <h3><a name="replication"></a><?php echo __('Replication status'); ?></h3>
    <?php

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
        <th colspan="2"><?php echo __('Traffic') . '&nbsp;' . PMA_showHint(__('On a busy server, the byte counters may overrun, so those statistics as reported by the MySQL server may be incorrect.')); ?></th>
        <th>&oslash; <?php echo __('per hour'); ?></th>
    </tr>
    </thead>
    <tbody>
    <tr class="odd">
        <th class="name"><?php echo __('Received'); ?></th>
        <td class="value"><?php echo
            implode(' ',
                PMA_formatByteDown($server_status['Bytes_received'], 3, 1)); ?></td>
        <td class="value"><?php echo
            implode(' ',
                PMA_formatByteDown(
                    $server_status['Bytes_received'] * $hour_factor, 3, 1)); ?></td>
    </tr>
    <tr class="even">
        <th class="name"><?php echo __('Sent'); ?></th>
        <td class="value"><?php echo
            implode(' ',
                PMA_formatByteDown($server_status['Bytes_sent'], 3, 1)); ?></td>
        <td class="value"><?php echo
            implode(' ',
                PMA_formatByteDown(
                    $server_status['Bytes_sent'] * $hour_factor, 3, 1)); ?></td>
    </tr>
    <tr class="odd">
        <th class="name"><?php echo __('Total'); ?></th>
        <td class="value"><?php echo
            implode(' ',
                PMA_formatByteDown(
                    $server_status['Bytes_received'] + $server_status['Bytes_sent'], 3, 1)
            ); ?></td>
        <td class="value"><?php echo
            implode(' ',
                PMA_formatByteDown(
                    ($server_status['Bytes_received'] + $server_status['Bytes_sent'])
                    * $hour_factor, 3, 1)
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
            PMA_formatNumber($server_status['Max_used_connections'], 0); ?>  </td>
        <td class="value">--- </td>
        <td class="value">--- </td>
    </tr>
    <tr class="even">
        <th class="name"><?php echo __('Failed attempts'); ?></th>
        <td class="value"><?php echo
            PMA_formatNumber($server_status['Aborted_connects'], 4, 1, true); ?></td>
        <td class="value"><?php echo
            PMA_formatNumber($server_status['Aborted_connects'] * $hour_factor,
                4, 2, true); ?></td>
        <td class="value"><?php echo
            $server_status['Connections'] > 0
          ? PMA_formatNumber(
                $server_status['Aborted_connects'] * 100 / $server_status['Connections'],
                0, 2, true) . '%'
          : '--- '; ?></td>
    </tr>
    <tr class="odd">
        <th class="name"><?php echo __('Aborted'); ?></th>
        <td class="value"><?php echo
            PMA_formatNumber($server_status['Aborted_clients'], 4, 1, true); ?></td>
        <td class="value"><?php echo
            PMA_formatNumber($server_status['Aborted_clients'] * $hour_factor,
                4, 2, true); ?></td>
        <td class="value"><?php echo
            $server_status['Connections'] > 0
          ? PMA_formatNumber(
                $server_status['Aborted_clients'] * 100 / $server_status['Connections'],
                0, 2, true) . '%'
          : '--- '; ?></td>
    </tr>
    <tr class="even">
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
    <?php

    $url_params = array();

    $show_full_sql = !empty($_REQUEST['full']);
    if ($show_full_sql) {
        $url_params['full'] = 1;
        $full_text_link = 'server_status.php' . PMA_generate_common_url(array(), 'html', '?');
    } else {
        $full_text_link = 'server_status.php' . PMA_generate_common_url(array('full' => 1));
    }
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
    } else {
        $sql_query = $show_full_sql
            ? 'SHOW FULL PROCESSLIST'
            : 'SHOW PROCESSLIST';
    }
    $result = PMA_DBI_query($sql_query);

    /**
     * Displays the page
     */
    ?>
    <table id="tableprocesslist" class="data clearfloat noclick">
    <thead>
    <tr>
        <th><?php echo __('Processes'); ?></th>
        <th><?php echo __('ID'); ?></th>
        <th><?php echo __('User'); ?></th>
        <th><?php echo __('Host'); ?></th>
        <th><?php echo __('Database'); ?></th>
        <th><?php echo __('Command'); ?></th>
        <th><?php echo __('Time'); ?></th>
        <th><?php echo __('Status'); ?></th>
        <th><?php
            echo __('SQL query');
            if (! PMA_DRIZZLE) {
                ?>
            <a href="<?php echo $full_text_link; ?>"
                title="<?php echo $show_full_sql ? __('Truncate Shown Queries') : __('Show Full Queries'); ?>">
                <img src="<?php echo $GLOBALS['pmaThemeImage'] . 's_' . ($show_full_sql ? 'partial' : 'full'); ?>text.png"
                alt="<?php echo $show_full_sql ? __('Truncate Shown Queries') : __('Show Full Queries'); ?>" />
            </a>
            <?php } ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php
    $odd_row = true;
    while ($process = PMA_DBI_fetch_assoc($result)) {
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

function printVariablesTable()
{
    global $server_status, $server_variables, $allocationMap, $links;
    /**
     * Messages are built using the message name
     */
    $strShowStatus = array(
        'Aborted_clients' => __('The number of connections that were aborted because the client died without closing the connection properly.'),
        'Aborted_connects' => __('The number of failed attempts to connect to the MySQL server.'),
        'Binlog_cache_disk_use' => __('The number of transactions that used the temporary binary log cache but that exceeded the value of binlog_cache_size and used a temporary file to store statements from the transaction.'),
        'Binlog_cache_use' => __('The number of transactions that used the temporary binary log cache.'),
        'Connections' => __('The number of connection attempts (successful or not) to the MySQL server.'),
        'Created_tmp_disk_tables' => __('The number of temporary tables on disk created automatically by the server while executing statements. If Created_tmp_disk_tables is big, you may want to increase the tmp_table_size  value to cause temporary tables to be memory-based instead of disk-based.'),
        'Created_tmp_files' => __('How many temporary files mysqld has created.'),
        'Created_tmp_tables' => __('The number of in-memory temporary tables created automatically by the server while executing statements.'),
        'Delayed_errors' => __('The number of rows written with INSERT DELAYED for which some error occurred (probably duplicate key).'),
        'Delayed_insert_threads' => __('The number of INSERT DELAYED handler threads in use. Every different table on which one uses INSERT DELAYED gets its own thread.'),
        'Delayed_writes' => __('The number of INSERT DELAYED rows written.'),
        'Flush_commands'  => __('The number of executed FLUSH statements.'),
        'Handler_commit' => __('The number of internal COMMIT statements.'),
        'Handler_delete' => __('The number of times a row was deleted from a table.'),
        'Handler_discover' => __('The MySQL server can ask the NDB Cluster storage engine if it knows about a table with a given name. This is called discovery. Handler_discover indicates the number of time tables have been discovered.'),
        'Handler_read_first' => __('The number of times the first entry was read from an index. If this is high, it suggests that the server is doing a lot of full index scans; for example, SELECT col1 FROM foo, assuming that col1 is indexed.'),
        'Handler_read_key' => __('The number of requests to read a row based on a key. If this is high, it is a good indication that your queries and tables are properly indexed.'),
        'Handler_read_next' => __('The number of requests to read the next row in key order. This is incremented if you are querying an index column with a range constraint or if you are doing an index scan.'),
        'Handler_read_prev' => __('The number of requests to read the previous row in key order. This read method is mainly used to optimize ORDER BY ... DESC.'),
        'Handler_read_rnd' => __('The number of requests to read a row based on a fixed position. This is high if you are doing a lot of queries that require sorting of the result. You probably have a lot of queries that require MySQL to scan whole tables or you have joins that don\'t use keys properly.'),
        'Handler_read_rnd_next' => __('The number of requests to read the next row in the data file. This is high if you are doing a lot of table scans. Generally this suggests that your tables are not properly indexed or that your queries are not written to take advantage of the indexes you have.'),
        'Handler_rollback' => __('The number of internal ROLLBACK statements.'),
        'Handler_update' => __('The number of requests to update a row in a table.'),
        'Handler_write' => __('The number of requests to insert a row in a table.'),
        'Innodb_buffer_pool_pages_data' => __('The number of pages containing data (dirty or clean).'),
        'Innodb_buffer_pool_pages_dirty' => __('The number of pages currently dirty.'),
        'Innodb_buffer_pool_pages_flushed' => __('The number of buffer pool pages that have been requested to be flushed.'),
        'Innodb_buffer_pool_pages_free' => __('The number of free pages.'),
        'Innodb_buffer_pool_pages_latched' => __('The number of latched pages in InnoDB buffer pool. These are pages currently being read or written or that can\'t be flushed or removed for some other reason.'),
        'Innodb_buffer_pool_pages_misc' => __('The number of pages busy because they have been allocated for administrative overhead such as row locks or the adaptive hash index. This value can also be calculated as Innodb_buffer_pool_pages_total - Innodb_buffer_pool_pages_free - Innodb_buffer_pool_pages_data.'),
        'Innodb_buffer_pool_pages_total' => __('Total size of buffer pool, in pages.'),
        'Innodb_buffer_pool_read_ahead_rnd' => __('The number of "random" read-aheads InnoDB initiated. This happens when a query is to scan a large portion of a table but in random order.'),
        'Innodb_buffer_pool_read_ahead_seq' => __('The number of sequential read-aheads InnoDB initiated. This happens when InnoDB does a sequential full table scan.'),
        'Innodb_buffer_pool_read_requests' => __('The number of logical read requests InnoDB has done.'),
        'Innodb_buffer_pool_reads' => __('The number of logical reads that InnoDB could not satisfy from buffer pool and had to do a single-page read.'),
        'Innodb_buffer_pool_wait_free' => __('Normally, writes to the InnoDB buffer pool happen in the background. However, if it\'s necessary to read or create a page and no clean pages are available, it\'s necessary to wait for pages to be flushed first. This counter counts instances of these waits. If the buffer pool size was set properly, this value should be small.'),
        'Innodb_buffer_pool_write_requests' => __('The number writes done to the InnoDB buffer pool.'),
        'Innodb_data_fsyncs' => __('The number of fsync() operations so far.'),
        'Innodb_data_pending_fsyncs' => __('The current number of pending fsync() operations.'),
        'Innodb_data_pending_reads' => __('The current number of pending reads.'),
        'Innodb_data_pending_writes' => __('The current number of pending writes.'),
        'Innodb_data_read' => __('The amount of data read so far, in bytes.'),
        'Innodb_data_reads' => __('The total number of data reads.'),
        'Innodb_data_writes' => __('The total number of data writes.'),
        'Innodb_data_written' => __('The amount of data written so far, in bytes.'),
        'Innodb_dblwr_pages_written' => __('The number of pages that have been written for doublewrite operations.'),
        'Innodb_dblwr_writes' => __('The number of doublewrite operations that have been performed.'),
        'Innodb_log_waits' => __('The number of waits we had because log buffer was too small and we had to wait for it to be flushed before continuing.'),
        'Innodb_log_write_requests' => __('The number of log write requests.'),
        'Innodb_log_writes' => __('The number of physical writes to the log file.'),
        'Innodb_os_log_fsyncs' => __('The number of fsync() writes done to the log file.'),
        'Innodb_os_log_pending_fsyncs' => __('The number of pending log file fsyncs.'),
        'Innodb_os_log_pending_writes' => __('Pending log file writes.'),
        'Innodb_os_log_written' => __('The number of bytes written to the log file.'),
        'Innodb_pages_created' => __('The number of pages created.'),
        'Innodb_page_size' => __('The compiled-in InnoDB page size (default 16KB). Many values are counted in pages; the page size allows them to be easily converted to bytes.'),
        'Innodb_pages_read' => __('The number of pages read.'),
        'Innodb_pages_written' => __('The number of pages written.'),
        'Innodb_row_lock_current_waits' => __('The number of row locks currently being waited for.'),
        'Innodb_row_lock_time_avg' => __('The average time to acquire a row lock, in milliseconds.'),
        'Innodb_row_lock_time' => __('The total time spent in acquiring row locks, in milliseconds.'),
        'Innodb_row_lock_time_max' => __('The maximum time to acquire a row lock, in milliseconds.'),
        'Innodb_row_lock_waits' => __('The number of times a row lock had to be waited for.'),
        'Innodb_rows_deleted' => __('The number of rows deleted from InnoDB tables.'),
        'Innodb_rows_inserted' => __('The number of rows inserted in InnoDB tables.'),
        'Innodb_rows_read' => __('The number of rows read from InnoDB tables.'),
        'Innodb_rows_updated' => __('The number of rows updated in InnoDB tables.'),
        'Key_blocks_not_flushed' => __('The number of key blocks in the key cache that have changed but haven\'t yet been flushed to disk. It used to be known as Not_flushed_key_blocks.'),
        'Key_blocks_unused' => __('The number of unused blocks in the key cache. You can use this value to determine how much of the key cache is in use.'),
        'Key_blocks_used' => __('The number of used blocks in the key cache. This value is a high-water mark that indicates the maximum number of blocks that have ever been in use at one time.'),
        'Key_read_requests' => __('The number of requests to read a key block from the cache.'),
        'Key_reads' => __('The number of physical reads of a key block from disk. If Key_reads is big, then your key_buffer_size value is probably too small. The cache miss rate can be calculated as Key_reads/Key_read_requests.'),
        'Key_write_requests' => __('The number of requests to write a key block to the cache.'),
        'Key_writes' => __('The number of physical writes of a key block to disk.'),
        'Last_query_cost' => __('The total cost of the last compiled query as computed by the query optimizer. Useful for comparing the cost of different query plans for the same query. The default value of 0 means that no query has been compiled yet.'),
        'Max_used_connections' => __('The maximum number of connections that have been in use simultaneously since the server started.'),
        'Not_flushed_delayed_rows' => __('The number of rows waiting to be written in INSERT DELAYED queues.'),
        'Opened_tables' => __('The number of tables that have been opened. If opened tables is big, your table cache value is probably too small.'),
        'Open_files' => __('The number of files that are open.'),
        'Open_streams' => __('The number of streams that are open (used mainly for logging).'),
        'Open_tables' => __('The number of tables that are open.'),
        'Qcache_free_blocks' => __('The number of free memory blocks in query cache. High numbers can indicate fragmentation issues, which may be solved by issuing a FLUSH QUERY CACHE statement.'),
        'Qcache_free_memory' => __('The amount of free memory for query cache.'),
        'Qcache_hits' => __('The number of cache hits.'),
        'Qcache_inserts' => __('The number of queries added to the cache.'),
        'Qcache_lowmem_prunes' => __('The number of queries that have been removed from the cache to free up memory for caching new queries. This information can help you tune the query cache size. The query cache uses a least recently used (LRU) strategy to decide which queries to remove from the cache.'),
        'Qcache_not_cached' => __('The number of non-cached queries (not cachable, or not cached due to the query_cache_type setting).'),
        'Qcache_queries_in_cache' => __('The number of queries registered in the cache.'),
        'Qcache_total_blocks' => __('The total number of blocks in the query cache.'),
        'Rpl_status' => __('The status of failsafe replication (not yet implemented).'),
        'Select_full_join' => __('The number of joins that do not use indexes. If this value is not 0, you should carefully check the indexes of your tables.'),
        'Select_full_range_join' => __('The number of joins that used a range search on a reference table.'),
        'Select_range_check' => __('The number of joins without keys that check for key usage after each row. (If this is not 0, you should carefully check the indexes of your tables.)'),
        'Select_range' => __('The number of joins that used ranges on the first table. (It\'s normally not critical even if this is big.)'),
        'Select_scan' => __('The number of joins that did a full scan of the first table.'),
        'Slave_open_temp_tables' => __('The number of temporary tables currently open by the slave SQL thread.'),
        'Slave_retried_transactions' => __('Total (since startup) number of times the replication slave SQL thread has retried transactions.'),
        'Slave_running' => __('This is ON if this server is a slave that is connected to a master.'),
        'Slow_launch_threads' => __('The number of threads that have taken more than slow_launch_time seconds to create.'),
        'Slow_queries' => __('The number of queries that have taken more than long_query_time seconds.'),
        'Sort_merge_passes' => __('The number of merge passes the sort algorithm has had to do. If this value is large, you should consider increasing the value of the sort_buffer_size system variable.'),
        'Sort_range' => __('The number of sorts that were done with ranges.'),
        'Sort_rows' => __('The number of sorted rows.'),
        'Sort_scan' => __('The number of sorts that were done by scanning the table.'),
        'Table_locks_immediate' => __('The number of times that a table lock was acquired immediately.'),
        'Table_locks_waited' => __('The number of times that a table lock could not be acquired immediately and a wait was needed. If this is high, and you have performance problems, you should first optimize your queries, and then either split your table or tables or use replication.'),
        'Threads_cached' => __('The number of threads in the thread cache. The cache hit rate can be calculated as Threads_created/Connections. If this value is red you should raise your thread_cache_size.'),
        'Threads_connected' => __('The number of currently open connections.'),
        'Threads_created' => __('The number of threads created to handle connections. If Threads_created is big, you may want to increase the thread_cache_size value. (Normally this doesn\'t give a notable performance improvement if you have a good thread implementation.)'),
        'Threads_running' => __('The number of threads that are not sleeping.')
    );

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

        'Qcache_free_blocks' => isset($server_status['Qcache_total_blocks']) ? $server_status['Qcache_total_blocks'] / 5 : 0,
        'Slow_launch_threads' => 0,

        // depends on Key_read_requests
        // normaly lower then 1:0.01
        'Key_reads' => isset($server_status['Key_read_requests']) ? (0.01 * $server_status['Key_read_requests']) : 0,
        // depends on Key_write_requests
        // normaly nearly 1:1
        'Key_writes' => isset($server_status['Key_write_requests']) ? (0.9 * $server_status['Key_write_requests']) : 0,

        'Key_buffer_fraction' => 0.5,

        // alert if more than 95% of thread cache is in use
        'Threads_cached' => isset($server_variables['thread_cache_size']) ? 0.95 * $server_variables['thread_cache_size'] : 0

        // higher is better
        // variable => min value
        //'Handler read key' => '> ',
    );

?>
<table class="data sortable noclick" id="serverstatusvariables">
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
    <tbody>
    <?php

    $odd_row = false;
    foreach ($server_status as $name => $value) {
            $odd_row = !$odd_row;
?>
        <tr class="<?php echo $odd_row ? 'odd' : 'even'; echo isset($allocationMap[$name])?' s_' . $allocationMap[$name]:''; ?>">
            <th class="name"><?php
            echo htmlspecialchars(str_replace('_', ' ', $name));
            /* Fields containing % are calculated, they can not be described in MySQL documentation */
            if (strpos($name, '%') === FALSE) {
                 echo PMA_showMySQLDocu('server-status-variables', 'server-status-variables', false, 'statvar_' . $name);
            }
            ?>
            </th>
            <td class="value"><span class="formatted"><?php
            if (isset($alerts[$name])) {
                if ($value > $alerts[$name]) {
                    echo '<span class="attention">';
                } else {
                    echo '<span class="allfine">';
                }
            }
            if ('%' === substr($name, -1, 1)) {
                echo htmlspecialchars(PMA_formatNumber($value, 0, 2)) . ' %';
            } elseif (strpos($name, 'Uptime') !== false) {
                echo htmlspecialchars(PMA_timespanFormat($value));
            } elseif (is_numeric($value) && $value == (int) $value && $value > 1000) {
                echo htmlspecialchars(PMA_formatNumber($value, 3, 1));
            } elseif (is_numeric($value) && $value == (int) $value) {
                echo htmlspecialchars(PMA_formatNumber($value, 3, 0));
            } elseif (is_numeric($value)) {
                echo htmlspecialchars(PMA_formatNumber($value, 3, 1));
            } else {
                echo htmlspecialchars($value);
            }
            if (isset($alerts[$name])) {
                echo '</span>';
            }
            ?></span><span style="display:none;" class="original"><?php echo $value; ?></span>
            </td>
            <td class="descr">
            <?php
            if (isset($strShowStatus[$name ])) {
                echo $strShowStatus[$name];
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
    ?>
    </tbody>
    </table>
    <?php
}

function printMonitor()
{
    global $server_status, $server_db_isLocal;
?>
    <div class="tabLinks" style="display:none;">
        <a href="#pauseCharts">
            <?php echo PMA_getImage('play.png'); ?>
            <?php echo __('Start Monitor'); ?>
        </a>
        <a href="#settingsPopup" rel="popupLink" style="display:none;">
            <?php echo PMA_getImage('s_cog.png'); ?>
            <?php echo __('Settings'); ?>
        </a>
        <?php if (!PMA_DRIZZLE) { ?>
        <a href="#monitorInstructionsDialog">
            <?php echo PMA_getImage('b_help.png'); ?>
            <?php echo __('Instructions/Setup'); ?>
        </a>
        <?php } ?>
        <a href="#endChartEditMode" style="display:none;">
            <?php echo PMA_getImage('s_okay.png'); ?>
            <?php echo __('Done rearranging/editing charts'); ?>
        </a>
    </div>

    <div class="popupContent settingsPopup">
        <a href="#addNewChart">
            <?php echo PMA_getImage('b_chart.png'); ?>
            <?php echo __('Add chart'); ?>
        </a>
        <a href="#rearrangeCharts"><?php echo PMA_getImage('b_tblops.png'); ?><?php echo __('Rearrange/edit charts'); ?></a>
        <div class="clearfloat paddingtop"></div>
        <div class="floatleft">
            <?php
            echo __('Refresh rate') . '<br />';
            refreshList('gridChartRefresh', 5, Array(2, 3, 4, 5, 10, 20, 40, 60, 120, 300, 600, 1200));
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
        <b><?php echo __('Chart arrangement'); ?></b> <?php echo PMA_showHint(__('The arrangement of the charts is stored to the browsers local storage. You may want to export it if you have a complicated set up.')); ?><br/>
        <a href="#importMonitorConfig"><?php echo __('Import'); ?></a>&nbsp;&nbsp;<a href="#exportMonitorConfig"><?php echo __('Export'); ?></a>&nbsp;&nbsp;<a href="#clearMonitorConfig"><?php echo __('Reset to default'); ?></a>
        </div>
    </div>

    <div id="monitorInstructionsDialog" title="<?php echo __('Monitor Instructions'); ?>" style="display:none;">
        <?php echo __('The phpMyAdmin Monitor can assist you in optimizing the server configuration and track down time intensive queries. For the latter you will need to set log_output to \'TABLE\' and have either the slow_query_log or general_log enabled. Note however, that the general_log produces a lot of data and increases server load by up to 15%'); ?>
    <?php if (PMA_MYSQL_INT_VERSION < 50106) { ?>
        <p>
        <?php echo PMA_getImage('s_attention.png'); ?>
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
            <?php echo PMA_getImage('s_attention.png'); ?>
            <?php
                echo '<strong>';
                echo __('Please note:');
                echo '</strong><br />';
                echo __('Enabling the general_log may increase the server load by 5-15%. Also be aware that generating statistics from the logs is a load intensive task, so it is advisable to select only a small time span and to disable the general_log and empty its table once monitoring is not required any more.');
            ?>
            </p>
        </div>
    <?php } ?>
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

    <?php if (!PMA_DRIZZLE) { ?>
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
    <?php } ?>

    <table border="0" class="clearfloat" id="chartGrid">

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

/* Builds a <select> list for refresh rates */
function refreshList($name, $defaultRate=5, $refreshRates=Array(1, 2, 5, 10, 20, 40, 60, 120, 300, 600))
{
?>
    <select name="<?php echo $name; ?>" id="id_<?php echo $name; ?>">
        <?php
            foreach ($refreshRates as $rate) {
                $selected = ($rate == $defaultRate)?' selected="selected"':'';

                if ($rate<60) {
                    echo '<option value="' . $rate . '"' . $selected . '>' . sprintf(_ngettext('%d second', '%d seconds', $rate), $rate) . '</option>';
                } else {
                    echo '<option value="' . $rate . '"' . $selected . '>' . sprintf(_ngettext('%d minute', '%d minutes', $rate/60), $rate/60) . '</option>';
                }
            }
        ?>
    </select>
<?php
}

/**
 * cleanup of some deprecated values
 *
 * @param array &$server_status
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

/**
 * Sends the footer
 */
require './libraries/footer.inc.php';
?>
