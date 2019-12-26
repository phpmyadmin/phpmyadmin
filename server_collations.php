<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server charsets and collations page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var CollationsController $controller */
$controller = $containerBuilder->get(CollationsController::class);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);
$response->addHTML($controller->indexAction());
