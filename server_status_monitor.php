<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server status monitor feature
 *
 * @package PhpMyAdmin
 */

use PMA\libraries\ServerStatusData;
use PMA\libraries\Response;

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/server_status_monitor.lib.php';
require_once 'libraries/replication.inc.php';
require_once 'libraries/replication_gui.lib.php';

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
            $ret = PMA_getJsonForChartingData();
            $response->addJSON('message', $ret);
            exit;
        }
    }

    if (isset($_REQUEST['log_data'])) {

        $start = intval($_REQUEST['time_start']);
        $end = intval($_REQUEST['time_end']);

        if ($_REQUEST['type'] == 'slow') {
            $return = PMA_getJsonForLogDataTypeSlow($start, $end);
            $response->addJSON('message', $return);
            exit;
        }

        if ($_REQUEST['type'] == 'general') {
            $return = PMA_getJsonForLogDataTypeGeneral($start, $end);
            $response->addJSON('message', $return);
            exit;
        }
    }

    if (isset($_REQUEST['logging_vars'])) {
        $loggingVars = PMA_getJsonForLoggingVars();
        $response->addJSON('message', $loggingVars);
        exit;
    }

    if (isset($_REQUEST['query_analyzer'])) {
        $return = PMA_getJsonForQueryAnalyzer();
        $response->addJSON('message', $return);
        exit;
    }
}

/**
 * JS Includes
 */
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('jquery/jquery.sortableTable.js');
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');
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
$ServerStatusData = new ServerStatusData();

/**
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(PMA_getHtmlForMonitor($ServerStatusData));
$response->addHTML(PMA_getHtmlForClientSideDataAndLinks($ServerStatusData));
$response->addHTML('</div>');
exit;
