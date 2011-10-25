<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require './libraries/StorageEngine.class.php';

/**
 * Include settings for relation stuff
 * get all variables needed for exporting relational schema
 * in $cfgRelation
 */
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

require_once './libraries/transformations.lib.php';
require_once './libraries/Index.class.php';
require_once "./libraries/schema/Export_Relation_Schema.class.php";

/**
 * get all the export options and verify
 * call and include the appropriate Schema Class depending on $export_type
 * default is PDF
 */
global  $db, $export_type;
if (!isset($export_type) || !preg_match('/^[a-zA-Z]+$/', $export_type)) {
    $export_type = 'pdf';
}
PMA_DBI_select_db($db);

$path = PMA_securePath(ucfirst($export_type));
if (!file_exists('./libraries/schema/' . $path . '_Relation_Schema.class.php')) {
    PMA_Export_Relation_Schema::dieSchema($_POST['chpage'], $export_type, __('File doesn\'t exist'));
}
require "./libraries/schema/".$path."_Relation_Schema.class.php";
$obj_schema = eval("new PMA_".$path."_Relation_Schema();");
