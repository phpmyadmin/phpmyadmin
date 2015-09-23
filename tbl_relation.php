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

/**
 * Get the TableRelationController
 */
namespace PMA;

use PMA_Response;
use PMA_Table;
use PMA_Util;

require_once 'libraries/common.inc.php';
require_once 'libraries/di/Container.class.php';
require_once 'libraries/controllers/TableRelationController.class.php';
require_once 'libraries/Response.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/Util.class.php';

$container = DI\Container::getDefaultContainer();
$container->factory('PMA\Controllers\Table\TableRelationController');
$container->alias(
    'TableRelationController', 'PMA\Controllers\Table\TableRelationController'
);
$container->set('PMA_Response', PMA_Response::getInstance());
$container->alias('response', 'PMA_Response');

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
$cfgRelation = PMA_getRelationsParam();
$tbl_storage_engine = /*overload*/
    mb_strtoupper(
        $GLOBALS['dbi']->getTable($db, $table)->getStatusInfo('Engine')
    );
$upd_query = new PMA_Table($table, $db, $dbi);

$dependency_definitions = array(
    "options_array" => $options_array,
    "cfgRelation" => $cfgRelation,
    "tbl_storage_engine" => $tbl_storage_engine,
    "upd_query" => $upd_query
);
if ($cfgRelation['relwork']) {
    $dependency_definitions['existrel'] = PMA_getForeigners(
        $db, $table, '', 'internal'
    );
}
if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
    $dependency_definitions['existrel_foreign'] = PMA_getForeigners(
        $db, $table, '', 'foreign'
    );
}
if ($cfgRelation['displaywork']) {
    $dependency_definitions['disp'] = PMA_getDisplayField($db, $table);
} else {
    $dependency_definitions['disp'] = 'asas';
}

/** @var Controllers\Table\TableRelationController $controller */
$controller = $container->get('TableRelationController', $dependency_definitions);
$controller->indexAction();
