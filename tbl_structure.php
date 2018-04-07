<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Controllers\Table\TableStructureController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Table\TableStructureController');
$container->alias(
    'TableStructureController',
    'PhpMyAdmin\Controllers\Table\TableStructureController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

global $db, $table, $db_is_system_schema, $tbl_is_view, $tbl_storage_engine,
    $table_info_num_rows, $tbl_collation, $showtable;
$GLOBALS['dbi']->selectDb($GLOBALS['db']);
$table_class_object = $GLOBALS['dbi']->getTable(
    $GLOBALS['db'],
    $GLOBALS['table']
);
$reread_info = $table_class_object->getStatusInfo(null, true);
$GLOBALS['showtable'] = $table_class_object->getStatusInfo(null, (isset($reread_info) && $reread_info ? true : false));
if ($table_class_object->isView()) {
    $tbl_is_view = true;
    $tbl_storage_engine = __('View');
} else {
    $tbl_is_view = false;
    $tbl_storage_engine = $table_class_object->getStorageEngine();
}
$tbl_collation = $table_class_object->getCollation();
$table_info_num_rows = $table_class_object->getNumRows();
/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'db' => $db,
    'table' => $table,
    'db_is_system_schema' => $db_is_system_schema,
    'tbl_is_view' => $tbl_is_view,
    'tbl_storage_engine' => $tbl_storage_engine,
    'table_info_num_rows' => $table_info_num_rows,
    'tbl_collation' => $tbl_collation,
    'showtable' => $GLOBALS['showtable']
);

/** @var TableStructureController $controller */
$controller = $container->get('TableStructureController', $dependency_definitions);
$controller->indexAction();
