<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// this should be recoded as functions, to avoid messing with global
// variables

// Check parameters

require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db', 'table'));

/**
 * Defining global variables, in case this script is included by a function.
 * This is necessary because this script can be included by header.inc.php.
 */
global $showtable, $tbl_is_view, $tbl_type, $show_comment, $tbl_collation,
       $table_info_num_rows, $auto_increment;

/**
 * Gets table informations
 */

// Seems we need to do this in MySQL 5.0.2,
// otherwise error #1046, no database selected
PMA_DBI_select_db($db);

// The 'show table' statement works correct since 3.23.03
$table_info_result   = PMA_DBI_query('SHOW TABLE STATUS LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\';');
$showtable           = PMA_DBI_fetch_assoc($table_info_result);
if (!isset($showtable['Type']) && isset($showtable['Engine'])) {
    $showtable['Type'] =& $showtable['Engine'];
}
if (PMA_MYSQL_INT_VERSION >= 50000 && !isset($showtable['Type']) && isset($showtable['Comment']) && $showtable['Comment'] == 'view') {
    $tbl_is_view     = TRUE;
    $tbl_type        = $strView;
    $show_comment    = NULL;
} else {
    $tbl_is_view     = FALSE;
    $tbl_type        = isset($showtable['Type']) ? strtoupper($showtable['Type']) : '';
    $show_comment    = (isset($showtable['Comment']) ? $showtable['Comment'] : '');
}
$tbl_collation       = empty($showtable['Collation']) ? '' : $showtable['Collation'];
$table_info_num_rows = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
$auto_increment      = (isset($showtable['Auto_increment']) ? $showtable['Auto_increment'] : '');

$tmp                 = isset($showtable['Create_options']) ? explode(' ', $showtable['Create_options']) : array();
$tmp_cnt             = count($tmp);
for ($i = 0; $i < $tmp_cnt; $i++) {
    $tmp1            = explode('=', $tmp[$i]);
    if (isset($tmp1[1])) {
        $$tmp1[0]    = $tmp1[1];
    }
} // end for
PMA_DBI_free_result($table_info_result);
unset($tmp1, $tmp, $table_info_result);
?>
