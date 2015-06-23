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
require_once 'libraries/controllers/table/TableRelationController.class.php';

use PMA\Controllers\Table\TableRelationController;

$tblRelationCtrl = new TableRelationController();
$tblRelationCtrl->indexAction();
