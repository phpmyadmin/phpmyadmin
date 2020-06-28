<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\DatabaseInterface;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db_is_system_schema, $tbl_is_view, $tbl_storage_engine;
global $table_info_num_rows, $tbl_collation, $showtable;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get('dbi');

/** @var string $db */
$db = $containerBuilder->getParameter('db');

/** @var string $table */
$table = $containerBuilder->getParameter('table');

$dbi->selectDb($db);
$table_class_object = $dbi->getTable($db, $table);
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
$dependency_definitions = [
    'db_is_system_schema' => $db_is_system_schema,
    'tbl_is_view' => $tbl_is_view,
    'tbl_storage_engine' => $tbl_storage_engine,
    'table_info_num_rows' => $table_info_num_rows,
    'tbl_collation' => $tbl_collation,
    'showtable' => $GLOBALS['showtable'],
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition(StructureController::class);
$parameterBag = $containerBuilder->getParameterBag();
array_map(
    static function (string $parameterName, $value) use ($definition, $parameterBag) {
        $definition->replaceArgument($parameterName, $parameterBag->escapeValue($value));
    },
    array_keys($dependency_definitions),
    $dependency_definitions
);

/** @var StructureController $controller */
$controller = $containerBuilder->get(StructureController::class);
$controller->indexAction($containerBuilder);
