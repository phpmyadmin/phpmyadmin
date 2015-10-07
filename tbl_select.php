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

use PMA\libraries\controllers\table\TableSearchController;

$container = \PMA\libraries\di\Container::getDefaultContainer();
$container->factory('PMA\libraries\controllers\table\TableSearchController');
$container->alias(
    'TableSearchController', 'PMA\libraries\controllers\table\TableSearchController'
);
$container->set('PMA\libraries\Response', PMA\libraries\Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'searchType' => 'normal',
    'url_query' => &$url_query
);

/** @var TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
