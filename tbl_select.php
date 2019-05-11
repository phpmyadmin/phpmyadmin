<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table search tab
 *
 * display table search form, create SQL query from form data
 * and call Sql::executeQueryAndSendQueryResponse() to execute it
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

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());
$container->alias('response', Response::class);

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'db' => $container->get('db'),
    'table' => $container->get('table'),
    'searchType' => 'normal',
    'url_query' => &$url_query
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition('table_search_controller');
$definition->setArguments(array_merge($definition->getArguments(), $dependency_definitions));

/** @var SearchController $controller */
$controller = $containerBuilder->get('table_search_controller');
$controller->indexAction();
