<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


// Check parameters

require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db', 'table'));

/**
 * Prepares links
 */
require_once('./libraries/bookmark.lib.php');
$book_sql_query = PMA_queryBookmarks($db, $GLOBALS['cfg']['Bookmark'], '\'' . PMA_sqlAddslashes($table) . '\'', 'label');

/**
 * Displays links
 */
$tabs = array();

$tabs['browse']['icon'] = 'b_browse.png';
$tabs['browse']['text'] = $strBrowse;

$tabs['structure']['icon'] = 'b_props.png';
$tabs['structure']['link'] = 'tbl_properties_structure.php';
$tabs['structure']['text'] = $strStructure;

$tabs['sql']['icon'] = 'b_sql.png';
$tabs['sql']['link'] = 'tbl_properties.php';
$tabs['sql']['text'] = $strSQL;

$tabs['search']['icon'] = 'b_search.png';
$tabs['search']['text'] = $strSearch;

$tabs['insert']['icon'] = 'b_insrow.png';
$tabs['insert']['link'] = 'tbl_change.php';
$tabs['insert']['text'] = $strInsert;

/**
 * Don't display "Export", "Import", "Operations" and "Empty" for views.
 */
if (!$tbl_is_view) {
    $tabs['export']['icon'] = 'b_tblexport.png';
    $tabs['export']['link'] = 'tbl_properties_export.php';
    $tabs['export']['args']['single_table'] = 'true';
    $tabs['export']['text'] = $strExport;
    
    $tabs['import']['icon'] = 'b_tblimport.png';
    $tabs['import']['link'] = 'tbl_import.php';
    $tabs['import']['text'] = $strImport;
    
    $tabs['operation']['icon'] = 'b_tblops.png';
    $tabs['operation']['link'] = 'tbl_properties_operations.php';
    $tabs['operation']['text'] = $strOperations;
    
    if ($table_info_num_rows > 0) {
        $ln8_stt = (PMA_MYSQL_INT_VERSION >= 40000)
                 ? 'TRUNCATE TABLE '
                 : 'DELETE FROM ';
        $tabs['empty']['link']  = 'sql.php';
        $tabs['empty']['args']['sql_query'] = $ln8_stt . PMA_backquote($table);
        $tabs['empty']['args']['zero_rows'] = sprintf($strTableHasBeenEmptied, htmlspecialchars($table));
        $tabs['empty']['attr']  = 'onclick="return confirmLink(this, \'' . $ln8_stt . PMA_jsFormat($table) . '\')"';
        $tabs['empty']['class'] = 'caution';
    }
    $tabs['empty']['icon'] = 'b_empty.png';
    $tabs['empty']['text'] = $strEmpty;
}
$tabs['drop']['icon'] = 'b_deltbl.png';
$tabs['drop']['link'] = 'sql.php';
$tabs['drop']['text'] = $strDrop;
$tabs['drop']['args']['reload']     = 1;
$tabs['drop']['args']['purge']      = 1;
$tabs['drop']['args']['sql_query']  = 'DROP ' . ($tbl_is_view ? 'VIEW' : 'TABLE') . ' ' . PMA_backquote($table);
$tabs['drop']['args']['zero_rows']  = sprintf($strTableHasBeenDropped, htmlspecialchars($table));
$tabs['drop']['attr'] = 'onclick="return confirmLink(this, \'DROP TABLE ' . PMA_jsFormat($table) . '\')"';
$tabs['drop']['class'] = 'caution';

if ($table_info_num_rows > 0 || $tbl_is_view) {
    $tabs['browse']['link'] = 'sql.php';
    $tabs['browse']['args']['sql_query'] = isset($book_sql_query) && $book_sql_query != FALSE ? $book_sql_query : 'SELECT * FROM ' . PMA_backquote($table);
    $tabs['browse']['args']['pos'] = 0;
    $tabs['search']['link'] = 'tbl_select.php';
}

echo PMA_getTabs( $tabs );
unset( $tabs );

/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

?><br />
