<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table search tab
 *
 * display table search form, create SQL query from form data
 * and include sql.php to execute it
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.lib.php';
require_once 'libraries/tbl_select.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('sql.js');
$scripts->addFile('tbl_select.js');
$scripts->addFile('tbl_change.js');
$scripts->addFile('jquery/timepicker.js');
$scripts->addFile('gis_data_editor.js');

$post_params = array(
    'ajax_request',
    'session_max_rows'
);
foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}


/**
 * Not selection yet required -> displays the selection form
 */
if (! isset($_POST['columnsToDisplay']) || $_POST['columnsToDisplay'][0] == '') {
    // Gets some core libraries
    include_once 'libraries/tbl_common.inc.php';
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

    /**
     * Gets table's information
     */
    include_once 'libraries/tbl_info.inc.php';

    if (! isset($goto)) {
        $goto = $GLOBALS['cfg']['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields
    list($columnNames, $columnTypes, $columnCollations, $columnNullFlags, $geomColumnFlag)
        = PMA_tbl_getFields($db, $table);

    // retrieve keys into foreign fields, if any
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    $foreigners = PMA_getForeigners($db, $table);

    // Displays the table search form
    echo PMA_tblSearchGetSelectionForm(
        $goto, $db, $table, $columnNames, $columnTypes, $columnCollations,
        $columnNullFlags, $geomColumnFlag, $foreigners, "normal"
    );
} else {
    /**
     * Selection criteria have been submitted -> do the work
     */
    $sql_query = PMA_tblSearchBuildSqlQuery();
    include 'sql.php';
}
?>
