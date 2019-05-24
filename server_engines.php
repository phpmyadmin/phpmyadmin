<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server engines page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var EnginesController $controller */
$controller = $containerBuilder->get(EnginesController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

if (isset($_GET['engine']) && $_GET['engine'] !== '') {
    $response->addHTML($controller->show([
        'engine' => $_GET['engine'],
        'page' => $_GET['page'] ?? null,
    ]));
} else {
    $response->addHTML($controller->index());
}
