<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table zoom search tab
 *
 * display table zoom search form, create SQL queries from form data
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once 'libraries/tbl_common.inc.php';
require_once 'libraries/tbl_info.inc.php';
require_once './libraries/di/Container.class.php';
require_once './libraries/Response.class.php';
require_once './libraries/controllers/TableSearchController.class.php';

use PMA\DI;

$container = DI\Container::getDefaultContainer();
$container->factory('PMA\Controllers\Table\TableSearchController');
$container->alias(
    'TableSearchController', 'PMA\Controllers\Table\TableSearchController'
);
$container->set('PMA_Response', PMA_Response::getInstance());
$container->alias('response', 'PMA_Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'searchType' => 'zoom',
    'url_query' => &$url_query
);

/** @var PMA\Controllers\Table\TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
