<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/* $Id$ */

/**
 * Gets some core libraries
 */
require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');

PMA_checkParameters(array('db'));

$is_show_stats = $cfg['ShowStats'];

if ( PMA_MYSQL_INT_VERSION >= 50002 && $db == 'information_schema' ) {
    $is_show_stats = false;
    $db_is_information_schema = true;
} else {
    $db_is_information_schema = false;
}

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = 'main.php?' . PMA_generate_common_url();
$err_url   = $cfg['DefaultTabDatabase'] . '?' . PMA_generate_common_url($db);


/**
 * Ensures the database exists (else move to the "parent" script) and displays
 * headers
 */
if (!isset($is_db) || !$is_db) {
    // Not a valid db name -> back to the welcome page
    if (isset($db) && strlen($db)) {
        $is_db = PMA_DBI_select_db($db);
    }
    if (!isset($db) || !strlen($db) || !$is_db) {
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'main.php?' . PMA_generate_common_url('', '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit;
    }
} // end if (ensures db exists)

/**
 * Changes database charset if requested by the user
 */
if (isset($submitcollation) && !empty($db_collation) && PMA_MYSQL_INT_VERSION >= 40101) {
    list($db_charset) = explode('_', $db_collation);
    $sql_query        = 'ALTER DATABASE ' . PMA_backquote($db) . ' DEFAULT' . PMA_generateCharsetQueryPart($db_collation);
    $result           = PMA_DBI_query($sql_query);
    $message          = $strSuccess;
    unset($db_charset, $db_collation);
}

$js_to_run = 'functions.js';
require_once('./libraries/header.inc.php');

/**
 * Set parameters for links
 */
$url_query = PMA_generate_common_url($db);

?>
