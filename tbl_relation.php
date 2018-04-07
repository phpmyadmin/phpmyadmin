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

use PhpMyAdmin\Controllers\Table\TableRelationController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Util;

require_once 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Table\TableRelationController');
$container->alias(
    'TableRelationController',
    'PhpMyAdmin\Controllers\Table\TableRelationController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$db = $container->get('db');
$table = $container->get('table');
$dbi = $container->get('dbi');
$options_array = array(
    'CASCADE' => 'CASCADE',
    'SET_NULL' => 'SET NULL',
    'NO_ACTION' => 'NO ACTION',
    'RESTRICT' => 'RESTRICT',
);
$relation = new Relation();
$cfgRelation = $relation->getRelationsParam();
$tbl_storage_engine = mb_strtoupper(
    $dbi->getTable($db, $table)->getStatusInfo('Engine')
);
$upd_query = new Table($table, $db, $dbi);

$dependency_definitions = array(
    "options_array" => $options_array,
    "cfgRelation" => $cfgRelation,
    "tbl_storage_engine" => $tbl_storage_engine,
    "upd_query" => $upd_query
);
if ($cfgRelation['relwork']) {
    $dependency_definitions['existrel'] = $relation->getForeigners(
        $db, $table, '', 'internal'
    );
}
if (Util::isForeignKeySupported($tbl_storage_engine)) {
    $dependency_definitions['existrel_foreign'] = $relation->getForeigners(
        $db, $table, '', 'foreign'
    );
}

/** @var TableRelationController $controller */
$controller = $container->get('TableRelationController', $dependency_definitions);
$controller->indexAction();
