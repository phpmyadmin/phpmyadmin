<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server plugins page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\PluginsController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());
$container->alias('response', Response::class);

/** @var PluginsController $controller */
$controller = $containerBuilder->get('server_plugins_controller');

/** @var Response $response */
$response = $container->get(Response::class);

$response->addHTML($controller->index());
