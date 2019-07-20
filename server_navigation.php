<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server Navigation page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\NavigationController;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var NavigationController $controller */
$controller = $containerBuilder->get(NavigationController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

if ($response->isAjax()
    && isset($_GET['type']) && $_GET['type'] === 'getval') {
    $response->addJSON($controller->getValue([
        'varName' => $_GET['varName'] ?? null,
    ]));
} else {
    $response->addHTML($controller->index([
        'filter' => $_REQUEST['filter'] ?? null,
    ]));
}
