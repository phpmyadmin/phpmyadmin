<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Check parameters
 */
require_once './libraries/common.inc.php';

PMA_checkParameters(array('db', 'table'));

/**
 * Prepares links
 */
require_once './libraries/bookmark.lib.php';


/**
 * Set parameters for links
 */
if (empty($url_query)
|| (isset($_POST['table']) && isset($_POST['new_name']) && $_POST['table'] != $_POST['new_name'])) {
    $url_query = PMA_generate_common_url($db, $table);
}
$url_params['db']    = $db;
$url_params['table'] = $table;

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = $cfg['DefaultTabDatabase'] . PMA_generate_common_url(array('db' => $db,));
$err_url   = $cfg['DefaultTabTable'] . PMA_generate_common_url($url_params);

/**
 * Displays headers
 */
$js_to_run = 'functions.js';
require_once './libraries/header.inc.php';

/**
 * Displays links
 */
$tabs = array();

$tabs['browse']['icon'] = 'b_browse.png';
$tabs['browse']['text'] = $strBrowse;
$tabs['browse']['link'] = 'sql.php';
$tabs['browse']['args']['pos'] = 0;

$tabs['structure']['icon'] = 'b_props.png';
$tabs['structure']['link'] = 'tbl_structure.php';
$tabs['structure']['text'] = $strStructure;

$tabs['sql']['icon'] = 'b_sql.png';
$tabs['sql']['link'] = 'tbl_sql.php';
$tabs['sql']['text'] = $strSQL;

$tabs['search']['icon'] = 'b_search.png';
$tabs['search']['text'] = $strSearch;
$tabs['search']['link'] = 'tbl_select.php';

if (! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $tabs['insert']['icon'] = 'b_insrow.png';
    $tabs['insert']['link'] = 'tbl_change.php';
    $tabs['insert']['text'] = $strInsert;
}

$tabs['export']['icon'] = 'b_tblexport.png';
$tabs['export']['link'] = 'tbl_export.php';
$tabs['export']['args']['single_table'] = 'true';
$tabs['export']['text'] = $strExport;

/**
 * Don't display "Import", "Operations" and "Empty"
 * for views and information_schema
 */
if (! $tbl_is_view && ! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $tabs['import']['icon'] = 'b_tblimport.png';
    $tabs['import']['link'] = 'tbl_import.php';
    $tabs['import']['text'] = $strImport;

    $tabs['operation']['icon'] = 'b_tblops.png';
    $tabs['operation']['link'] = 'tbl_operations.php';
    $tabs['operation']['text'] = $strOperations;

    $ln8_stt = (PMA_MYSQL_INT_VERSION >= 40000)
             ? 'TRUNCATE TABLE '
             : 'DELETE FROM ';
    $tabs['empty']['link']  = 'sql.php';
    $tabs['empty']['args']['reload']    = 1;
    $tabs['empty']['args']['sql_query'] = $ln8_stt . PMA_backquote($table);
    $tabs['empty']['args']['zero_rows'] = sprintf($strTableHasBeenEmptied, htmlspecialchars($table));
    $tabs['empty']['attr']  = 'onclick="return confirmLink(this, \'' . $ln8_stt . PMA_jsFormat($table) . '\')"';
    $tabs['empty']['args']['goto'] = 'tbl_structure.php';
    $tabs['empty']['class'] = 'caution';
    $tabs['empty']['icon'] = 'b_empty.png';
    $tabs['empty']['text'] = $strEmpty;
    if ($table_info_num_rows == 0) {
        $tabs['empty']['warning'] = $strTableIsEmpty;
    }
}

/**
 * no drop in information_schema
 */
if (! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $tabs['drop']['icon'] = 'b_deltbl.png';
    $tabs['drop']['link'] = 'sql.php';
    $tabs['drop']['text'] = $strDrop;
    $tabs['drop']['args']['reload']     = 1;
    $tabs['drop']['args']['purge']      = 1;
    $drop_command = 'DROP ' . ($tbl_is_view ? 'VIEW' : 'TABLE');
    $tabs['drop']['args']['sql_query']  = $drop_command . ' ' . PMA_backquote($table);
    $tabs['drop']['args']['goto']       = 'db_structure.php';
    $tabs['drop']['args']['zero_rows']  = sprintf(($tbl_is_view ? $strViewHasBeenDropped : $strTableHasBeenDropped), htmlspecialchars($table));
    $tabs['drop']['attr'] = 'onclick="return confirmLink(this, \'' . $drop_command . ' ' . PMA_jsFormat($table) . '\')"';
    unset($drop_command);
    $tabs['drop']['class'] = 'caution';
}

if ($table_info_num_rows == 0 && !$tbl_is_view) {
    $tabs['browse']['warning'] = $strTableIsEmpty;
    $tabs['search']['warning'] = $strTableIsEmpty;
}

echo PMA_getTabs($tabs);
unset($tabs);

/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

?><br />
