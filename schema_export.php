<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Schema export handler
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Export;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Util;

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

/**
 * get all variables needed for exporting relational schema
 * in $cfgRelation
 */
$relation = new Relation();
$cfgRelation = $relation->getRelationsParam();

if (! isset($_REQUEST['export_type'])) {
    Util::checkParameters(array('export_type'));
}

/**
 * Include the appropriate Schema Class depending on $export_type
 * default is PDF
 */
Export::processExportSchema($_REQUEST['export_type']);
