<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA_Response;

require_once 'libraries/common.inc.php';
require_once 'libraries/tbl_info.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/config/page_settings.class.php';
require_once 'libraries/bookmark.lib.php';
require_once 'libraries/di/Container.class.php';
require_once 'libraries/controllers/TableStructureController.class.php';
require_once 'libraries/Response.class.php';

$container = DI\Container::getDefaultContainer();
$container->factory('PMA\Controllers\TableStructureController');
$container->alias(
    'TableStructureController', 'PMA\Controllers\TableStructureController'
);
$container->set('PMA_Response', PMA_Response::getInstance());
$container->alias('response', 'PMA_Response');

global $db, $table, $db_is_system_schema, $tbl_is_view, $tbl_storage_engine,
    $table_info_num_rows, $tbl_collation, $showtable;
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
    'showtable' => $showtable
);

/** @var Controllers\TableStructureController $controller */
$controller = $container->get('TableStructureController', $dependency_definitions);
$controller->indexAction();
