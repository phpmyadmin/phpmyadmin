<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server status monitor feature
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/replication.inc.php';

$response = Response::getInstance();

/**
 * Ajax request
 */
if ($response->isAjax()) {
    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');

    // real-time charting data
    if (isset($_REQUEST['chart_data'])) {
        switch($_REQUEST['type']) {
        case 'chartgrid': // Data for the monitor
            $ret = Monitor::getJsonForChartingData();
            $response->addJSON('message', $ret);
            exit;
        }
    }

    if (isset($_REQUEST['log_data'])) {

        $start = intval($_REQUEST['time_start']);
        $end = intval($_REQUEST['time_end']);

        if ($_REQUEST['type'] == 'slow') {
            $return = Monitor::getJsonForLogDataTypeSlow($start, $end);
            $response->addJSON('message', $return);
            exit;
        }

        if ($_REQUEST['type'] == 'general') {
            $return = Monitor::getJsonForLogDataTypeGeneral($start, $end);
            $response->addJSON('message', $return);
            exit;
        }
    }

    if (isset($_REQUEST['logging_vars'])) {
        $loggingVars = Monitor::getJsonForLoggingVars();
        $response->addJSON('message', $loggingVars);
        exit;
    }

    if (isset($_REQUEST['query_analyzer'])) {
        $return = Monitor::getJsonForQueryAnalyzer();
        $response->addJSON('message', $return);
        exit;
    }
}

/**
 * JS Includes
 */
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('vendor/jquery/jquery.sortableTable.js');
// for charting
$scripts->addFile('vendor/jqplot/jquery.jqplot.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.pieRenderer.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.enhancedPieLegendRenderer.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.canvasTextRenderer.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.dateAxisRenderer.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.highlighter.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.cursor.js');
$scripts->addFile('jqplot/plugins/jqplot.byteFormatter.js');

$scripts->addFile('server_status_monitor.js');
$scripts->addFile('server_status_sorter.js');


/**
 * start output
 */
$serverStatusData = new Data();

/**
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($serverStatusData->getMenuHtml());
$response->addHTML(Monitor::getHtmlForMonitor($serverStatusData));
$response->addHTML(Monitor::getHtmlForClientSideDataAndLinks($serverStatusData));
$response->addHTML('</div>');
exit;
