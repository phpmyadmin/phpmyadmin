<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/tbl_indexes.lib.php';

if (! isset($_REQUEST['create_edit_table'])) {
    include_once 'libraries/tbl_common.inc.php';
}


$index = PMA_prepareFormValues($db, $table);
/**
 * Process the data from the edit/create index form,
 * run the query to build the new index
 * and moves back to "tbl_sql.php"
 */
if (isset($_REQUEST['do_save_data'])) {
    PMA_handleCreateOrEditIndex($db, $table, $index);
} // end builds the new index


/**
 * Display the form to edit/create an index
 */
require_once 'libraries/tbl_info.inc.php';

$add_fields = PMA_getNumberOfFieldsForForm($index);

$form_params = PMA_getFormParameters($db, $table);

// Get fields and stores their name/type
if (isset($_REQUEST['create_edit_table'])) {
    $fields = json_decode($_REQUEST['columns'], true);
    $index_params = array(
        'Non_unique' => ($_REQUEST['index']['Index_type'] == 'UNIQUE') ? '0' : '1'
    );
    $index->set($index_params);
    $add_fields = count($fields);
} else {
    $fields = PMA_getNameAndTypeOfTheColumns($db, $table);
}

$html = PMA_getHtmlForIndexForm($fields, $index, $form_params, $add_fields);

$response = PMA_Response::getInstance();
$response->addHTML($html);
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('indexes.js');
?>
