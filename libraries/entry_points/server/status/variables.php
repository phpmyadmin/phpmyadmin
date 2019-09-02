<?php
/**
 * Displays a list of server status variables
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\VariablesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

require_once ROOT_PATH . 'libraries/server_common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var VariablesController $controller */
$controller = $containerBuilder->get(VariablesController::class);

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('server/status/variables.js');
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('server/status/sorter.js');

$response->addHTML($controller->index([
    'flush' => $_POST['flush'] ?? null,
    'filterAlert' => $_POST['filterAlert'] ?? null,
    'filterText' => $_POST['filterText'] ?? null,
    'filterCategory' => $_POST['filterCategory'] ?? null,
    'dontFormat' => $_POST['dontFormat'] ?? null,
]));
