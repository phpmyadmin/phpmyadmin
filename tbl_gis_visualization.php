<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the GIS visualizations.
 *
 * @package PhpMyAdmin
 */

use PMA\Controllers\Table\TableGisVisualizationController;

require_once 'libraries/controllers/table/TableGisVisualizationController.class.php';

$controller = new TableGisVisualizationController();
$controller->indexAction();
