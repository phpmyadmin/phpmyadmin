<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Insert data from one table to another one
 *
 * @param   string  the original insert statement
 *
 * @global  string  the database name
 * @global  string  the original table name
 * @global  string  the target database and table names
 * @global  string  the sql query used to copy the data
 */
function PMA_myHandler($sql_insert = '')
{
    global $db, $table, $target;
    global $sql_insert_data;

    $sql_insert = eregi_replace('INSERT INTO (`?)' . $table . '(`?)', 'INSERT INTO ' . $target, $sql_insert);
    $result     = PMA_mysql_query($sql_insert) or PMA_mysqlDie('', $sql_insert, '', $GLOBALS['err_url']);

    $sql_insert_data .= $sql_insert . ';' . "\n";
} // end of the 'PMA_myHandler()' function


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php3?' . PMA_generate_common_url($db, $table);


/**
 * Selects the database to work with
 */
PMA_mysql_select_db($db);


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

    // Ensure the target is valid
    if (count($dblist) > 0 &&
        (PMA_isInto($db, $dblist) == -1 || PMA_isInto($target_db, $dblist) == -1)) {
        exit();
    }
    if (PMA_MYSQL_INT_VERSION < 32306) {
        PMA_checkReservedWords($target_db, $err_url);
        PMA_checkReservedWords($new_name, $err_url);
    }

    $source = PMA_backquote($db) . '.' . PMA_backquote($table);
    $target = PMA_backquote($target_db) . '.' . PMA_backquote($new_name);

    include('./libraries/build_dump.lib.php3');

    $sql_structure = PMA_getTableDef($db, $table, "\n", $err_url);
    $parsed_sql =  PMA_SQP_parse($sql_structure);
    // no need to PMA_backquote()
    $parsed_sql[2]['data'] = $target;
    $sql_structure = PMA_SQP_formatHtml($parsed_sql, 'query_only');

    // do not create the table if dataonly
    if ($what != 'dataonly') {
        $result        = @PMA_mysql_query($sql_structure);
        if (PMA_mysql_error()) {
            include('./header.inc.php3');
            PMA_mysqlDie('', $sql_structure, '', $err_url);
        } else if (isset($sql_query)) {
            $sql_query .= "\n" . $sql_structure . ';';
        } else {
            $sql_query = $sql_structure . ';';
        }
    } else {
        $sql_query='';
    }

    // Copy the data
    if ($result != FALSE && ($what == 'data' || $what == 'dataonly')) {
        // speedup copy table - staybyte - 22. Juni 2001
        if (PMA_MYSQL_INT_VERSION >= 32300) {
            $sql_insert_data = 'INSERT INTO ' . $target . ' SELECT * FROM ' . $source;
            $result          = @PMA_mysql_query($sql_insert_data);
            if (PMA_mysql_error()) {
                include('./header.inc.php3');
                PMA_mysqlDie('', $sql_insert_data, '', $err_url);
            }
        } // end MySQL >= 3.23
        else {
            $sql_insert_data = '';
            PMA_getTableContent($db, $table, 0, 0, 'PMA_myHandler', $err_url,'');
        } // end MySQL < 3.23
        $sql_query .= "\n\n" . $sql_insert_data;
    }

    include('./libraries/relation.lib.php3');
    $cfgRelation = PMA_getRelationsParam();

    // Drops old table if the user has requested to move it
    if (isset($submit_move)) {
        $sql_drop_table = 'DROP TABLE ' . $source;
        $result         = @PMA_mysql_query($sql_drop_table);
        if (PMA_mysql_error()) {
            include('./header.inc.php3');
            PMA_mysqlDie('', $sql_drop_table, '', $err_url);
        }

        // garvin: Move old entries from comments to new table
        if ($cfgRelation['commwork']) {
            $remove_query = 'UPDATE ' . PMA_backquote($cfgRelation['column_info'])
                          . ' SET     table_name = \'' . PMA_sqlAddslashes($new_name) . '\', '
                          . '        db_name    = \'' . PMA_sqlAddslashes($target_db) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                          . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
            $rmv_rs    = PMA_query_as_cu($remove_query);
            unset($rmv_query);
        }

        $sql_query      .= "\n\n" . $sql_drop_table . ';';
        $db             = $target_db;
        $table          = $new_name;
    } else {
        // garvin: Create new entries as duplicates from old comments
        if ($cfgRelation['commwork']) {
            // Get all comments and MIME-Types for current table
            $comments_copy_query = 'SELECT
                                        column_name, ' . PMA_backquote('comment') . '
                                    FROM ' . PMA_backquote($cfgRelation['column_info']) . '
                                    WHERE
                                        db_name = \'' . PMA_sqlAddslashes($db) . '\' AND
                                        table_name = \'' . PMA_sqlAddslashes($table) . '\'';
            $comments_copy_rs    = PMA_query_as_cu($comments_copy_query);

            // Write every comment as new copied entry. [MIME]
            while ($comments_copy_row = @PMA_mysql_fetch_array($comments_copy_rs)) {
                $new_comment_query = 'INSERT INTO ' . PMA_backquote($cfgRelation['column_comments'])
                           . ' (db_name, table_name, column_name, ' . PMA_backquote('comment') . ') '
                           . ' VALUES('
                           . '\'' . PMA_sqlAddslashes($target_db) . '\','
                          . '\'' . PMA_sqlAddslashes($new_name) . '\','
                          . '\'' . PMA_handleSlashes($comments_copy_row['comment']) . '\')';
                $new_comment_rs    = PMA_query_as_cu($new_comment_query);
            } // end while
        }
    }

    $message   = (isset($submit_move) ? $strMoveTableOK : $strCopyTableOK);
    $message   = sprintf($message, $source, $target);
    $reload    = 1;
    $js_to_run = 'functions.js';
    include('./header.inc.php3');
} // end is target table name


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
