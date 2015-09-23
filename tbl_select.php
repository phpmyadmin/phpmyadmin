<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table search tab
 *
 * display table search form, create SQL query from form data
 * and call PMA_executeQueryAndSendQueryResponse() to execute it
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/tbl_common.inc.php';
require_once 'libraries/tbl_info.inc.php';
require_once 'libraries/di/Container.class.php';
require_once 'libraries/Response.class.php';
require_once 'libraries/controllers/TableSearchController.class.php';

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
    'searchType' => 'normal',
    'url_query' => &$url_query
);

/** @var PMA\Controllers\TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
