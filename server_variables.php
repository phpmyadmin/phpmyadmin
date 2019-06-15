<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server variables page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\VariablesController;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var VariablesController $controller */
$controller = $containerBuilder->get(VariablesController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

if ($response->isAjax()
    && isset($_GET['type']) && $_GET['type'] === 'getval') {
    $response->addJSON($controller->getValue([
        'varName' => $_GET['varName'] ?? null,
    ]));
} elseif ($response->isAjax()
    && isset($_POST['type']) && $_POST['type'] === 'setval') {
    $response->addJSON($controller->setValue([
        'varName' => $_POST['varName'] ?? null,
        'varValue' => $_POST['varValue'] ?? null,
    ]));
} else {
    $response->addHTML($controller->index([
        'filter' => $_REQUEST['filter'] ?? null,
    ]));
}
