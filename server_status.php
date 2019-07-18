<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server status page: processes, connections and traffic
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\StatusController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ReplicationGui;
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

/** @var StatusController $controller */
$controller = $containerBuilder->get(StatusController::class);

/** @var ReplicationGui $replicationGui */
$replicationGui = $containerBuilder->get('replication_gui');

$response->addHTML($controller->index($replicationGui));
