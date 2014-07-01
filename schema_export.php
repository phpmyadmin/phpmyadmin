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
require_once 'libraries/schema/User_Schema.class.php';

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

$user_schema = new PMA_User_Schema();

/**
 * This function will process the user defined pages
 * and tables which will be exported as Relational schema
 * you can set the table positions on the paper via scratchboard
 * for table positions, put the x,y co-ordinates
 *
 * @param string $do It tells what the Schema is supposed to do
 *                  create and select a page, generate schema etc
 */
if (isset($_REQUEST['do'])) {
    $temp_page = PMA_createNewPage("_temp" . rand());
    try {
        PMA_saveTablePositions($temp_page);
        $_POST['pdf_page_number'] = $temp_page;
        $user_schema->setAction($_REQUEST['do']);
        $user_schema->processUserChoice();
        PMA_deletePage($temp_page);
    } catch (Exception $e) {
        PMA_deletePage($temp_page); // delete temp page even if an exception occured
        throw $e;
    }
}
?>
