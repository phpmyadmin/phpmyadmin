<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
if (!defined('PMA_GRAB_GLOBALS_INCLUDED')) {
    include('./libraries/grab_globals.lib.php');
}
if (!defined('PMA_COMMON_LIB_INCLUDED')) {
    include('./libraries/common.lib.php');
}
if (PMA_MYSQL_INT_VERSION >= 40100 && !defined('PMA_MYSQL_CHARSETS_LIB_INCLUDED')) {
    include('./libraries/mysql_charsets.lib.php');
}

require('./header.inc.php');

/**
 * Drop multiple rows if required
 */

// workaround for IE problem:
if (isset($submit_mult_x)) {
    $submit_mult = 'row_delete';
}

if ((!empty($submit_mult) && isset($rows_to_delete) && is_array($rows_to_delete))
    || isset($mult_btn)) {
    $action = 'tbl_row_delete.php';
    $err_url = 'tbl_row_delete.php?' . PMA_generate_common_url($db, $table);
    if (!isset($mult_btn)) {
        $original_sql_query = $sql_query;
        $original_url_query = $url_query;
        $original_pos       = $pos;
    }
    include('./mult_submits.inc.php');
}
$url_query = PMA_generate_common_url($db, $table)
           . '&amp;goto=tbl_properties.php';


/**
 * Show result of multi submit operation
 */
if ((!empty($submit_mult) && isset($rows_to_delete))
    || isset($mult_btn)) {
    PMA_showMessage($strSuccess);
}

if (isset($original_sql_query)) {
    $sql_query = $original_sql_query;
}

if (isset($original_url_query)) {
    $url_query = $original_url_query;
}

if (isset($original_pos)) {
    $pos       = $original_pos;
}

include('./sql.php');

/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php');
?>