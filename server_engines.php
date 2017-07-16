<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server engines page.
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Controllers\Server\ServerEnginesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

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
    'ServerEnginesController', array()
);
$controller->indexAction();
