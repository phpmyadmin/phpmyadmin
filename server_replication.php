<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server replications
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\ReplicationController;
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

/** @var ReplicationController $controller */
$controller = $containerBuilder->get(ReplicationController::class);

/** @var ReplicationGui $replicationGui */
$replicationGui = $containerBuilder->get('replication_gui');

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('server/privileges.js');
$scripts->addFile('replication.js');
$scripts->addFile('vendor/zxcvbn.js');

if (isset($_POST['url_params']) && is_array($_POST['url_params'])) {
    $GLOBALS['url_params'] = $_POST['url_params'];
}

if ($dbi->isSuperuser()) {
    $replicationGui->handleControlRequest();
}

$response->addHTML(
    $controller->index(
        [
            'mr_configure' => $_POST['mr_configure'] ?? null,
            'sl_configure' => $_POST['sl_configure'] ?? null,
            'repl_clear_scr' => $_POST['repl_clear_scr'] ?? null,
        ],
        $replicationGui
    )
);
