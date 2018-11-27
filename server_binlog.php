<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles server binary log page.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(
    'PhpMyAdmin\Controllers\Server\ServerBinlogController'
);
$container->alias(
    'ServerBinlogController',
    'PhpMyAdmin\Controllers\Server\ServerBinlogController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var \PhpMyAdmin\Controllers\Server\ServerBinlogController $controller */
$controller = $container->get(
    'ServerBinlogController',
    []
);
$controller->indexAction();
