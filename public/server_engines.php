<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server engines page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\ServerEnginesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(
    'PhpMyAdmin\Controllers\Server\ServerEnginesController'
);
$container->alias(
    'ServerEnginesController',
    'PhpMyAdmin\Controllers\Server\ServerEnginesController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerEnginesController $controller */
$controller = $container->get(
    'ServerEnginesController',
    []
);
$controller->indexAction();
