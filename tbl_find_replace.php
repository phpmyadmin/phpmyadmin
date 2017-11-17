<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles find and replace tab
 *
 * Displays find and replace form, allows previewing and do the replacing
 *
 * @package PhpMyAdmin
 */

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

$dependency_definitions = array(
    'searchType' => 'replace',
    'url_query' => &$url_query
);

/** @var PhpMyAdmin\Controllers\Table\TableSearchController $controller */
$controller = $container->get('TableSearchController', $dependency_definitions);
$controller->indexAction();
