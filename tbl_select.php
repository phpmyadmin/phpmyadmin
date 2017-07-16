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

use PhpMyAdmin\Controllers\Table\TableSearchController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/tbl_common.inc.php';

$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Table\TableSearchController');
$container->alias(
    'TableSearchController', 'PhpMyAdmin\Controllers\Table\TableSearchController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'searchType' => 'normal',
    'url_query' => &$url_query
);

/** @var TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
