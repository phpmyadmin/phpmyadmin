<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
$js_to_run = 'functions.js';
require('./libraries/common.lib.php3');


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php3?' . PMA_generate_common_url($db, $table);


/**
 * A new name has been submitted -> do the work
 */
if (isset($new_name) && trim($new_name) != '') {
    $old_name     = $table;
    $table        = $new_name;
    if (get_magic_quotes_gpc()) {
        $new_name = stripslashes($new_name);
    }

    // Ensure the target is valid
    if (count($dblist) > 0 && PMA_isInto($db, $dblist) == -1) {
        exit();
    }
    if (PMA_MYSQL_INT_VERSION < 32306) {
        PMA_checkReservedWords($new_name, $err_url);
    }

    include('./header.inc.php3');
    PMA_mysql_select_db($db);
    $sql_query = 'ALTER TABLE ' . PMA_backquote($old_name) . ' RENAME ' . PMA_backquote($new_name);
    $result    = PMA_mysql_query($sql_query) or PMA_mysqlDie('', '', '', $err_url);
    $message   = sprintf($strRenameTableOK, $old_name, $table);
    $reload    = 1;
}


/**
 * No new name for the table!
 */
else {
    include('./header.inc.php3');
    PMA_mysqlDie($strTableEmpty, '', '', $err_url);
}


/**
 * Back to the calling script
 */
require('./tbl_properties.php3');
?>
