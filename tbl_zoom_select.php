<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table zoom search tab
 *
 * display table zoom search form, create SQL queries from form data
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\SearchController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Gets some core libraries
 */
require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

$container = Container::getDefaultContainer();
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'db' => $container->get('db'),
    'table' => $container->get('table'),
    'searchType' => 'zoom',
    'url_query' => &$url_query
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition('table_search_controller');
$definition->setArguments(array_merge($definition->getArguments(), $dependency_definitions));

/** @var SearchController $controller */
$controller = $containerBuilder->get('table_search_controller');
$controller->indexAction();
