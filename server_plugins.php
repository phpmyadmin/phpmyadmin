<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server plugins page.
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Controllers\Server\ServerPluginsController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(
    'PhpMyAdmin\Controllers\Server\ServerPluginsController'
);
$container->alias(
    'ServerPluginsController',
    'PhpMyAdmin\Controllers\Server\ServerPluginsController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerPluginsController $controller */
$controller = $container->get(
    'ServerPluginsController', array()
);
$controller->indexAction();
