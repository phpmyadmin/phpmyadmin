<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/bookmark.lib.php');

// Check parameters
PMA_checkParameters(array('db','table'));

/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = $cfg['DefaultTabDatabase'] . '?' . PMA_generate_common_url($db);
$err_url   = $cfg['DefaultTabTable'] . '?' . PMA_generate_common_url($db, $table);


/**
 * Ensures the database and the table exist (else move to the "parent" script)
 */
require_once('./libraries/db_table_exists.lib.php');


/**
 * Displays headers
 */
if (!isset($message)) {
    $js_to_run = 'functions.js';
    require_once('./header.inc.php');
} else {
    PMA_showMessage($message);
    unset($message);
}


/**
 * Set parameters for links
 */
$url_query = PMA_generate_common_url($db, $table);

?>
