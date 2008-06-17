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
 *
 */
require_once './libraries/Table.class.php';

/**
 * extracts table properties from create statement
 *
 * @todo this should be recoded as functions,
 * to avoid messing with global variables
 */

/**
 * requirements
 */
require_once './libraries/common.inc.php';

// Check parameters
PMA_checkParameters(array('db', 'table'));

/**
 * Defining global variables, in case this script is included by a function.
 * This is necessary because this script can be included by libraries/header.inc.php.
 */
global $showtable, $tbl_is_view, $tbl_type, $show_comment, $tbl_collation,
       $table_info_num_rows, $auto_increment;

/**
 * Gets table informations
 */
// Seems we need to do this in MySQL 5.0.2,
// otherwise error #1046, no database selected
PMA_DBI_select_db($GLOBALS['db']);

// The 'show table' statement works correct since 3.23.03
$table_info_result   = PMA_DBI_query(
    'SHOW TABLE STATUS LIKE \'' . PMA_sqlAddslashes($GLOBALS['table'], true) . '\';',
    null, PMA_DBI_QUERY_STORE);

// need this test because when we are creating a table, we get 0 rows
// from the SHOW TABLE query
// and we don't want to mess up the $tbl_type coming from the form

if ($table_info_result && PMA_DBI_num_rows($table_info_result) > 0) {
    $showtable           = PMA_DBI_fetch_assoc($table_info_result);
    PMA_DBI_free_result($table_info_result);
    unset($table_info_result);

    if (!isset($showtable['Type']) && isset($showtable['Engine'])) {
        $showtable['Type'] =& $showtable['Engine'];
    }
    if (PMA_Table::isView($GLOBALS['db'], $GLOBALS['table'])) {
        $tbl_is_view     = true;
        $tbl_type        = $GLOBALS['strView'];
        $show_comment    = null;
    } else {
        $tbl_is_view     = false;
        $tbl_type        = isset($showtable['Type'])
            ? strtoupper($showtable['Type'])
            : '';
        // a new comment could be coming from tbl_operations.php
        // and we want to show it in the header
        if (isset($submitcomment) && isset($comment)) {
            $show_comment = $comment;
        } else {
            $show_comment    = isset($showtable['Comment'])
                ? $showtable['Comment']
                : '';
        }
    }
    $tbl_collation       = empty($showtable['Collation'])
        ? ''
        : $showtable['Collation'];

    if (null === $showtable['Rows']) {
        $showtable['Rows']   = PMA_Table::countRecords($GLOBALS['db'],
            $showtable['Name'], true, true);
    }
    $table_info_num_rows = isset($showtable['Rows']) ? $showtable['Rows'] : 0;
    $auto_increment      = isset($showtable['Auto_increment'])
        ? $showtable['Auto_increment']
        : '';

    $create_options      = isset($showtable['Create_options'])
        ? explode(' ', $showtable['Create_options'])
        : array();

    // export create options by its name as variables into gloabel namespace
    // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
    unset($pack_keys);
    foreach ($create_options as $each_create_option) {
        $each_create_option = explode('=', $each_create_option);
        if (isset($each_create_option[1])) {
            $$each_create_option[0]    = $each_create_option[1];
        }
    }
    // we need explicit DEFAULT value here (different from '0')
    $pack_keys = (!isset($pack_keys) || strlen($pack_keys) == 0) ? 'DEFAULT' : $pack_keys;
    unset($create_options, $each_create_option);
} // end if
?>
