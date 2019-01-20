<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server databases page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\ServerDatabasesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(
    'PhpMyAdmin\Controllers\Server\ServerDatabasesController'
);
$container->alias(
    'ServerDatabasesController',
    'PhpMyAdmin\Controllers\Server\ServerDatabasesController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerDatabasesController $controller */
$controller = $container->get(
    'ServerDatabasesController',
    []
);
$controller->indexAction();
