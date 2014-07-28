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
            $ret = PMA_getJsonForChartingData();
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
            $return = PMA_getJsonForLogDataTypeSlow($start, $end);
            PMA_Response::getInstance()->addJSON('message', $return);
            exit;
        }

        if ($_REQUEST['type'] == 'general') {
            $return = PMA_getJsonForLogDataTypeGeneral($start, $end);
            PMA_Response::getInstance()->addJSON('message', $return);
            exit;
        }
    }

    if (isset($_REQUEST['logging_vars'])) {
        $loggingVars = PMA_getJsonForLoggingVars();
        PMA_Response::getInstance()->addJSON('message', $loggingVars);
        exit;
    }

    if (isset($_REQUEST['query_analyzer'])) {
        $return = PMA_getJsonForQueryAnalyzer();
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
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(PMA_getHtmlForMonitor($ServerStatusData));
$response->addHTML(PMA_getHtmlForClientSideDataAndLinks($ServerStatusData));
$response->addHTML('</div>');
exit;

?>
