<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server status monitor feature
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/server_common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

$response = Response::getInstance();

$statusMonitor = new Monitor();
$statusData = new Data();

/**
 * Ajax request
 */
if ($response->isAjax()) {
    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');

    // real-time charting data
    if (isset($_POST['chart_data'])) {
        switch ($_POST['type']) {
            case 'chartgrid': // Data for the monitor
                $ret = $statusMonitor->getJsonForChartingData();
                $response->addJSON('message', $ret);
                exit;
        }
    }

    if (isset($_POST['log_data'])) {
        $start = intval($_POST['time_start']);
        $end = intval($_POST['time_end']);

        if ($_POST['type'] == 'slow') {
            $return = $statusMonitor->getJsonForLogDataTypeSlow($start, $end);
            $response->addJSON('message', $return);
            exit;
        }

        if ($_POST['type'] == 'general') {
            $return = $statusMonitor->getJsonForLogDataTypeGeneral($start, $end);
            $response->addJSON('message', $return);
            exit;
        }
    }

    if (isset($_POST['logging_vars'])) {
        $loggingVars = $statusMonitor->getJsonForLoggingVars();
        $response->addJSON('message', $loggingVars);
        exit;
    }

    if (isset($_POST['query_analyzer'])) {
        $return = $statusMonitor->getJsonForQueryAnalyzer();
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
 * Output
 */
$response->addHTML('<div>');
$response->addHTML($statusData->getMenuHtml());
$response->addHTML($statusMonitor->getHtmlForMonitor($statusData));
$response->addHTML($statusMonitor->getHtmlForClientSideDataAndLinks($statusData));
$response->addHTML('</div>');
exit;
