<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\table\TableStructureController;
use PMA\libraries\controllers\Table;
use PMA\libraries\Response;

require_once 'libraries/common.inc.php';
require_once 'libraries/config/messages.inc.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory('PMA\libraries\controllers\table\TableStructureController');
$container->alias(
    'TableStructureController',
    'PMA\libraries\controllers\table\TableStructureController'
);
$container->set('PMA\libraries\Response', Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

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
    'url_query' => &$GLOBALS['url_query'],
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
