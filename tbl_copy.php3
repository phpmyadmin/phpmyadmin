<?php
/* $Id$ */


/**
 * Insert datas from one table to another one
 *
 * @param   string  the original insert statement
 *
 * @global  string  the database name
 * @global  string  the original table name
 * @global  string  the target database and table names
 * @global  string  the sql query used to copy the data
 */
function my_handler($sql_insert = '')
{
    global $db, $table, $target;
    global $sql_insert_data;

    $sql_insert = eregi_replace('INSERT INTO (`?)' . $table . '(`?)', 'INSERT INTO ' . $target, $sql_insert);
    $result     = mysql_query($sql_insert) or mysql_die('', $sql_insert, '', $GLOBALS['err_url']);
    
    $sql_insert_data .= $sql_insert . ';' . "\n";
} // end of the 'my_handler' function


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
$js_to_run = 'functions.js';
require('./header.inc.php3');


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php3'
         . '?lang=' . $lang
         . '&server=' . $server
         . '&db=' . urlencode($db)
         . '&table=' . urlencode($table);


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

    if (get_magic_quotes_gpc()) {
        if (!empty($target_db)) {
            $target_db = stripslashes($target_db);
        } else {
            $target_db = stripslashes($db);
        }
        $new_name      = stripslashes($new_name);
    }
    if (MYSQL_INT_VERSION < 32306) {
        check_reserved_words($db, $err_url);
        check_reserved_words($table, $err_url);
    }

    $source = backquote($db) . '.' . backquote($table);
    $target = backquote($target_db) . '.' . backquote($new_name);

    include('./libraries/build_dump.lib.php3');

    $sql_structure = get_table_def($db, $table, "\n", $err_url);
    $sql_structure = eregi_replace('^CREATE TABLE (`?)' . $table . '(`?)', 'CREATE TABLE ' . $target, $sql_structure);
    $result        = mysql_query($sql_structure) or mysql_die('', $sql_structure, '', $err_url);
    if (isset($sql_query)) {
        $sql_query .= "\n" . $sql_structure . ';';
    } else {
        $sql_query = $sql_structure . ';';
    }

    // Copy the data
    if ($result != FALSE && $what == 'data') {
        // speedup copy table - staybyte - 22. Juni 2001
        if (MYSQL_INT_VERSION >= 32300) {
            $sql_insert_data = 'INSERT INTO ' . $target . ' SELECT * FROM ' . backquote($table);
            $result          = mysql_query($sql_insert_data) or mysql_die('', $sql_insert_data, '', $err_url);
        } // end MySQL >= 3.23
        else {
            $sql_insert_data = '';
            get_table_content($db, $table, 0, 0, 'my_handler', $err_url);
        } // end MySQL < 3.23
        $sql_query .= "\n\n" . $sql_insert_data;
    }

    $message  = sprintf($strCopyTableOK, $source, $target);
    $reload   = 1;
} // end is target table name


/**
 * No new name for the table!
 */
else {
    mysql_die($strTableEmpty, '', '', $err_url);
} 


/**
 * Back to the calling script
 */
require('./tbl_properties.php3');
?>
