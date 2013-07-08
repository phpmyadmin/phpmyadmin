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

            PMA_DBI_free_result($result);

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
        PMA_Response::getInstance()->addJSON('message', $loggingVars);
        exit;
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

$scripts->addFile('server_status_monitor.js');
$scripts->addFile('server_status_sorter.js');


/**
 * start output
 */
$ServerStatusData = new PMA_ServerStatusData();

/**
 * Define some data needed on the client side
 */
$input = '<input type="hidden" name="%s" value="%s" />';
$form  = '<form id="js_data" class="hide">';
$form .= sprintf($input, 'server_time', microtime(true) * 1000);
$form .= sprintf($input, 'server_os', PHP_OS);
$form .= sprintf($input, 'is_superuser', PMA_isSuperuser());
$form .= sprintf($input, 'server_db_isLocal', $ServerStatusData->db_isLocal);
$form .= '</form>';
/**
 * Define some links used on client side
 */
$links  = '<div id="profiling_docu" class="hide">';
$links .= PMA_Util::showMySQLDocu('general-thread-states', 'general-thread-states');
$links .= '</div>';
$links .= '<div id="explain_docu" class="hide">';
$links .= PMA_Util::showMySQLDocu('explain-output', 'explain-output');
$links .= '</div>';

/**
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(getPrintMonitorHtml($ServerStatusData));
$response->addHTML($form);
$response->addHTML($links);
$response->addHTML('</div>');
exit;

/**
 * Prints html with monitor
 *
 * @param object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function getPrintMonitorHtml($ServerStatusData)
{
    $retval  = '<div class="tabLinks">';
    $retval .= '<a href="#pauseCharts">';
    $retval .= PMA_Util::getImage('play.png') . __('Start Monitor');
    $retval .= '</a>';
    $retval .= '<a href="#settingsPopup" class="popupLink">';
    $retval .= PMA_Util::getImage('s_cog.png') .  __('Settings');
    $retval .= '</a>';
    if (! PMA_DRIZZLE) {
        $retval .= '<a href="#monitorInstructionsDialog">';
        $retval .= PMA_Util::getImage('b_help.png') . __('Instructions/Setup');
    }
    $retval .= '<a href="#endChartEditMode" style="display:none;">';
    $retval .= PMA_Util::getImage('s_okay.png');
    $retval .= __('Done dragging (rearranging) charts');
    $retval .= '</a>';
    $retval .= '</div>';

    $retval .= '<div class="popupContent settingsPopup">';
    $retval .= '<a href="#addNewChart">';
    $retval .= PMA_Util::getImage('b_chart.png') . __('Add chart');
    $retval .= '</a>';
    $retval .= '<a href="#rearrangeCharts">';
    $retval .= PMA_Util::getImage('b_tblops.png') . __('Enable charts dragging');
    $retval .= '</a>';
    $retval .= '<div class="clearfloat paddingtop"></div>';
    $retval .= '<div class="floatleft">';
    $retval .= __('Refresh rate') . '<br />';
    $retval .= PMA_getRefreshList(
        'gridChartRefresh',
        5,
        Array(2, 3, 4, 5, 10, 20, 40, 60, 120, 300, 600, 1200)
    );
    $retval .= '<br />';
    $retval .= '</div>';
    $retval .= '<div class="floatleft">';
    $retval .= __('Chart columns');
    $retval .= '<br />';
    $retval .= '<select name="chartColumns">';
    $retval .= '<option>1</option>';
    $retval .= '<option>2</option>';
    $retval .= '<option>3</option>';
    $retval .= '<option>4</option>';
    $retval .= '<option>5</option>';
    $retval .= '<option>6</option>';
    $retval .= '<option>7</option>';
    $retval .= '<option>8</option>';
    $retval .= '<option>9</option>';
    $retval .= '<option>10</option>';
    $retval .= '</select>';
    $retval .= '</div>';
    $retval .= '<div class="clearfloat paddingtop">';
    $retval .= '<b>' . __('Chart arrangement') . '</b> ';
    $retval .= PMA_Util::showHint(
        __(
            'The arrangement of the charts is stored to the browsers local storage. '
            . 'You may want to export it if you have a complicated set up.'
        )
    );
    $retval .= '<br/>';
    $retval .= '<a class="ajax" href="#importMonitorConfig">';
    $retval .= __('Import');
    $retval .= '</a>';
    $retval .= '&nbsp;&nbsp;';
    $retval .= '<a class="disableAjax" href="#exportMonitorConfig">';
    $retval .= __('Export');
    $retval .= '</a>';
    $retval .= '&nbsp;&nbsp;';
    $retval .= '<a href="#clearMonitorConfig">';
    $retval .= __('Reset to default');
    $retval .= '</a>';
    $retval .= '</div>';
    $retval .= '</div>';

    $retval .= '<div id="monitorInstructionsDialog" title="';
    $retval .= __('Monitor Instructions') . '" style="display:none;">';
    $retval .= __(
        'The phpMyAdmin Monitor can assist you in optimizing the server'
        . ' configuration and track down time intensive queries. For the latter you'
        . ' will need to set log_output to \'TABLE\' and have either the'
        . ' slow_query_log or general_log enabled. Note however, that the'
        . ' general_log produces a lot of data and increases server load'
        . ' by up to 15%'
    );

    if (PMA_MYSQL_INT_VERSION < 50106) {
        $retval .= '<p>';
        $retval .= PMA_Util::getImage('s_attention.png');
        $retval .=  __(
            'Unfortunately your Database server does not support logging to table,'
            . ' which is a requirement for analyzing the database logs with'
            . ' phpMyAdmin. Logging to table is supported by MySQL 5.1.6 and'
            . ' onwards. You may still use the server charting features however.'
        );
        $retval .= '</p>';
    } else {
        $retval .= '<p></p>';
        $retval .= '<img class="ajaxIcon" src="';
        $retval .= $GLOBALS['pmaThemeImage'] . 'ajax_clock_small.gif"';
        $retval .= ' alt="' . __('Loading') . '" />';
        $retval .= '<div class="ajaxContent"></div>';
        $retval .= '<div class="monitorUse" style="display:none;">';
        $retval .= '<p></p>';
        $retval .= '<strong>';
        $retval .= __('Using the monitor:');
        $retval .= '</strong><p>';
        $retval .= __(
            'Your browser will refresh all displayed charts in a regular interval.'
            . ' You may add charts and change the refresh rate under \'Settings\','
            . ' or remove any chart using the cog icon on each respective chart.'
        );
        $retval .= '</p><p>';
        $retval .= __(
            'To display queries from the logs, select the relevant time span on any'
            . ' chart by holding down the left mouse button and panning over the'
            . ' chart. Once confirmed, this will load a table of grouped queries,'
            . ' there you may click on any occuring SELECT statements to further'
            . ' analyze them.'
        );
        $retval .= '</p>';
        $retval .= '<p>';
        $retval .= PMA_Util::getImage('s_attention.png');
        $retval .= '<strong>';
        $retval .= __('Please note:');
        $retval .= '</strong><br />';
        $retval .= __(
            'Enabling the general_log may increase the server load by'
            . ' 5-15%. Also be aware that generating statistics from the logs is a'
            . ' load intensive task, so it is advisable to select only a small time'
            . ' span and to disable the general_log and empty its table once'
            . ' monitoring is not required any more.'
        );
        $retval .= '</p>';
        $retval .= '</div>';
    }
    $retval .= '</div>';

    $retval .= '<div id="addChartDialog" title="' . __('Add chart') . '" style="display:none;">';
    $retval .= '<div id="tabGridVariables">';
    $retval .= '<p><input type="text" name="chartTitle" value="' . __('Chart Title') . '" /></p>';
    $retval .= '<input type="radio" name="chartType" value="preset" id="chartPreset" />';
    $retval .= '<label for="chartPreset">' . __('Preset chart') . '</label>';
    $retval .= '<select name="presetCharts"></select><br/>';
    $retval .= '<input type="radio" name="chartType" value="variable" id="chartStatusVar" checked="checked" />';
    $retval .= '<label for="chartStatusVar">';
    $retval .= __('Status variable(s)');
    $retval .= '</label><br/>';
    $retval .= '<div id="chartVariableSettings">';
    $retval .= '<label for="chartSeries">' . __('Select series:') . '</label><br />';
    $retval .= '<select id="chartSeries" name="varChartList" size="1">';
    $retval .= '<option>' . __('Commonly monitored') . '</option>';
    $retval .= '<option>Processes</option>';
    $retval .= '<option>Questions</option>';
    $retval .= '<option>Connections</option>';
    $retval .= '<option>Bytes_sent</option>';
    $retval .= '<option>Bytes_received</option>';
    $retval .= '<option>Threads_connected</option>';
    $retval .= '<option>Created_tmp_disk_tables</option>';
    $retval .= '<option>Handler_read_first</option>';
    $retval .= '<option>Innodb_buffer_pool_wait_free</option>';
    $retval .= '<option>Key_reads</option>';
    $retval .= '<option>Open_tables</option>';
    $retval .= '<option>Select_full_join</option>';
    $retval .= '<option>Slow_queries</option>';
    $retval .= '</select><br />';
    $retval .= '<label for="variableInput">';
    $retval .= __('or type variable name:');
    $retval .= ' </label>';
    $retval .= '<input type="text" name="variableInput" id="variableInput" />';
    $retval .= '<p></p>';
    $retval .= '<input type="checkbox" name="differentialValue"';
    $retval .= ' id="differentialValue" value="differential" checked="checked" />';
    $retval .= '<label for="differentialValue">';
    $retval .= __('Display as differential value');
    $retval .= '</label><br />';
    $retval .= '<input type="checkbox" id="useDivisor" name="useDivisor" value="1" />';
    $retval .= '<label for="useDivisor">' . __('Apply a divisor') . '</label>';
    $retval .= '<span class="divisorInput" style="display:none;">';
    $retval .= '<input type="text" name="valueDivisor" size="4" value="1" />';
    $retval .= '(<a href="#kibDivisor">' . __('KiB') . '</a>, ';
    $retval .= '<a href="#mibDivisor">' . __('MiB') . '</a>)';
    $retval .= '</span><br />';
    $retval .= '<input type="checkbox" id="useUnit" name="useUnit" value="1" />';
    $retval .= '<label for="useUnit">';
    $retval .= __('Append unit to data values');
    $retval .= '</label>';
    $retval .= '<span class="unitInput" style="display:none;">';
    $retval .= '<input type="text" name="valueUnit" size="4" value="" />';
    $retval .= '</span>';
    $retval .= '<p>';
    $retval .= '<a href="#submitAddSeries"><b>' . __('Add this series') . '</b></a>';
    $retval .= '<span id="clearSeriesLink" style="display:none;">';
    $retval .= ' | <a href="#submitClearSeries">' . __('Clear series') . '</a>';
    $retval .= '</span>';
    $retval .= '</p>';
    $retval .= __('Series in Chart:');
    $retval .= '<br/>';
    $retval .= '<span id="seriesPreview">';
    $retval .= '<i>' . __('None') . '</i>';
    $retval .= '</span>';
    $retval .= '</div>';
    $retval .= '</div>';
    $retval .= '</div>';

    if (! PMA_DRIZZLE) {
        $retval .= '<div id="logAnalyseDialog" title="';
        $retval .= __('Log statistics') . '" style="display:none;">';
        $retval .= '<p>' . __('Selected time range:');
        $retval .= '<input type="text" name="dateStart" class="datetimefield" value="" /> - ';
        $retval .= '<input type="text" name="dateEnd" class="datetimefield" value="" />';
        $retval .= '</p>';
        $retval .= '<input type="checkbox" id="limitTypes" value="1" checked="checked" />';
        $retval .= '<label for="limitTypes">';
        $retval .= __('Only retrieve SELECT,INSERT,UPDATE and DELETE Statements');
        $retval .= '</label>';
        $retval .= '<br/>';
        $retval .= '<input type="checkbox" id="removeVariables" value="1" checked="checked" />';
        $retval .= '<label for="removeVariables">';
        $retval .= __('Remove variable data in INSERT statements for better grouping');
        $retval .= '</label>';
        $retval .= '<p>';
        $retval .= __('Choose from which log you want the statistics to be generated from.');
        $retval .= '</p>';
        $retval .= '<p>';
        $retval .= __('Results are grouped by query text.');
        $retval .= '</p>';
        $retval .= '</div>';
        $retval .= '<div id="queryAnalyzerDialog" title="';
        $retval .= __('Query analyzer') . '" style="display:none;">';
        $retval .= '<textarea id="sqlquery"> </textarea>';
        $retval .= '<p></p>';
        $retval .= '<div class="placeHolder"></div>';
        $retval .= '</div>';
    }

    $retval .= '<table class="clearfloat" id="chartGrid"></table>';
    $retval .= '<div id="logTable">';
    $retval .= '<br/>';
    $retval .= '</div>';

    $retval .= '<script type="text/javascript">';
    $retval .= 'variableNames = [ ';
    $i=0;
    foreach ($ServerStatusData->status as $name=>$value) {
        if (is_numeric($value)) {
            if ($i++ > 0) {
                $retval .= ", ";
            }
            $retval .= "'" . $name . "'";
        }
    }
    $retval .= '];';
    $retval .= '</script>';

    return $retval;
}

/**
 * Builds a <select> list for refresh rates
 *
 * @param string $name         Name of select
 * @param int    $defaultRate  Currently chosen rate
 * @param array  $refreshRates List of refresh rates
 *
 * @return string
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

?>
