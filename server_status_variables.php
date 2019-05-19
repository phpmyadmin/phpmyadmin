<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays a list of server status variables
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\VariablesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/server_common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

/** @var Response $response */
$response = $container->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

$containerBuilder->set('status_data', new Data());
/** @var VariablesController $controller */
$controller = $containerBuilder->get(VariablesController::class);

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('server_status_variables.js');
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('server_status_sorter.js');

$response->addHTML($controller->index([
    'flush' => $_POST['flush'] ?? null,
    'filterAlert' => $_POST['filterAlert'] ?? null,
    'filterText' => $_POST['filterText'] ?? null,
    'filterCategory' => $_POST['filterCategory'] ?? null,
    'dontFormat' => $_POST['dontFormat'] ?? null,
]));
