<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\ChartController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'db' => $container->get('db'),
    'table' => $container->get('table'),
    'sql_query' => &$GLOBALS['sql_query'],
    'url_query' => &$GLOBALS['url_query'],
    'cfg' => &$GLOBALS['cfg']
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition('table_chart_controller');
$definition->setArguments(array_merge($definition->getArguments(), $dependency_definitions));

/** @var ChartController $controller */
$controller = $containerBuilder->get('table_chart_controller');
$controller->indexAction();
