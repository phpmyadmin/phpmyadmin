<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require 'libraries/StorageEngine.class.php';

/**
 * get all variables needed for exporting relational schema
 * in $cfgRelation
 */
$cfgRelation = PMA_getRelationsParam();

require_once 'libraries/transformations.lib.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/schema/Export_Relation_Schema.class.php';

/**
 * get all the export options and verify
 * call and include the appropriate Schema Class depending on $export_type
 * default is PDF
 */

$post_params = array(
    'all_tables_same_width',
    'chpage',
    'db',
    'do',
    'export_type',
    'orientation',
    'paper',
    'names',
    'pdf_page_number',
    'show_color',
    'show_grid',
    'show_keys',
    'show_table_dimension',
    'with_doc'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

if (! isset($export_type) || ! preg_match('/^[a-zA-Z]+$/', $export_type)) {
    $export_type = 'pdf';
}
PMA_DBI_select_db($db);

$path = PMA_securePath(ucfirst($export_type));
if (!file_exists('libraries/schema/' . $path . '_Relation_Schema.class.php')) {
    PMA_Export_Relation_Schema::dieSchema(
        $_POST['chpage'],
        $export_type,
        __('File doesn\'t exist')
    );
}
require "libraries/schema/".$path.'_Relation_Schema.class.php';
$obj_schema = eval("new PMA_".$path."_Relation_Schema();");
