<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays query statistics for the server
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\QueriesController;
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

/** @var QueriesController $controller */
$controller = $containerBuilder->get(QueriesController::class);

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('chart.js');
$scripts->addFile('vendor/jqplot/jquery.jqplot.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.pieRenderer.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.highlighter.js');
$scripts->addFile('vendor/jqplot/plugins/jqplot.enhancedPieLegendRenderer.js');
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('server/status/sorter.js');
$scripts->addFile('server/status/queries.js');

$response->addHTML($controller->index());
