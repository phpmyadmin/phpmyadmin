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

use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Gets some core libraries
 */
require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Table\TableSearchController');
$container->alias(
    'TableSearchController',
    'PhpMyAdmin\Controllers\Table\TableSearchController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'searchType' => 'zoom',
    'url_query' => &$url_query
];

/** @var PhpMyAdmin\Controllers\Table\TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
