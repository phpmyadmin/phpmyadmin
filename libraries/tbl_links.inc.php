<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
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
$url_params = array();
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
$GLOBALS['js_include'][] = 'functions.js';
require_once './libraries/header.inc.php';

/**
 * Displays links
 */
$tabs = array();

$tabs['browse']['icon'] = 'b_browse.png';
$tabs['browse']['text'] = __('Browse');
$tabs['browse']['link'] = 'sql.php';
$tabs['browse']['args']['pos'] = 0;

$tabs['structure']['icon'] = 'b_props.png';
$tabs['structure']['link'] = 'tbl_structure.php';
$tabs['structure']['text'] = __('Structure');

$tabs['sql']['icon'] = 'b_sql.png';
$tabs['sql']['link'] = 'tbl_sql.php';
$tabs['sql']['text'] = __('SQL');

$tabs['search']['icon'] = 'b_search.png';
$tabs['search']['text'] = __('Search');
$tabs['search']['link'] = 'tbl_select.php';

if(PMA_Tracker::isActive())
{
    $tabs['tracking']['icon'] = 'eye.png';
    $tabs['tracking']['text'] = __('Tracking');
    $tabs['tracking']['link'] = 'tbl_tracking.php';
}

if (! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $tabs['insert']['icon'] = 'b_insrow.png';
    $tabs['insert']['link'] = 'tbl_change.php';
    $tabs['insert']['text'] = __('Insert');
}

$tabs['export']['icon'] = 'b_tblexport.png';
$tabs['export']['link'] = 'tbl_export.php';
$tabs['export']['args']['single_table'] = 'true';
$tabs['export']['text'] = __('Export');

/**
 * Don't display "Import", "Operations" and "Empty"
 * for views and information_schema
 */
if (! $tbl_is_view && ! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $tabs['import']['icon'] = 'b_tblimport.png';
    $tabs['import']['link'] = 'tbl_import.php';
    $tabs['import']['text'] = __('Import');

    $tabs['operation']['icon'] = 'b_tblops.png';
    $tabs['operation']['link'] = 'tbl_operations.php';
    $tabs['operation']['text'] = __('Operations');

    $tabs['empty']['link']  = 'sql.php';
    $tabs['empty']['args']['reload']    = 1;
    $tabs['empty']['args']['sql_query'] = 'TRUNCATE TABLE ' . PMA_backquote($table);
    $tabs['empty']['args']['zero_rows'] = sprintf(__('Table %s has been emptied'), htmlspecialchars($table));
    $tabs['empty']['attr']  = 'onclick="return confirmLink(this, \'TRUNCATE TABLE ' . PMA_jsFormat($table) . '\')"';
    $tabs['empty']['args']['goto'] = 'tbl_structure.php';
    $tabs['empty']['class'] = 'caution';
    $tabs['empty']['icon'] = 'b_empty.png';
    $tabs['empty']['text'] = __('Empty');
    if ($table_info_num_rows == 0) {
        $tabs['empty']['warning'] = __('Table seems to be empty!');
    }
}

/**
 * Views support a limited number of operations 
 */
if ($tbl_is_view && ! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $tabs['operation']['icon'] = 'b_tblops.png';
    $tabs['operation']['link'] = 'view_operations.php';
    $tabs['operation']['text'] = __('Operations');
}

/**
 * no drop in information_schema
 */
if (! (isset($db_is_information_schema) && $db_is_information_schema)) {
    $tabs['drop']['icon'] = 'b_deltbl.png';
    $tabs['drop']['link'] = 'sql.php';
    $tabs['drop']['url_params'] = array('table' => NULL);
    $tabs['drop']['text'] = __('Drop');
    $tabs['drop']['args']['reload']     = 1;
    $tabs['drop']['args']['purge']      = 1;
    $drop_command = 'DROP ' . ($tbl_is_view ? 'VIEW' : 'TABLE');
    $tabs['drop']['args']['sql_query']  = $drop_command . ' ' . PMA_backquote($table);
    $tabs['drop']['args']['goto']       = 'db_structure.php';
    $tabs['drop']['args']['zero_rows']  = sprintf(($tbl_is_view ? __('View %s has been dropped') : __('Table %s has been dropped')), htmlspecialchars($table));
    $tabs['drop']['attr'] = 'onclick="return confirmLink(this, \'' . $drop_command . ' ' . PMA_jsFormat($table) . '\')"';
    unset($drop_command);
    $tabs['drop']['class'] = 'caution';
}

if ($table_info_num_rows == 0 && !$tbl_is_view) {
    $tabs['browse']['warning'] = __('Table seems to be empty!');
    $tabs['search']['warning'] = __('Table seems to be empty!');
}

echo PMA_generate_html_tabs($tabs, $url_params);
unset($tabs);

if(PMA_Tracker::isActive() and PMA_Tracker::isTracked($GLOBALS["db"], $GLOBALS["table"]))
{
    $msg = PMA_Message::notice('<a href="tbl_tracking.php?'.$url_query.'">'.sprintf(__('Tracking of %s.%s is activated.'), $GLOBALS["db"], $GLOBALS["table"]).'</a>');
    $msg->display();
}

/**
 * Displays a message
 */
if (!empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

?>
