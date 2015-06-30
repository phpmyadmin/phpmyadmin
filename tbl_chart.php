<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */

namespace PMA;

require_once 'libraries/di/Container.class.php';
require_once 'libraries/controllers/table/TableChartController.class.php';

$container = DI\Container::getDefaultContainer();

$container->factory('PMA\Controllers\Table\TableChartController');
$container->alias('TableChartController', 'PMA\Controllers\Table\TableChartController');

/** @var Controllers\Table\TableChartController $controller */
$controller = $container->get('TableChartController');
$controller->indexAction();
