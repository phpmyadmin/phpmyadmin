<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Check parameters

require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db', 'table'));

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

    $sql_insert = preg_replace('~INSERT INTO (`?)' . $table . '(`?)~i', 'INSERT INTO ' . $target, $sql_insert);
    $result     = PMA_mysql_query($sql_insert) or PMA_mysqlDie('', $sql_insert, '', $GLOBALS['err_url']);

    $sql_insert_data .= $sql_insert . ';' . "\n";
} // end of the 'PMA_myHandler()' function

/**
 * Inserts existing entries in a PMA_* table by reading a value from an old entry
 *
 * @param   string  The array index, which Relation feature to check
 *                  ('relwork', 'commwork', ...)
 * @param   string  The array index, which PMA-table to update
 *                  ('bookmark', 'relation', ...)
 * @param   array   Which fields will be SELECT'ed from the old entry
 * @param   array   Which fields will be used for the WHERE query
 *                  (array('FIELDNAME' => 'FIELDVALUE'))
 * @param   array   Which fields will be used as new VALUES. These are the important
 *                  keys which differ from the old entry.
 *                  (array('FIELDNAME' => 'NEW FIELDVALUE'))

 * @global  string  relation variable
 *
 * @author          Garvin Hicking <me@supergarv.de>
 */
function PMA_duplicate_table($work, $pma_table, $get_fields, $where_fields, $new_fields) {
global $cfgRelation;

    $last_id = -1;

    if ($cfgRelation[$work]) {
        $select_parts = array();
        $row_fields = array();
        foreach($get_fields AS $nr => $get_field) {
            $select_parts[] = PMA_backquote($get_field);
            $row_fields[$get_field] = 'cc';
        }

        $where_parts = array();
        foreach($where_fields AS $_where => $_value) {
            $where_parts[] = PMA_backquote($_where) . ' = \'' . PMA_sqlAddslashes($_value) . '\'';
        }

        $new_parts = array();
        $new_value_parts = array();
        foreach($new_fields AS $_where => $_value) {
            $new_parts[] = PMA_backquote($_where);
            $new_value_parts[] = PMA_sqlAddslashes($_value);
        }

        $table_copy_query = 'SELECT ' . implode(', ', $select_parts)
                          . ' FROM ' . PMA_backquote($cfgRelation[$pma_table])
                          . ' WHERE ' . implode(' AND ', $where_parts);
        $table_copy_rs    = PMA_query_as_cu($table_copy_query);

        while ($table_copy_row = @PMA_mysql_fetch_array($table_copy_rs)) {
            $value_parts = array();
            foreach($table_copy_row AS $_key => $_val) {
                if (isset($row_fields[$_key]) && $row_fields[$_key] == 'cc') {
                    $value_parts[] = PMA_sqlAddslashes($_val);
                }
            }

            $new_table_query = 'INSERT IGNORE INTO ' . PMA_backquote($cfgRelation[$pma_table])
                            . ' (' . implode(', ', $select_parts) . ', ' . implode(', ', $new_parts) . ')'
                            . ' VALUES '
                            . ' (\'' . implode('\', \'', $value_parts) . '\', \'' . implode('\', \'', $new_value_parts) . '\')';

            $new_table_rs    = PMA_query_as_cu($new_table_query);
            $last_id = (@function_exists('mysql_insert_id') ? @mysql_insert_id() : -1);
        } // end while

        return $last_id;
    }

    return true;
} // end of 'PMA_duplicate_table()' function

/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');


/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_properties.php?' . PMA_generate_common_url($db, $table);


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

    // Ensure the target is valid
    if (count($dblist) > 0 &&
        (PMA_isInto($db, $dblist) == -1 || PMA_isInto($target_db, $dblist) == -1)) {
        exit();
    }

    if ($db == $target_db && $new_name == $table) {
        $message   = (isset($submit_move) ? $strMoveTableSameNames : $strCopyTableSameNames);
    } else {
        $source = PMA_backquote($db) . '.' . PMA_backquote($table);
        if (empty($target_db)) $target_db = $db;

        // This could avoid some problems with replicated databases, when
        // moving table from replicated one to not replicated one
        PMA_mysql_select_db($target_db);

        $target = PMA_backquote($target_db) . '.' . PMA_backquote($new_name);

        // do not create the table if dataonly
        if ($what != 'dataonly') {
            require('./libraries/export/sql.php');

            $no_constraints_comments = true;
            $sql_structure = PMA_getTableDef($db, $table, "\n", $err_url);
            unset($no_constraints_comments);

            $parsed_sql =  PMA_SQP_parse($sql_structure);

            /* nijel: Find table name in query and replace it */
            $i = 0;
            while ($parsed_sql[$i]['type'] != 'quote_backtick') $i++;

            /* no need to PMA_backquote() */
            $parsed_sql[$i]['data'] = $target;

            /* Generate query back */
            $sql_structure = PMA_SQP_formatHtml($parsed_sql, 'query_only');

            // If table exists, and 'add drop table' is selected: Drop it!
            $drop_query = '';
            if (isset($drop_if_exists) && $drop_if_exists == 'true') {
                $drop_query = 'DROP TABLE IF EXISTS ' . PMA_backquote($target_db) . '.' . PMA_backquote($new_name);
                $result        = @PMA_mysql_query($drop_query);
                if (PMA_mysql_error()) {
                    require_once('./header.inc.php');
                    PMA_mysqlDie('', $sql_structure, '', $err_url);
                }

                if (isset($sql_query)) {
                    $sql_query .= "\n" . $drop_query . ';';
                } else {
                    $sql_query = $drop_query . ';';
                }

                // garvin: If an existing table gets deleted, maintain any entries
                // for the PMA_* tables
                $maintain_relations = true;
            }

            $result        = @PMA_mysql_query($sql_structure);
            if (PMA_mysql_error()) {
                require_once('./header.inc.php');
                PMA_mysqlDie('', $sql_structure, '', $err_url);
            } else if (isset($sql_query)) {
                $sql_query .= "\n" . $sql_structure . ';';
            } else {
                $sql_query = $sql_structure . ';';
            }

            if ((isset($submit_move) || isset($constraints)) && isset($sql_constraints)) {
                $parsed_sql =  PMA_SQP_parse($sql_constraints);

                $i = 0;
                while ($parsed_sql[$i]['type'] != 'quote_backtick') $i++;

                /* no need to PMA_backquote() */
                $parsed_sql[$i]['data'] = $target;

                /* Generate query back */
                $sql_constraints = PMA_SQP_formatHtml($parsed_sql, 'query_only');
                $result        = @PMA_mysql_query($sql_constraints);
                if (PMA_mysql_error()) {
                    require_once('./header.inc.php');
                    PMA_mysqlDie('', $sql_structure, '', $err_url);
                } else if (isset($sql_query)) {
                    $sql_query .= "\n" . $sql_constraints;
                } else {
                    $sql_query = $sql_constraints;
                }
            }

        } else {
            $sql_query='';
        }

        // Copy the data
        if ($result != FALSE && ($what == 'data' || $what == 'dataonly')) {
            $sql_insert_data = 'INSERT INTO ' . $target . ' SELECT * FROM ' . $source;
            $result          = @PMA_mysql_query($sql_insert_data);
            if (PMA_mysql_error()) {
                require_once('./header.inc.php');
                PMA_mysqlDie('', $sql_insert_data, '', $err_url);
            }
            $sql_query .= "\n\n" . $sql_insert_data . ';';
        }

        require_once('./libraries/relation.lib.php');
        $cfgRelation = PMA_getRelationsParam();

        // Drops old table if the user has requested to move it
        if (isset($submit_move)) {

            // This could avoid some problems with replicated databases, when
            // moving table from replicated one to not replicated one
            PMA_mysql_select_db($db);

            $sql_drop_table = 'DROP TABLE ' . $source;
            $result         = @PMA_mysql_query($sql_drop_table);
            if (PMA_mysql_error()) {
                require_once('./header.inc.php');
                PMA_mysqlDie('', $sql_drop_table, '', $err_url);
            }

            // garvin: Move old entries from PMA-DBs to new table
            if ($cfgRelation['commwork']) {
                $remove_query = 'UPDATE ' . PMA_backquote($cfgRelation['column_info'])
                              . ' SET     table_name = \'' . PMA_sqlAddslashes($new_name) . '\', '
                              . '        db_name    = \'' . PMA_sqlAddslashes($target_db) . '\''
                              . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                              . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
                $rmv_rs    = PMA_query_as_cu($remove_query);
                unset($rmv_query);
            }

            // garvin: updating bookmarks is not possible since only a single table is moved,
            // and not the whole DB.
            // if ($cfgRelation['bookmarkwork']) {
            //     $remove_query = 'UPDATE ' . PMA_backquote($cfgRelation['bookmark'])
            //                   . ' SET     dbase = \'' . PMA_sqlAddslashes($target_db) . '\''
            //                   . ' WHERE dbase  = \'' . PMA_sqlAddslashes($db) . '\'';
            //     $rmv_rs    = PMA_query_as_cu($remove_query);
            //     unset($rmv_query);
            // }

            if ($cfgRelation['displaywork']) {
                $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                                . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\', '
                                . '         table_name = \'' . PMA_sqlAddslashes($new_name) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);
            }

            if ($cfgRelation['relwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['relation'])
                                . ' SET     foreign_table = \'' . PMA_sqlAddslashes($new_name) . '\','
                                . '         foreign_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);

                $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['relation'])
                                . ' SET     master_table = \'' . PMA_sqlAddslashes($new_name) . '\','
                                . '         master_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);
            }

            // garvin: [TODO] Can't get moving PDFs the right way. The page numbers always
            // get screwed up independently from duplication because the numbers do not
            // seem to be stored on a per-database basis. Would the author of pdf support
            // please have a look at it?

            if ($cfgRelation['pdfwork']) {
                $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['table_coords'])
                                . ' SET     table_name = \'' . PMA_sqlAddslashes($new_name) . '\','
                                . '         db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
                $tb_rs    = PMA_query_as_cu($table_query);
                unset($table_query);
                unset($tb_rs);
                /*
                $pdf_query = 'SELECT pdf_page_number '
                           . ' FROM ' . PMA_backquote($cfgRelation['table_coords'])
                           . ' WHERE db_name  = \'' . PMA_sqlAddslashes($target_db) . '\''
                           . ' AND table_name = \'' . PMA_sqlAddslashes($new_name) . '\'';
                $pdf_rs = PMA_query_as_cu($pdf_query);

                while ($pdf_copy_row = @PMA_mysql_fetch_array($pdf_rs)) {
                    $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['pdf_pages'])
                                    . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                                    . ' AND page_nr = \'' . PMA_sqlAddslashes($pdf_copy_row['pdf_page_number']) . '\'';
                    $tb_rs    = PMA_query_as_cu($table_query);
                    unset($table_query);
                    unset($tb_rs);
                }
                */
            }

            $sql_query      .= "\n\n" . $sql_drop_table . ';';
        } else {
            // garvin: Create new entries as duplicates from old PMA DBs
            if ($what != 'dataonly' && !isset($maintain_relations)) {
                if ($cfgRelation['commwork']) {
                    // Get all comments and MIME-Types for current table
                    $comments_copy_query = 'SELECT
                                                column_name, ' . PMA_backquote('comment') . ($cfgRelation['mimework'] ? ', mimetype, transformation, transformation_options' : '') . '
                                            FROM ' . PMA_backquote($cfgRelation['column_info']) . '
                                            WHERE
                                                db_name = \'' . PMA_sqlAddslashes($db) . '\' AND
                                                table_name = \'' . PMA_sqlAddslashes($table) . '\'';
                    $comments_copy_rs    = PMA_query_as_cu($comments_copy_query);

                    // Write every comment as new copied entry. [MIME]
                    while ($comments_copy_row = @PMA_mysql_fetch_array($comments_copy_rs)) {
                        $new_comment_query = 'REPLACE INTO ' . PMA_backquote($cfgRelation['column_info'])
                                    . ' (db_name, table_name, column_name, ' . PMA_backquote('comment') . ($cfgRelation['mimework'] ? ', mimetype, transformation, transformation_options' : '') . ') '
                                    . ' VALUES('
                                    . '\'' . PMA_sqlAddslashes($target_db) . '\','
                                    . '\'' . PMA_sqlAddslashes($new_name) . '\','
                                    . '\'' . PMA_sqlAddslashes($comments_copy_row['column_name']) . '\''
                                    . ($cfgRelation['mimework'] ? ',\'' . PMA_sqlAddslashes($comments_copy_row['comment']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['mimetype']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation']) . '\','
                                            . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation_options']) . '\'' : '')
                                    . ')';
                        $new_comment_rs    = PMA_query_as_cu($new_comment_query);
                    } // end while
                }

                if ($db != $target_db) {
                    $get_fields = array('user','label','query');
                    $where_fields = array('dbase' => $db);
                    $new_fields = array('dbase' => $target_db);
                    PMA_duplicate_table('bookmarkwork', 'bookmark', $get_fields, $where_fields, $new_fields);
                }

                $get_fields = array('display_field');
                $where_fields = array('db_name' => $db, 'table_name' => $table);
                $new_fields = array('db_name' => $target_db, 'table_name' => $new_name);
                PMA_duplicate_table('displaywork', 'table_info', $get_fields, $where_fields, $new_fields);

                $get_fields = array('master_field', 'foreign_db', 'foreign_table', 'foreign_field');
                $where_fields = array('master_db' => $db, 'master_table' => $table);
                $new_fields = array('master_db' => $target_db, 'master_table' => $new_name);
                PMA_duplicate_table('relwork', 'relation', $get_fields, $where_fields, $new_fields);

                $get_fields = array('foreign_field', 'master_db', 'master_table', 'master_field');
                $where_fields = array('foreign_db' => $db, 'foreign_table' => $table);
                $new_fields = array('foreign_db' => $target_db, 'foreign_table' => $new_name);
                PMA_duplicate_table('relwork', 'relation', $get_fields, $where_fields, $new_fields);

                // garvin: [TODO] Can't get duplicating PDFs the right way. The page numbers always
                // get screwed up independently from duplication because the numbers do not
                // seem to be stored on a per-database basis. Would the author of pdf support
                // please have a look at it?
                /*
                $get_fields = array('page_descr');
                $where_fields = array('db_name' => $db);
                $new_fields = array('db_name' => $target_db);
                $last_id = PMA_duplicate_table('pdfwork', 'pdf_pages', $get_fields, $where_fields, $new_fields);

                if (isset($last_id) && $last_id >= 0) {
                    $get_fields = array('x', 'y');
                    $where_fields = array('db_name' => $db, 'table_name' => $table);
                    $new_fields = array('db_name' => $target_db, 'table_name' => $new_name, 'pdf_page_number' => $last_id);
                    PMA_duplicate_table('pdfwork', 'table_coords', $get_fields, $where_fields, $new_fields);
                }
                */
            }
        }

        $message   = (isset($submit_move) ? $strMoveTableOK : $strCopyTableOK);
        $message   = sprintf($message, htmlspecialchars($source), htmlspecialchars($target));
        $reload    = 1;
        $js_to_run = 'functions.js';
        /* Check: Work on new table or on old table? */
        if (isset($submit_move)) {
            $db        = $target_db;
            $table     = $new_name;
        } else {
            $pma_uri_parts = parse_url($cfg['PmaAbsoluteUri']);
            if (isset($switch_to_new) && $switch_to_new == 'true') {
                setcookie('pma_switch_to_new', 'true', 0, substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/')), '', ($pma_uri_parts['scheme'] == 'https'));
                $db             = $target_db;
                $table          = $new_name;
            } else {
                setcookie('pma_switch_to_new', '', 0, substr($pma_uri_parts['path'], 0, strrpos($pma_uri_parts['path'], '/')), '', ($pma_uri_parts['scheme'] == 'https'));
                // garvin:Keep original table for work.
            }
        }
    }
    require_once('./header.inc.php');
} // end is target table name


/**
 * No new name for the table!
 */
else {
    require_once('./header.inc.php');
    PMA_mysqlDie($strTableEmpty, '', '', $err_url);
}


/**
 * Back to the calling script
 */

require('./tbl_properties.php');
?>
