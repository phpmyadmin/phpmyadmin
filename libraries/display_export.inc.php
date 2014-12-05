<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays export tab.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// Get relations & co. status
$cfgRelation = PMA_getRelationsParam();

if (isset($_REQUEST['single_table'])) {
    $GLOBALS['single_table'] = $_REQUEST['single_table'];
}

require_once './libraries/file_listing.lib.php';
require_once './libraries/plugin_interface.lib.php';
require_once './libraries/display_export.lib.php';

/* Scan for plugins */
$export_list = PMA_getPlugins(
    "export",
    'libraries/plugins/export/',
    array(
        'export_type' => $export_type,
        'single_table' => isset($single_table)
    )
);

/* Fail if we didn't find any plugin */
if (empty($export_list)) {
    PMA_Message::error(
        __('Could not load export plugins, please check your installation!')
    )->display();
    exit;
}

$html = '<form method="post" action="export.php" '
    . ' name="dump" class="disableAjax">';

//output Hidden Inputs
$single_table_str = isset($single_table)? $single_table : '';
$sql_query_str = isset($sql_query)? $sql_query : '';
$html .= PMA_getHtmlForHiddenInput(
    $export_type,
    $db,
    $table,
    $single_table_str,
    $sql_query_str
);

//output Export Options
$num_tables_str = isset($num_tables)? $num_tables : '';
$unlim_num_rows_str = isset($unlim_num_rows)? $unlim_num_rows : '';
$multi_values_str = isset($multi_values)? $multi_values : '';
$html .= PMA_getHtmlForExportOptions(
    $export_type,
    $db,
    $table,
    $multi_values_str,
    $num_tables_str,
    $export_list,
    $unlim_num_rows_str
);

$html .= '</form>';

$response = PMA_Response::getInstance();
$response->addHTML($html);
