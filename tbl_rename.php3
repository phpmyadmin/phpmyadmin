<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
$js_to_run = 'functions.js';
require('./lib.inc.php3');


/**
 * A new name has been submitted -> do the work
 */
if (isset($new_name) && trim($new_name) != '') { 
    $old_name     = $table;
    $table        = $new_name;
    if (get_magic_quotes_gpc()) {
        $new_name = stripslashes($new_name);
    }
    if (MYSQL_INT_VERSION < 32306) {
        check_reserved_words($new_name);
    }

    include('./header.inc.php3');
    mysql_select_db($db);
    $sql_query = 'ALTER TABLE ' . backquote($old_name) . ' RENAME ' . backquote($new_name);
    $result    = mysql_query($sql_query) or mysql_die();
    $message   = sprintf($strRenameTableOK, $old_name, $table);
    $reload    = 1;
} 


/**
 * No new name for the table!
 */
else { 
    include('./header.inc.php3');
    mysql_die($strTableEmpty); 
} 


/**
 * Back to the calling script
 */
require('./tbl_properties.php3');
?>
