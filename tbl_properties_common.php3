<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./libraries/bookmark.lib.php3');


/**
 * Defines the urls to return to in case of error in a sql statement
 */
$err_url_0 = $cfg['DefaultTabDatabase']
           . '?lang=' . $lang
           . '&amp;convcharset=' . $convcharset
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db);
$err_url   = $cfg['DefaultTabTable']
           . '?lang=' . $lang
           . '&amp;convcharset=' . $convcharset
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db)
           . '&amp;table=' . urlencode($table);


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
}


/**
 * Set parameters for links
 */
$url_query = 'lang=' . $lang
           . '&amp;convcharset=' . $convcharset
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db)
           . '&amp;table=' . urlencode($table)
           . '&amp;goto=tbl_properties.php3';

?>
