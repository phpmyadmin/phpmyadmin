<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
if (!defined('PMA_GRAB_GLOBALS_INCLUDED')) {
    include('./libraries/grab_globals.lib.php3');
}
if (!defined('PMA_COMMON_LIB_INCLUDED')) {
    include('./libraries/common.lib.php3');
}
if (!defined('PMA_BOOKMARK_LIB_INCLUDED')) {
    include('./libraries/bookmark.lib.php3');
}


/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = $cfg['DefaultTabDatabase'] . '?' . PMA_generate_common_url($db);
$err_url   = $cfg['DefaultTabTable'] . '?' . PMA_generate_common_url($db, $table);


/**
 * Ensures the database and the table exist (else move to the "parent" script)
 */
require('./libraries/db_table_exists.lib.php3');


/**
 * Displays headers
 */
if (!isset($message)) {
    $js_to_run = 'functions.js';
    include('./header.inc.php3');
} else {
    PMA_showMessage($message);
    unset($message);
}


/**
 * Set parameters for links
 */
$url_query = PMA_generate_common_url($db, $table);

?>
