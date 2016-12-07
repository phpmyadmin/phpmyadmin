<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles find and replace tab
 *
 * Displays find and replace form, allows previewing and do the replacing
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Response;

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/tbl_common.inc.php';
require_once 'libraries/tbl_info.inc.php';

$container = \PMA\libraries\di\Container::getDefaultContainer();
$container->factory('PMA\libraries\controllers\table\TableSearchController');
$container->alias(
    'TableSearchController', 'PMA\libraries\controllers\table\TableSearchController'
);
$container->set('PMA\libraries\Response', Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

$dependency_definitions = array(
    'searchType' => 'replace',
    'url_query' => &$url_query
);

/** @var PMA\libraries\controllers\table\TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
