<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database structure manipulation
 *
 * @package PhpMyAdmin
 */

namespace PMA;

require_once 'libraries/common.inc.php';
require_once 'libraries/db_common.inc.php';
require_once 'libraries/db_info.inc.php';
require_once 'libraries/di/Container.class.php';
require_once 'libraries/controllers/StructureController.class.php';

$container = DI\Container::getDefaultContainer();
$container->factory('PMA\Controllers\StructureController');
$container->alias(
    'StructureController', 'PMA\Controllers\StructureController'
);

global $db, $table, $pos, $db_is_system_schema, $total_num_tables, $tables,
       $num_tables, $tbl_is_view, $tbl_storage_engine, $table_info_num_rows, $tbl_collation, $showtable;
/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'db' => $db,
    'table' => $table,
    'type' => 'db',
    'url_query' => &$GLOBALS['url_query'],
    'pos' => $pos,
    'db_is_system_schema' => $db_is_system_schema,
    'num_tables' => $num_tables,
    'total_num_tables' => $total_num_tables,
    'tables' => $tables,
    'tbl_is_view' => $tbl_is_view,
    'tbl_storage_engine' => $tbl_storage_engine,
    'table_info_num_rows' => $table_info_num_rows,
    'tbl_collation' => $tbl_collation,
    'showtable' => $showtable
);

/** @var Controllers\StructureController $controller */
$controller = $container->get('StructureController', $dependency_definitions);
$controller->indexAction();
