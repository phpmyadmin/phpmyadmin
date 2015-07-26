<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns
 *
 * @package PhpMyAdmin
 */

namespace PMA;

require_once 'libraries/common.inc.php';
require_once 'libraries/tbl_common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/config/page_settings.class.php';
require_once 'libraries/bookmark.lib.php';
require_once 'libraries/di/Container.class.php';
require_once 'libraries/controllers/StructureController.class.php';

$container = DI\Container::getDefaultContainer();
$container->factory('PMA\Controllers\StructureController');
$container->alias(
    'StructureController', 'PMA\Controllers\StructureController'
);

global $db, $table, $pos, $db_is_system_schema, $total_num_tables, $tables, $num_tables;
/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'db' => $db,
    'table' => $table,
    'type' => 'table',
    'url_query' => &$GLOBALS['url_query'],
    'pos' => $pos,
    'db_is_system_schema' => $db_is_system_schema,
    'num_tables' => $num_tables,
    'total_num_tables' => $total_num_tables,
    'tables' => $tables
);

/** @var Controllers\StructureController $controller */
$controller = $container->get('StructureController', $dependency_definitions);
$controller->indexAction();
