<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
error_reporting(E_ALL);
/**
 * Set of functions used to build SQL dumps of tables
 */

/**
 * Returns $table's field types
 *
 * @param   string   the database name
 * @param   string   the table name
 *
 * @return  array    the field types; key of array is PMA_backquote
 *                   of the field name
 *
 * @access  public
 *
 * This function exists because mysql_field_type() returns 'blob'
 * even for 'text' fields.
 */
function PMA_fieldTypes($db, $table,$use_backquotes) {
    PMA_mysql_select_db($db);
    $table_def = PMA_mysql_query('SHOW FIELDS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table));
    while($row = @PMA_mysql_fetch_array($table_def)) {
        $types[PMA_backquote($row['Field'],$use_backquotes)] = ereg_replace('\\(.*', '', $row['Type']);
    }
    return $types;
}

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  bool        Whether it suceeded
 */
function PMA_exportComment($text) {
    return PMA_exportOutputHandler('# ' . $text . $GLOBALS['crlf']);
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader() {
    global $crlf;
    global $cfg;

    $head  =  '# phpMyAdmin SQL Dump' . $crlf
           .  '# version ' . PMA_VERSION . $crlf
           .  '# http://www.phpmyadmin.net' . $crlf
           .  '#' . $crlf
           .  '# ' . $GLOBALS['strHost'] . ': ' . $cfg['Server']['host'];
    if (!empty($cfg['Server']['port'])) {
         $head .= ':' . $cfg['Server']['port'];
    }
    $head .= $crlf
           .  '# ' . $GLOBALS['strGenTime'] . ': ' . PMA_localisedDate() . $crlf
           .  '# ' . $GLOBALS['strServerVersion'] . ': ' . substr(PMA_MYSQL_INT_VERSION, 0, 1) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 1, 2) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 3) . $crlf
           .  '# ' . $GLOBALS['strPHPVersion'] . ': ' . phpversion() . $crlf;
    return PMA_exportOutputHandler($head);
}

/**
 * Outputs create database database
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBCreate($db) {
    global $crlf;
    if (isset($GLOBALS['drop_database'])) {
        if (!PMA_exportOutputHandler('DROP DATABASE ' . (isset($GLOBALS['use_backquotes']) ? PMA_backquote($db) : $db) . ';' . $crlf)) return FALSE;
    }
    if (!PMA_exportOutputHandler('CREATE DATABASE ' . (isset($GLOBALS['use_backquotes']) ? PMA_backquote($db) : $db) . ';' . $crlf)) return FALSE;
    return PMA_exportOutputHandler('USE ' . $db . ';' . $crlf);
}

/**
 * Outputs database header
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBHeader($db) {
    global $crlf;
    $head = '# ' . $crlf
          . '# ' . $GLOBALS['strDatabase'] . ': ' . (isset($GLOBALS['use_backquotes']) ? PMA_backquote($db) : '\'' . $db . '\''). $crlf
          . '# ' . $crlf;
    return PMA_exportOutputHandler($head);
}

/**
 * Outputs database footer
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBFooter($db) {
    if (isset($GLOBALS['sql_constraints'])) return PMA_exportOutputHandler($GLOBALS['sql_constraints']);
    return TRUE;
}

/**
 * Returns $table's CREATE definition
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   string   the url to go back in case of error
 * @param   boolean  whether to include creation/update/check dates
 *
 * @return  string   resulting schema
 *
 * @global  boolean  whether to add 'drop' statements or not
 * @global  boolean  whether to use backquotes to allow the use of special
 *                   characters in database, table and fields names or not
 *
 * @access  public
 */
function PMA_getTableDef($db, $table, $crlf, $error_url, $show_dates = false)
{
    global $drop;
    global $use_backquotes;
    global $cfgRelation;
    global $sql_constraints;

    $schema_create = '';
    $auto_increment = '';
    $new_crlf = $crlf;


    $result = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . PMA_sqlAddslashes($table) . '\'');
    if ($result != FALSE) {
        if (mysql_num_rows($result) > 0) {
            $tmpres        = PMA_mysql_fetch_array($result);
            if (isset($GLOBALS['auto_increment']) && !empty($tmpres['Auto_increment'])) {
                $auto_increment .= ' AUTO_INCREMENT=' . $tmpres['Auto_increment'] . ' ';
            }

            if ($show_dates && isset($tmpres['Create_time']) && !empty($tmpres['Create_time'])) {
                $schema_create .= '# ' . $GLOBALS['strStatCreateTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Create_time'])) . $crlf;
                $new_crlf = '#' . $crlf . $crlf;
            }

            if ($show_dates && isset($tmpres['Update_time']) && !empty($tmpres['Update_time'])) {
                $schema_create .= '# ' . $GLOBALS['strStatUpdateTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Update_time'])) . $crlf;
                $new_crlf = '#' . $crlf . $crlf;
            }

            if ($show_dates && isset($tmpres['Check_time']) && !empty($tmpres['Check_time'])) {
                $schema_create .= '# ' . $GLOBALS['strStatCheckTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Check_time'])) . $crlf;
                $new_crlf = '#' . $crlf . $crlf;
            }
        mysql_free_result($result);
        }
    }

    $schema_create .= $new_crlf;

    if (!empty($drop)) {
        $schema_create .= 'DROP TABLE IF EXISTS ' . PMA_backquote($table, $use_backquotes) . ';' . $crlf;
    }

    // Steve Alberty's patch for complete table dump,
    // Whether to quote table and fields names or not
    if ($use_backquotes) {
        PMA_mysql_query('SET SQL_QUOTE_SHOW_CREATE = 1');
    } else {
        PMA_mysql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
    }
    $result = PMA_mysql_query('SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table));
    if ($result != FALSE && mysql_num_rows($result) > 0) {
        $tmpres        = PMA_mysql_fetch_array($result);
        // Fix for case problems with winwin, thanks to
        // Pawe³ Szczepañski <pauluz at users.sourceforge.net>
        $pos           = strpos($tmpres[1], ' (');

        // Fix a problem with older versions of mysql
        // Find the first opening parenthesys, i.e. that after the name
        // of the table
        $pos2          = strpos($tmpres[1], '(');
        // Old mysql did not insert a space after table name
        // in query "show create table ..."!
        if ($pos2 != $pos + 1)
        {
            // This is the real position of the first character after
            // the name of the table
            $pos = $pos2;
            // Old mysql did not even put newlines and indentation...
            $tmpres[1] = str_replace(",", ",\n     ", $tmpres[1]);
        }

        $tmpres[1]     = substr($tmpres[1], 0, 13)
                       . (($use_backquotes) ? PMA_backquote($tmpres[0]) : $tmpres[0])
                       . substr($tmpres[1], $pos);
        $tmpres[1]     = str_replace("\n", $crlf, $tmpres[1]);
        if (preg_match_all('((,\r?\n[\s]*(CONSTRAINT|FOREIGN[\s]*KEY)[^\r\n,]+)+)', $tmpres[1], $regs)) {
            if (!isset($sql_constraints)) {
                if (isset($GLOBALS['no_constraints_comments'])) {
                    $sql_constraints = '';
                } else {
                    $sql_constraints = $crlf . '#' . $crlf
                                        . '# ' . $GLOBALS['strConstraintsForDumped'] . $crlf
                                        . '#' . $crlf;
                }
            }
            if (!isset($GLOBALS['no_constraints_comments'])) {
                $sql_constraints .= $crlf .'#' . $crlf .'# ' . $GLOBALS['strConstraintsForTable'] . ' ' . PMA_backquote($table) . $crlf . '#' . $crlf;
            }
            $sql_constraints .= 'ALTER TABLE ' . PMA_backquote($table) . $crlf
                             . preg_replace('/(,\r?\n|^)([\s]*)(CONSTRAINT|FOREIGN[\s]*KEY)/', '\1\2ADD \3', substr($regs[0][0], 2))
                            . ";\n";
            $tmpres[1]     = preg_replace('((,\r?\n[\s]*(CONSTRAINT|FOREIGN[\s]*KEY)[^\r\n,]+)+)', '', $tmpres[1]);
        }
        $schema_create .= $tmpres[1];
    }

    $schema_create .= $auto_increment;


    mysql_free_result($result);
    return $schema_create;
} // end of the 'PMA_getTableDef()' function


/**
 * Returns $table's comments, relations etc.
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   boolean  whether to include relation comments
 * @param   boolean  whether to include column comments
 * @param   boolean  whether to include mime comments
 *
 * @return  string   resulting comments
 *
 * @access  public
 */
function PMA_getTableComments($db, $table, $crlf, $do_relation = false, $do_comments = false, $do_mime = false)
{
    global $cfgRelation;
    global $use_backquotes;
    global $sql_constraints;

    $schema_create = '';

    if ($do_comments && $cfgRelation['commwork']) {
        if (!($comments_map = PMA_getComments($db, $table))) unset($comments_map);
    }

    // Check if we can use Relations (Mike Beck)
    if ($do_relation && !empty($cfgRelation['relation'])) {
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel = PMA_getForeigners($db, $table);

        if ($res_rel && count($res_rel) > 0) {
            $have_rel = TRUE;
        } else {
            $have_rel = FALSE;
        }
    }
    else {
           $have_rel = FALSE;
    } // end if

    if ($do_mime && $cfgRelation['mimework']) {
        if (!($mime_map = PMA_getMIME($db, $table, true))) unset($mime_map);
    }

    if (isset($comments_map) && count($comments_map) > 0) {
        $schema_create .= $crlf . '#' . $crlf . '# COMMENTS FOR TABLE ' . PMA_backquote($table, $use_backquotes) . ':' . $crlf;
        foreach($comments_map AS $comment_field => $comment) {
            $schema_create .= '#   ' . PMA_backquote($comment_field, $use_backquotes) . $crlf . '#       ' . PMA_backquote($comment, $use_backquotes) . $crlf;
        }
        $schema_create .= '#' . $crlf;
    }

    if (isset($mime_map) && count($mime_map) > 0) {
        $schema_create .= $crlf . '#' . $crlf . '# MIME TYPES FOR TABLE ' . PMA_backquote($table, $use_backquotes) . ':' . $crlf;
        @reset($mime_map);
        foreach($mime_map AS $mime_field => $mime) {
            $schema_create .= '#   ' . PMA_backquote($mime_field, $use_backquotes) . $crlf . '#       ' . PMA_backquote($mime['mimetype'], $use_backquotes) . $crlf;
        }
        $schema_create .= '#' . $crlf;
    }

    if ($have_rel) {
        $schema_create .= $crlf . '#' . $crlf . '# RELATIONS FOR TABLE ' . PMA_backquote($table, $use_backquotes) . ':' . $crlf;
        foreach($res_rel AS $rel_field => $rel) {
            $schema_create .= '#   ' . PMA_backquote($rel_field, $use_backquotes) . $crlf . '#       ' . PMA_backquote($rel['foreign_table'], $use_backquotes) . ' -> ' . PMA_backquote($rel['foreign_field'], $use_backquotes) . $crlf;
        }
        $schema_create .= '#' . $crlf;
    }

    return $schema_create;

} // end of the 'PMA_getTableComments()' function

/**
 * Outputs table's structure
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   string   the url to go back in case of error
 * @param   boolean  whether to include relation comments
 * @param   boolean  whether to include column comments
 * @param   boolean  whether to include mime comments
 *
 * @return  bool     Whether it suceeded
 *
 * @access  public
 */
function PMA_exportStructure($db, $table, $crlf, $error_url, $relation = FALSE, $comments = FALSE, $mime = FALSE, $dates = FALSE) {
    $formatted_table_name = (isset($GLOBALS['use_backquotes']))
                          ? PMA_backquote($table)
                          : '\'' . $table . '\'';
    $dump = $crlf
          . '# --------------------------------------------------------' . $crlf
          .  $crlf . '#' . $crlf
          .  '# ' . $GLOBALS['strTableStructure'] . ' ' . $formatted_table_name . $crlf
          .  '#' . $crlf
          .  PMA_getTableDef($db, $table, $crlf, $error_url, $dates) . ';' . $crlf
          .  PMA_getTableComments($db, $table, $crlf, $relation, $comments, $mime);


    return PMA_exportOutputHandler($dump);
}

/**
 * Dispatches between the versions of 'getTableContent' to use depending
 * on the php version
 *
 * @param   string      the database name
 * @param   string      the table name
 * @param   string      the end of line sequence
 * @param   string      the url to go back in case of error
 * @param   string      SQL query for obtaining data
 *
 * @return  bool        Whether it suceeded
 *
 * @global  boolean  whether to use backquotes to allow the use of special
 *                   characters in database, table and fields names or not
 * @global  integer  the number of records
 * @global  integer  the current record position
 *
 * @access  public
 *
 * @see     PMA_getTableContentFast(), PMA_getTableContentOld()
 *
 * @author  staybyte
 */
function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
{
    global $use_backquotes;
    global $rows_cnt;
    global $current_row;

    $formatted_table_name = (isset($GLOBALS['use_backquotes']))
                          ? PMA_backquote($table)
                          : '\'' . $table . '\'';
    $head = $crlf
          . '#' . $crlf
          . '# ' . $GLOBALS['strDumpingData'] . ' ' . $formatted_table_name . $crlf
          . '#' . $crlf .$crlf;

    if (!PMA_exportOutputHandler($head)) return FALSE;

    $buffer = '';

    $result      = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, '', $error_url);
    if ($result != FALSE) {
        $fields_cnt = mysql_num_fields($result);
        $rows_cnt   = mysql_num_rows($result);

        // get the real types of the table's fields (in an array)
        // the key of the array is the backquoted field name
        $field_types = PMA_fieldTypes($db,$table,$use_backquotes);

        // analyze the query to get the true column names, not the aliases
        // (this fixes an undefined index, also if Complete inserts
        //  are used, we did not get the true column name in case of aliases)
        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($sql_query));

        // Checks whether the field is an integer or not
        for ($j = 0; $j < $fields_cnt; $j++) {

            if (isset($analyzed_sql[0]['select_expr'][$j]['column'])) {
                $field_set[$j] = PMA_backquote($analyzed_sql[0]['select_expr'][$j]['column'], $use_backquotes);
            } else {
                $field_set[$j] = PMA_backquote(PMA_mysql_field_name($result, $j), $use_backquotes);
            }

            $type          = $field_types[$field_set[$j]];

            if ($type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' ||
                $type == 'bigint'  || (PMA_MYSQL_INT_VERSION < 40100 && $type == 'timestamp')) {
                $field_num[$j] = TRUE;
            } else {
                $field_num[$j] = FALSE;
            }
            // blob
            if ($type == 'blob' || $type == 'mediumblob' || $type == 'longblob' || $type == 'tinyblob') {
                $field_blob[$j] = TRUE;
            } else {
                $field_blob[$j] = FALSE;
            }
        } // end for

        if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] == 'update') {
            // update
            $schema_insert  = 'UPDATE ' . PMA_backquote($table, $use_backquotes) . ' SET ';
            $fields_no      = count($field_set);
        } else {
            // insert or replace
            if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] == 'replace') {
                $sql_command    = 'REPLACE';
            } else {
                $sql_command    = 'INSERT';
            }

            // delayed inserts?
            if (isset($GLOBALS['delayed'])) {
                $insert_delayed = ' DELAYED';
            } else {
                $insert_delayed = '';
            }

            // Sets the scheme
            if (isset($GLOBALS['showcolumns'])) {
                $fields        = implode(', ', $field_set);
                $schema_insert = $sql_command . $insert_delayed .' INTO ' . PMA_backquote($table, $use_backquotes)
                               . ' (' . $fields . ') VALUES (';
            } else {
                $schema_insert = $sql_command . $insert_delayed .' INTO ' . PMA_backquote($table, $use_backquotes)
                               . ' VALUES (';
            }
        }

        $search       = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
        $replace      = array('\0', '\n', '\r', '\Z');
        $current_row  = 0;

        while ($row = PMA_mysql_fetch_row($result)) {
            $current_row++;
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $values[]     = 'NULL';
                } else if ($row[$j] == '0' || $row[$j] != '') {
                    // a number
                    if ($field_num[$j]) {
                        $values[] = $row[$j];
                    // a not empty blob
                    } else if ($field_blob[$j] && !empty($row[$j])) {
                        $values[] = '0x' . bin2hex($row[$j]);
                    // a string
                    } else {
                        $values[] = "'" . str_replace($search, $replace, PMA_sqlAddslashes($row[$j])) . "'";
                    }
                } else {
                    $values[]     = "''";
                } // end if
            } // end for

            // should we make update?
            if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] == 'update') {

                $insert_line = $schema_insert;
                for ($i = 0; $i < $fields_no; $i++) {
                    if ($i > 0) {
                        $insert_line .= ', ';
                    }
                    $insert_line .= $field_set[$i] . ' = ' . $values[$i];
                }

            } else {

                // Extended inserts case
                if (isset($GLOBALS['extended_ins'])) {
                    if ($current_row == 1) {
                        $insert_line  = $schema_insert . implode(', ', $values) . ')';
                    } else {
                        $insert_line  = '(' . implode(', ', $values) . ')';
                    }
                }
                // Other inserts case
                else {
                    $insert_line      = $schema_insert . implode(', ', $values) . ')';
                }
            }
            unset($values);

            if (!PMA_exportOutputHandler($insert_line . ((isset($GLOBALS['extended_ins']) && ($current_row < $rows_cnt)) ? ',' : ';') . $crlf)) return FALSE;

        } // end while
    } // end if ($result != FALSE)
    mysql_free_result($result);

    return TRUE;
} // end of the 'PMA_exportData()' function
?>
