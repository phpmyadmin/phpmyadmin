<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Controllers\Table\TableIndexesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Index;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Table\TableIndexesController');
$container->alias(
    'TableIndexesController',
    'PhpMyAdmin\Controllers\Table\TableIndexesController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$db = $container->get('db');
$table = $container->get('table');
$dbi = $container->get('dbi');

if (!isset($_REQUEST['create_edit_table'])) {
    include_once 'libraries/tbl_common.inc.php';
}
if (isset($_REQUEST['index'])) {
    if (is_array($_REQUEST['index'])) {
        // coming already from form
        $index = new Index($_REQUEST['index']);
    } else {
        $index = $dbi->getTable($db, $table)->getIndex($_REQUEST['index']);
    }
} else {
    $index = new Index;
}

$dependency_definitions = array(
    "index" => $index
);

/** @var TableIndexesController $controller */
$controller = $container->get('TableIndexesController', $dependency_definitions);
$controller->indexAction();
