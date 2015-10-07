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

$container = \PMA\libraries\di\Container::getDefaultContainer();
$container->factory('PMA\libraries\controllers\table\TableSearchController');
$container->alias(
    'TableSearchController', 'PMA\libraries\controllers\table\TableSearchController'
);
$container->set('PMA\libraries\Response', PMA\libraries\Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'searchType' => 'zoom',
    'url_query' => &$url_query
);

/** @var PMA\libraries\controllers\table\TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
