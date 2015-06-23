<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/controllers/table/TableIndexesController.class.php';

use PMA\Controllers\Table\TableIndexesController;

$controller = new TableIndexesController();
$controller->indexAction();
