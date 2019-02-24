<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the server status > processes list
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\ProcessesController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/server_common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

$response = Response::getInstance();

$controller = new ProcessesController(
    $response,
    $GLOBALS['dbi'],
    new Data()
);

if ($response->isAjax() && ! empty($_POST['kill'])) {
    $response->addJSON($controller->kill([
        'kill' => $_POST['kill'],
    ]));
} elseif ($response->isAjax() && ! empty($_POST['refresh'])) {
    $response->addHTML($controller->refresh([
        'showExecuting' => $_POST['showExecuting'] ?? null,
        'full' => $_POST['full'] ?? null,
        'column_name' => $_POST['column_name'] ?? null,
        'order_by_field' => $_POST['order_by_field'] ?? null,
        'sort_order' => $_POST['sort_order'] ?? null,
    ]));
} else {
    $header = $response->getHeader();
    $scripts = $header->getScripts();
    $scripts->addFile('server_status_processes.js');

    $response->addHTML($controller->index([
        'showExecuting' => $_POST['showExecuting'] ?? null,
        'full' => $_POST['full'] ?? null,
        'column_name' => $_POST['column_name'] ?? null,
        'order_by_field' => $_POST['order_by_field'] ?? null,
        'sort_order' => $_POST['sort_order'] ?? null,
    ]));
}
