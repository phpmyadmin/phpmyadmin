<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server binary log page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\BinlogController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var BinlogController $controller */
$controller = $containerBuilder->get(BinlogController::class);

/** @var Response $response */
$response = $container->get('response');

$response->addHTML($controller->indexAction([
    'log' => $_POST['log'] ?? null,
    'pos' => $_POST['pos'] ?? null,
    'is_full_query' => $_POST['is_full_query'] ?? null,
]));
