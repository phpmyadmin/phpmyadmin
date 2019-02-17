<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server variables page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\VariablesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(VariablesController::class);
$container->set(Response::class, Response::getInstance());
$container->alias('response', Response::class);

/** @var VariablesController $controller */
$controller = $container->get(
    VariablesController::class,
    []
);

/** @var Response $response */
$response = $container->get(Response::class);

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
