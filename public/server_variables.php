<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server variables page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\ServerVariablesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(
    'PhpMyAdmin\Controllers\Server\ServerVariablesController'
);
$container->alias(
    'ServerVariablesController',
    'PhpMyAdmin\Controllers\Server\ServerVariablesController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerVariablesController $controller */
$controller = $container->get(
    'ServerVariablesController',
    []
);
$controller->indexAction();
