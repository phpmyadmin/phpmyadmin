<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./lib.inc.php3');


/**
 * A new name has been submitted -> do the work
 */
if (isset($new_name) && trim($new_name) != '') { 
    $old_name = $table;
    $table    = $new_name;
    include('./header.inc.php3');
    mysql_select_db($db);
    $local_query = 'ALTER TABLE ' . backquote($old_name) . ' RENAME ' . backquote($new_name);
    $result      = mysql_query($local_query) or mysql_die('', $local_query);
    $message     = sprintf($strRenameTableOK, $old_name, $table);
    $reload      = 'true';
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
