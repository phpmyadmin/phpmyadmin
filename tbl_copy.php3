<?php
/* $Id$ */


/**
 * Insert datas from one table to another one
 *
 * @param   string  the original insert statement
 *
 * @global  string  the database name
 * @global  string  the original table name
 * @global  string  the target table name
 * @global  string  the sql query used to copy the data
 */
function my_handler($sql_insert = '')
{
    global $db, $table, $new_name;
    global $sql_insert_data;

    $sql_insert = ereg_replace('INSERT INTO (`?)' . $table . '(`?)', 'INSERT INTO ' . backquote($new_name), $sql_insert);
    $result     = mysql_query($sql_insert) or mysql_die();
    
    $sql_insert_data .= $sql_insert . ';' . "\n";
} // end of the 'my_handler' function


/**
 * Gets some core libraries
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');


/**
 * Selects the database to work with
 */
mysql_select_db($db);


/**
 * A target table name has been sent to this script -> do the work
 */
if (isset($new_name) && trim($new_name) != '') {
    $use_backquotes = 1;
    $asfile         = 1;

    $sql_structure = get_table_def($db, $table, "\n");
    $sql_structure = ereg_replace('^CREATE TABLE (`?)' . $table . '(`?)', 'CREATE TABLE ' . backquote($new_name), $sql_structure);
    $result        = mysql_query($sql_structure) or mysql_die();
    if (isset($sql_query)) {
        $sql_query .= "\n" . $sql_structure . ';';
    } else {
        $sql_query = $sql_structure . ';';
    }

    // Copy the data
    if ($result != FALSE && $what == 'data') {
        // speedup copy table - staybyte - 22. Juni 2001
        if (MYSQL_MAJOR_VERSION >= 3.23) {
            $sql_insert_data = 'INSERT INTO ' . backquote($new_name) . ' SELECT * FROM ' . backquote($table);
            $result          = mysql_query($sql_insert_data) or mysql_die();
        } // end MySQL >= 3.23
        else {
            $sql_insert_data = '';
            get_table_content($db, $table, 0, 0, 'my_handler');
        } // end MySQL < 3.23
        $sql_query .= "\n\n" . $sql_insert_data;
    }

    $message  = sprintf($strCopyTableOK, $table, $new_name);
    $reload   = 'true';
} // end is target table name


/**
 * No new name for the table!
 */
else {
    mysql_die($strTableEmpty);
} 


/**
 * Back to the calling script
 */
require('./db_details.php3');
?>
