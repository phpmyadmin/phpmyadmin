<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

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
function PMA_duplicate_table_info($work, $pma_table, $get_fields, $where_fields, $new_fields) {
    global $cfgRelation;

    $last_id = -1;

    if ($cfgRelation[$work]) {
        $select_parts = array();
        $row_fields = array();
        foreach ($get_fields AS $nr => $get_field) {
            $select_parts[] = PMA_backquote($get_field);
            $row_fields[$get_field] = 'cc';
        }

        $where_parts = array();
        foreach ($where_fields AS $_where => $_value) {
            $where_parts[] = PMA_backquote($_where) . ' = \'' . PMA_sqlAddslashes($_value) . '\'';
        }

        $new_parts = array();
        $new_value_parts = array();
        foreach ($new_fields AS $_where => $_value) {
            $new_parts[] = PMA_backquote($_where);
            $new_value_parts[] = PMA_sqlAddslashes($_value);
        }

        $table_copy_query = 'SELECT ' . implode(', ', $select_parts)
                          . ' FROM ' . PMA_backquote($cfgRelation[$pma_table])
                          . ' WHERE ' . implode(' AND ', $where_parts);
        $table_copy_rs    = PMA_query_as_cu($table_copy_query);

        while ($table_copy_row = @PMA_DBI_fetch_assoc($table_copy_rs)) {
            $value_parts = array();
            foreach ($table_copy_row AS $_key => $_val) {
                if (isset($row_fields[$_key]) && $row_fields[$_key] == 'cc') {
                    $value_parts[] = PMA_sqlAddslashes($_val);
                }
            }

            $new_table_query = 'INSERT IGNORE INTO ' . PMA_backquote($cfgRelation[$pma_table])
                            . ' (' . implode(', ', $select_parts) . ', ' . implode(', ', $new_parts) . ')'
                            . ' VALUES '
                            . ' (\'' . implode('\', \'', $value_parts) . '\', \'' . implode('\', \'', $new_value_parts) . '\')';

            $new_table_rs    = PMA_query_as_cu($new_table_query);
            $last_id = PMA_DBI_insert_id();
        } // end while

        return $last_id;
    }

    return true;
} // end of 'PMA_duplicate_table_info()' function


/**
 * Copies or renames table
 * FIXME: use RENAME
 *
 * @author          Michal Čihař <michal@cihar.com>
 */
function PMA_table_move_copy($source_db, $source_table, $target_db, $target_table, $what, $move) {
    global $cfgRelation, $dblist, $err_url, $sql_query;

    // set export settings we need
    $GLOBALS['use_backquotes'] = 1;
    $GLOBALS['asfile']         = 1;

    // Ensure the target is valid
    if (count($dblist) > 0 &&
        (PMA_isInto($source_db, $dblist) == -1 || PMA_isInto($target_db, $dblist) == -1)) {
        exit();
    }

    $source = PMA_backquote($source_db) . '.' . PMA_backquote($source_table);
    if (empty($target_db)) $target_db = $source_db;

    // This could avoid some problems with replicated databases, when
    // moving table from replicated one to not replicated one
    PMA_DBI_select_db($target_db);

    $target = PMA_backquote($target_db) . '.' . PMA_backquote($target_table);

    // do not create the table if dataonly
    if ($what != 'dataonly') {
        require_once('./libraries/export/sql.php');

        $no_constraints_comments = true;
        $sql_structure = PMA_getTableDef($source_db, $source_table, "\n", $err_url);
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
        if (isset($GLOBALS['drop_if_exists']) && $GLOBALS['drop_if_exists'] == 'true') {
            $drop_query = 'DROP TABLE IF EXISTS ' . PMA_backquote($target_db) . '.' . PMA_backquote($target_table);
            $result        = PMA_DBI_query($drop_query);

            if (isset($sql_query)) {
                $sql_query .= "\n" . $drop_query . ';';
            } else {
                $sql_query = $drop_query . ';';
            }

            // garvin: If an existing table gets deleted, maintain any entries
            // for the PMA_* tables
            $maintain_relations = TRUE;
        }

        $result        = @PMA_DBI_query($sql_structure);
        if (isset($sql_query)) {
            $sql_query .= "\n" . $sql_structure . ';';
        } else {
            $sql_query = $sql_structure . ';';
        }

        if (($move || isset($GLOBALS['constraints'])) && isset($GLOBALS['sql_constraints'])) {
            $parsed_sql =  PMA_SQP_parse($GLOBALS['sql_constraints']);

            $i = 0;
            while ($parsed_sql[$i]['type'] != 'quote_backtick') $i++;

            /* no need to PMA_backquote() */
            $parsed_sql[$i]['data'] = $target;

            /* Generate query back */
            $GLOBALS['sql_constraints'] = PMA_SQP_formatHtml($parsed_sql, 'query_only');
            $result          = PMA_DBI_query($GLOBALS['sql_constraints']);
            if (isset($sql_query)) {
                $sql_query .= "\n" . $GLOBALS['sql_constraints'];
            } else {
                $sql_query = $GLOBALS['sql_constraints'];
            }

            unset($GLOBALS['sql_constraints']);
        }

    } else {
        $sql_query='';
    }

    // Copy the data
    //if ($result != FALSE && ($what == 'data' || $what == 'dataonly')) {
    if ($what == 'data' || $what == 'dataonly') {
        $sql_insert_data = 'INSERT INTO ' . $target . ' SELECT * FROM ' . $source;
        PMA_DBI_query($sql_insert_data);
        $sql_query      .= "\n\n" . $sql_insert_data . ';';
    }

    require_once('./libraries/relation.lib.php');
    $cfgRelation = PMA_getRelationsParam();

    // Drops old table if the user has requested to move it
    if ($move) {

        // This could avoid some problems with replicated databases, when
        // moving table from replicated one to not replicated one
        PMA_DBI_select_db($source_db);

        $sql_drop_table = 'DROP TABLE ' . $source;
        PMA_DBI_query($sql_drop_table);

        // garvin: Move old entries from PMA-DBs to new table
        if ($cfgRelation['commwork']) {
            $remove_query = 'UPDATE ' . PMA_backquote($cfgRelation['column_info'])
                          . ' SET     table_name = \'' . PMA_sqlAddslashes($target_table) . '\', '
                          . '        db_name    = \'' . PMA_sqlAddslashes($target_db) . '\''
                          . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                          . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
            $rmv_rs    = PMA_query_as_cu($remove_query);
            unset($rmv_query);
        }

        // garvin: updating bookmarks is not possible since only a single table is moved,
        // and not the whole DB.
        // if ($cfgRelation['bookmarkwork']) {
        //     $remove_query = 'UPDATE ' . PMA_backquote($cfgRelation['bookmark'])
        //                   . ' SET     dbase = \'' . PMA_sqlAddslashes($target_db) . '\''
        //                   . ' WHERE dbase  = \'' . PMA_sqlAddslashes($source_db) . '\'';
        //     $rmv_rs    = PMA_query_as_cu($remove_query);
        //     unset($rmv_query);
        // }

        if ($cfgRelation['displaywork']) {
            $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                            . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\', '
                            . '         table_name = \'' . PMA_sqlAddslashes($target_table) . '\''
                            . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                            . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
            $tb_rs    = PMA_query_as_cu($table_query);
            unset($table_query);
            unset($tb_rs);
        }

        if ($cfgRelation['relwork']) {
            $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['relation'])
                            . ' SET     foreign_table = \'' . PMA_sqlAddslashes($target_table) . '\','
                            . '         foreign_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                            . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($source_db) . '\''
                            . ' AND foreign_table = \'' . PMA_sqlAddslashes($source_table) . '\'';
            $tb_rs    = PMA_query_as_cu($table_query);
            unset($table_query);
            unset($tb_rs);

            $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['relation'])
                            . ' SET     master_table = \'' . PMA_sqlAddslashes($target_table) . '\','
                            . '         master_db = \'' . PMA_sqlAddslashes($target_db) . '\''
                            . ' WHERE master_db  = \'' . PMA_sqlAddslashes($source_db) . '\''
                            . ' AND master_table = \'' . PMA_sqlAddslashes($source_table) . '\'';
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
                            . ' SET     table_name = \'' . PMA_sqlAddslashes($target_table) . '\','
                            . '         db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                            . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
                            . ' AND table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
            $tb_rs    = PMA_query_as_cu($table_query);
            unset($table_query);
            unset($tb_rs);
            /*
            $pdf_query = 'SELECT pdf_page_number '
                       . ' FROM ' . PMA_backquote($cfgRelation['table_coords'])
                       . ' WHERE db_name  = \'' . PMA_sqlAddslashes($target_db) . '\''
                       . ' AND table_name = \'' . PMA_sqlAddslashes($target_table) . '\'';
            $pdf_rs = PMA_query_as_cu($pdf_query);

            while ($pdf_copy_row = PMA_DBI_fetch_assoc($pdf_rs)) {
                $table_query = 'UPDATE ' . PMA_backquote($cfgRelation['pdf_pages'])
                                . ' SET     db_name = \'' . PMA_sqlAddslashes($target_db) . '\''
                                . ' WHERE db_name  = \'' . PMA_sqlAddslashes($source_db) . '\''
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
                                            db_name = \'' . PMA_sqlAddslashes($source_db) . '\' AND
                                            table_name = \'' . PMA_sqlAddslashes($source_table) . '\'';
                $comments_copy_rs    = PMA_query_as_cu($comments_copy_query);

                // Write every comment as new copied entry. [MIME]
                while ($comments_copy_row = PMA_DBI_fetch_assoc($comments_copy_rs)) {
                    $new_comment_query = 'REPLACE INTO ' . PMA_backquote($cfgRelation['column_info'])
                                . ' (db_name, table_name, column_name, ' . PMA_backquote('comment') . ($cfgRelation['mimework'] ? ', mimetype, transformation, transformation_options' : '') . ') '
                                . ' VALUES('
                                . '\'' . PMA_sqlAddslashes($target_db) . '\','
                                . '\'' . PMA_sqlAddslashes($target_table) . '\','
                                . '\'' . PMA_sqlAddslashes($comments_copy_row['column_name']) . '\''
                                . ($cfgRelation['mimework'] ? ',\'' . PMA_sqlAddslashes($comments_copy_row['comment']) . '\','
                                        . '\'' . PMA_sqlAddslashes($comments_copy_row['mimetype']) . '\','
                                        . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation']) . '\','
                                        . '\'' . PMA_sqlAddslashes($comments_copy_row['transformation_options']) . '\'' : '')
                                . ')';
                    $new_comment_rs    = PMA_query_as_cu($new_comment_query);
                } // end while
            }

            if ($source_db != $target_db) {
                $get_fields = array('user','label','query');
                $where_fields = array('dbase' => $source_db);
                $new_fields = array('dbase' => $target_db);
                PMA_duplicate_table_info('bookmarkwork', 'bookmark', $get_fields, $where_fields, $new_fields);
            }

            $get_fields = array('display_field');
            $where_fields = array('db_name' => $source_db, 'table_name' => $source_table);
            $new_fields = array('db_name' => $target_db, 'table_name' => $target_table);
            PMA_duplicate_table_info('displaywork', 'table_info', $get_fields, $where_fields, $new_fields);

            $get_fields = array('master_field', 'foreign_db', 'foreign_table', 'foreign_field');
            $where_fields = array('master_db' => $source_db, 'master_table' => $source_table);
            $new_fields = array('master_db' => $target_db, 'master_table' => $target_table);
            PMA_duplicate_table_info('relwork', 'relation', $get_fields, $where_fields, $new_fields);

            $get_fields = array('foreign_field', 'master_db', 'master_table', 'master_field');
            $where_fields = array('foreign_db' => $source_db, 'foreign_table' => $source_table);
            $new_fields = array('foreign_db' => $target_db, 'foreign_table' => $target_table);
            PMA_duplicate_table_info('relwork', 'relation', $get_fields, $where_fields, $new_fields);

            // garvin: [TODO] Can't get duplicating PDFs the right way. The page numbers always
            // get screwed up independently from duplication because the numbers do not
            // seem to be stored on a per-database basis. Would the author of pdf support
            // please have a look at it?
            /*
            $get_fields = array('page_descr');
            $where_fields = array('db_name' => $source_db);
            $new_fields = array('db_name' => $target_db);
            $last_id = PMA_duplicate_table_info('pdfwork', 'pdf_pages', $get_fields, $where_fields, $new_fields);

            if (isset($last_id) && $last_id >= 0) {
                $get_fields = array('x', 'y');
                $where_fields = array('db_name' => $source_db, 'table_name' => $source_table);
                $new_fields = array('db_name' => $target_db, 'table_name' => $target_table, 'pdf_page_number' => $last_id);
                PMA_duplicate_table_info('pdfwork', 'table_coords', $get_fields, $where_fields, $new_fields);
            }
            */
        }
    }

}
?>
