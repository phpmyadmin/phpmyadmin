<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display table relations for viewing and editing
 *
 * includes phpMyAdmin relations and InnoDB relations
 *
 * @todo fix name handling: currently names with dots (.) are not properly handled
 * for internal relations (but foreign keys relations are correct)
 * @todo foreign key constraints require both fields being of equal type and size
 * @todo check foreign fields to be from same type and size, all other makes no sense
 * @todo if above todos are fullfilled we can add all fields meet requirements
 * in the select dropdown
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\RelationController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(RelationController::class);
$container->set(Response::class, Response::getInstance());
$container->alias('response', Response::class);

/* Define dependencies for the concerned controller */
$db = $container->get('db');
$table = $container->get('table');

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

$options_array = [
    'CASCADE' => 'CASCADE',
    'SET_NULL' => 'SET NULL',
    'NO_ACTION' => 'NO ACTION',
    'RESTRICT' => 'RESTRICT',
];
$relation = new Relation($dbi);
$cfgRelation = $relation->getRelationsParam();
$tbl_storage_engine = mb_strtoupper(
    $dbi->getTable($db, $table)->getStatusInfo('Engine')
);
$upd_query = new Table($table, $db, $dbi);

$dependency_definitions = [
    "options_array" => $options_array,
    "cfgRelation" => $cfgRelation,
    "tbl_storage_engine" => $tbl_storage_engine,
    "upd_query" => $upd_query
];
if ($cfgRelation['relwork']) {
    $dependency_definitions['existrel'] = $relation->getForeigners(
        $db,
        $table,
        '',
        'internal'
    );
}
if (Util::isForeignKeySupported($tbl_storage_engine)) {
    $dependency_definitions['existrel_foreign'] = $relation->getForeigners(
        $db,
        $table,
        '',
        'foreign'
    );
}

/** @var RelationController $controller */
$controller = $container->get(RelationController::class, $dependency_definitions);
$controller->indexAction();
