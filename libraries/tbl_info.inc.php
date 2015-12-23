<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * extracts table properties from create statement
 *
 * @todo should be handled by class Table
 * @todo this should be recoded as functions, to avoid messing with global variables
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// Check parameters
PMA_Util::checkParameters(array('db', 'table'));

/**
 * Defining global variables, in case this script is included by a function.
 */
global $showtable, $tbl_is_view, $tbl_storage_engine, $show_comment, $tbl_collation,
       $table_info_num_rows, $auto_increment;

/**
 * Gets table information
 */
// Seems we need to do this in MySQL 5.0.2,
// otherwise error #1046, no database selected
$GLOBALS['dbi']->selectDb($GLOBALS['db']);


/**
 * Holds information about the current table
 *
 * @todo replace this by PMA_Table
 * @global array $GLOBALS['showtable']
 * @name $showtable
 */
$GLOBALS['showtable'] = array();

// PMA_Table::getStatusInfo() does caching by default, but here
// we force reading of the current table status
// if $reread_info is true (for example, coming from tbl_operations.php
// and we just changed the table's storage engine)
$GLOBALS['showtable'] = $GLOBALS['dbi']->getTable(
    $GLOBALS['db'],
    $GLOBALS['table']
)->getStatusInfo(
    null,
    (isset($reread_info) && $reread_info ? true : false)
);

// need this test because when we are creating a table, we get 0 rows
// from the SHOW TABLE query
// and we don't want to mess up the $tbl_storage_engine coming from the form

if ($showtable) {
    /** @var PMA_String $pmaString */
    $pmaString = $GLOBALS['PMA_String'];

    if ($GLOBALS['dbi']->getTable($GLOBALS['db'], $GLOBALS['table'])->isView()) {
        $tbl_is_view     = true;
        $tbl_storage_engine = __('View');
        $show_comment    = null;
    } else {
        $tbl_is_view     = false;
        $tbl_storage_engine = isset($showtable['Engine'])
            ? /*overload*/mb_strtoupper($showtable['Engine'])
            : '';
        $show_comment = '';
        if (isset($showtable['Comment'])) {
            $show_comment = $showtable['Comment'];
        }
    }
    $tbl_collation       = empty($showtable['Collation'])
        ? ''
        : $showtable['Collation'];

    if (null === $showtable['Rows']) {
        $showtable['Rows']   = $GLOBALS['dbi']
            ->getTable($GLOBALS['db'], $showtable['Name'])
            ->countRecords(true);
    }
    $table_info_num_rows = isset($showtable['Rows']) ? $showtable['Rows'] : 0;
    $row_format = isset($showtable['Row_format']) ? $showtable['Row_format'] : '';
    $auto_increment      = isset($showtable['Auto_increment'])
        ? $showtable['Auto_increment']
        : '';

    $create_options      = isset($showtable['Create_options'])
        ? explode(' ', $showtable['Create_options'])
        : array();

    // export create options by its name as variables into global namespace
    // f.e. pack_keys=1 becomes available as $pack_keys with value of '1'
    unset($pack_keys);
    foreach ($create_options as $each_create_option) {
        $each_create_option = explode('=', $each_create_option);
        if (isset($each_create_option[1])) {
            // ensure there is no ambiguity for PHP 5 and 7
            ${$each_create_option[0]} = $each_create_option[1];
        }
    }
    // we need explicit DEFAULT value here (different from '0')
    $pack_keys = (! isset($pack_keys) || /*overload*/mb_strlen($pack_keys) == 0)
        ? 'DEFAULT'
        : $pack_keys;
    unset($create_options, $each_create_option);
} else {
    $pack_keys = $row_format = null;
}// end if
