<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server status monitor feature
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/ServerStatusData.class.php';
require_once 'libraries/server_status_monitor.lib.php';
if (PMA_DRIZZLE) {
    $server_master_status = false;
    $server_slave_status = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

/**
 * Ajax request
 */
if (isset($_REQUEST['ajax_request']) && $_REQUEST['ajax_request'] == true) {
    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');

    // real-time charting data
    if (isset($_REQUEST['chart_data'])) {
        switch($_REQUEST['type']) {
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
                            $result = $GLOBALS['dbi']->query('SHOW PROCESSLIST');
                            $ret[$chart_id][$node_id][$point_id]['value']
                                = $GLOBALS['dbi']->numRows($result);
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
                $statusVarValues = $GLOBALS['dbi']->fetchResult(
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
                $serverVarValues = $GLOBALS['dbi']->fetchResult(
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

            PMA_Response::getInstance()->addJSON('message', $ret);
            exit;
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

            $result = $GLOBALS['dbi']->tryQuery($q);

            $return = array('rows' => array(), 'sum' => array());
            $type = '';

            while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
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

            $GLOBALS['dbi']->freeResult($result);

            PMA_Response::getInstance()->addJSON('message', $return);
            exit;
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

            $result = $GLOBALS['dbi']->tryQuery($q);

            $return = array('rows' => array(), 'sum' => array());
            $type = '';
            $insertTables = array();
            $insertTablesFirst = -1;
            $i = 0;
            $removeVars = isset($_REQUEST['removeVariables'])
                && $_REQUEST['removeVariables'];

            while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
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
                                    strlen($row['argument']),
                                    2,
                                    2
                                )
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

            $GLOBALS['dbi']->freeResult($result);

            PMA_Response::getInstance()->addJSON('message', $return);
            exit;
        }
    }

    if (isset($_REQUEST['logging_vars'])) {
        if (isset($_REQUEST['varName']) && isset($_REQUEST['varValue'])) {
            $value = PMA_Util::sqlAddSlashes($_REQUEST['varValue']);
            if (! is_numeric($value)) {
                $value="'" . $value . "'";
            }

            if (! preg_match("/[^a-zA-Z0-9_]+/", $_REQUEST['varName'])) {
                $GLOBALS['dbi']->query(
                    'SET GLOBAL ' . $_REQUEST['varName'] . ' = ' . $value
                );
            }

        }

        $loggingVars = $GLOBALS['dbi']->fetchResult(
            'SHOW GLOBAL VARIABLES WHERE Variable_name IN'
            . ' ("general_log","slow_query_log","long_query_time","log_output")',
            0,
            1
        );
        PMA_Response::getInstance()->addJSON('message', $loggingVars);
        exit;
    }

    if (isset($_REQUEST['query_analyzer'])) {
        $return = array();

        if (strlen($_REQUEST['database'])) {
            $GLOBALS['dbi']->selectDb($_REQUEST['database']);
        }

        if ($profiling = PMA_Util::profilingSupported()) {
            $GLOBALS['dbi']->query('SET PROFILING=1;');
        }

        // Do not cache query
        $query = preg_replace(
            '/^(\s*SELECT)/i',
            '\\1 SQL_NO_CACHE',
            $_REQUEST['query']
        );

        $result = $GLOBALS['dbi']->tryQuery($query);
        $return['affectedRows'] = $GLOBALS['cached_affected_rows'];

        $result = $GLOBALS['dbi']->tryQuery('EXPLAIN ' . $query);
        while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
            $return['explain'][] = $row;
        }

        // In case an error happened
        $return['error'] = $GLOBALS['dbi']->getError();

        $GLOBALS['dbi']->freeResult($result);

        if ($profiling) {
            $return['profiling'] = array();
            $result = $GLOBALS['dbi']->tryQuery(
                'SELECT seq,state,duration FROM INFORMATION_SCHEMA.PROFILING'
                . ' WHERE QUERY_ID=1 ORDER BY seq'
            );
            while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                $return['profiling'][]= $row;
            }
            $GLOBALS['dbi']->freeResult($result);
        }

        PMA_Response::getInstance()->addJSON('message', $return);
        exit;
    }
}

/**
 * JS Includes
 */
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('jquery/jquery.json-2.4.js');
$scripts->addFile('jquery/jquery.sortableTable.js');
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
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
$scripts->addFile('jqplot/plugins/jqplot.byteFormatter.js');
$scripts->addFile('date.js');

$scripts->addFile('server_status_monitor.js');
$scripts->addFile('server_status_sorter.js');


/**
 * start output
 */
$ServerStatusData = new PMA_ServerStatusData();

/**
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(PMA_getHtmlForMonitor($ServerStatusData));
$response->addHTML(PMA_getHtmlForClientSideDataAndLinks($ServerStatusData));
$response->addHTML('</div>');
exit;

?>
