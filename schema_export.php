<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Schema export handler
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
require_once 'libraries/pmd_common.php';
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
    'export_type',
    'orientation',
    'paper',
    'names',
    'show_color',
    'show_grid',
    'show_keys',
    'show_table_dimension',
    'with_doc'
);
foreach ($post_params as $one_post_param) {
    if (isset($_REQUEST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_REQUEST[$one_post_param];
        $_POST[$one_post_param] = $_REQUEST[$one_post_param];
    }
}

$temp_page = PMA_createNewPage("_temp" . rand(), $GLOBALS['db']);
try {
    PMA_saveTablePositions($temp_page);
    $_POST['pdf_page_number'] = $temp_page;
    PMA_processExportSchema();
    PMA_deletePage($temp_page);
} catch (Exception $e) {
    PMA_deletePage($temp_page); // delete temp page even if an exception occured
    throw $e;
}

/**
 * get all the export options and verify
 * call and include the appropriate Schema Class depending on $export_type
 *
 * @return void
 */
function PMA_processExportSchema()
{
    /**
     * default is PDF, otherwise validate it's only letters a-z
     */
    global  $db,$export_type;

    if (! isset($export_type) || ! preg_match('/^[a-zA-Z]+$/', $export_type)) {
        $export_type = 'pdf';
    }
    $GLOBALS['dbi']->selectDb($db);

    $path = PMA_securePath(ucfirst($export_type));
    $filename = 'libraries/schema/' . $path . '_Relation_Schema.class.php';
    if (!file_exists($filename)) {
        PMA_Export_Relation_Schema::dieSchema(
            $_POST['chpage'],
            $export_type,
            __('File doesn\'t exist')
        );
    }
    $GLOBALS['skip_import'] = false;
    include $filename;
    if ( $GLOBALS['skip_import']) {
        PMA_Export_Relation_Schema::dieSchema(
            $_POST['chpage'],
            $export_type,
            __('Plugin is disabled')
        );
    }
    $class_name = 'PMA_' . $path . '_Relation_Schema';
    $obj_schema = new $class_name();
    $obj_schema->showOutput();
}
?>
