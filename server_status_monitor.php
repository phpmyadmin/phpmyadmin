<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server status monitor feature
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\MonitorController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/server_common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var MonitorController $controller */
$controller = $containerBuilder->get(MonitorController::class);

/**
 * Ajax request
 */
if ($response->isAjax()) {
    // Send with correct charset
    header('Content-Type: text/html; charset=UTF-8');
}

// real-time charting data
if ($response->isAjax() && isset($_POST['chart_data']) && $_POST['type'] === 'chartgrid') {
    $response->addJSON($controller->chartingData([
        'requiredData' => $_POST['requiredData'] ?? null,
    ]));
} elseif ($response->isAjax() && isset($_POST['log_data']) && $_POST['type'] === 'slow') {
    $response->addJSON($controller->logDataTypeSlow([
        'time_start' => $_POST['time_start'] ?? null,
        'time_end' => $_POST['time_end'] ?? null,
    ]));
} elseif ($response->isAjax() && isset($_POST['log_data']) && $_POST['type'] === 'general') {
    $response->addJSON($controller->logDataTypeGeneral([
        'time_start' => $_POST['time_start'] ?? null,
        'time_end' => $_POST['time_end'] ?? null,
        'limitTypes' => $_POST['limitTypes'] ?? null,
        'removeVariables' => $_POST['removeVariables'] ?? null,
    ]));
} elseif ($response->isAjax() && isset($_POST['logging_vars'])) {
    $response->addJSON($controller->loggingVars([
        'varName' => $_POST['varName'] ?? null,
        'varValue' => $_POST['varValue'] ?? null,
    ]));
} elseif ($response->isAjax() && isset($_POST['query_analyzer'])) {
    $response->addJSON($controller->queryAnalyzer([
        'database' => $_POST['database'] ?? null,
        'query' => $_POST['query'] ?? null,
    ]));
} else {
    $header = $response->getHeader();
    $scripts = $header->getScripts();
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

    $scripts->addFile('server/status/monitor.js');
    $scripts->addFile('server/status/sorter.js');

    $response->addHTML($controller->index());
}
