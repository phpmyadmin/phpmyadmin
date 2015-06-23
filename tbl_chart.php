<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/controllers/table/TableChartController.class.php';

use PMA\Controllers\Table\TableChartController;

$controller = new TableChartController();
$controller->indexAction();
