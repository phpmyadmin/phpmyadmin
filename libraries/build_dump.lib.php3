<?php
/* $Id$ */


/**
 * Set of functions used to build dumps of tables
 */



if (!defined('__LIB_BUILD_DUMP__')){
    define('__LIB_BUILD_DUMP__', 1);

    /**
     * Uses the 'htmlspecialchars()' php function on databases, tables and fields
     * name if the dump has to be displayed on screen.
     *
     * @param   string   the string to format
     *
     * @return  string   the formatted string
     *
     * @access	private
     */
    function html_format($a_string = '')
    {
        return (empty($GLOBALS['asfile']) ? htmlspecialchars($a_string) : $a_string);
    } // end of the 'html_format()' function


    /**
     * Returns $table's CREATE definition
     *
     * Uses the 'html_format()' function defined in 'tbl_dump.php3'
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   string   the end of line sequence
     *
     * @return  string   the CREATE statement on success
     *
     * @global  boolean  whether to add 'drop' statements or not
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     *
     * @see	    html_format()
     *
     * @access	public
     */
    function get_table_def($db, $table, $crlf)
    {
        global $drop;
        global $use_backquotes;

        $schema_create = '';
        if (!empty($drop)) {
            $schema_create .= 'DROP TABLE IF EXISTS ' . backquote(html_format($table), $use_backquotes) . ';' . $crlf;
        }

        // Steve Alberty's patch for complete table dump,
        // modified by Lem9 to allow older MySQL versions to continue to work
        if (MYSQL_INT_VERSION >= 32321) {
            // Whether to quote table and fields names or not
            if ($use_backquotes) {
                mysql_query('SET SQL_QUOTE_SHOW_CREATE = 1');
            } else {
                mysql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
            }
            $result = mysql_query('SHOW CREATE TABLE ' . backquote($db) . '.' . backquote($table));
            if ($result != FALSE && mysql_num_rows($result) > 0) {
                $tmpres        = mysql_fetch_array($result);
                $schema_create .= str_replace("\n", $crlf, html_format($tmpres[1]));
            }
            mysql_free_result($result);
            return $schema_create;
        } // end if MySQL >= 3.23.20

        // For MySQL < 3.23.20
        $schema_create .= 'CREATE TABLE ' . html_format(backquote($table), $use_backquotes) . ' (' . $crlf;

        $local_query   = 'SHOW FIELDS FROM ' . backquote($db) . '.' . backquote($table);
        $result        = mysql_query($local_query) or mysql_die('', $local_query);
        while ($row = mysql_fetch_array($result)) {
            $schema_create     .= '   ' . html_format(backquote($row['Field'], $use_backquotes)) . ' ' . $row['Type'];
            if (isset($row['Default']) && $row['Default'] != '') {
                $schema_create .= ' DEFAULT \'' . html_format(sql_addslashes($row['Default'])) . '\'';
            }
            if ($row['Null'] != 'YES') {
                $schema_create .= ' NOT NULL';
            }
            if ($row['Extra'] != '') {
                $schema_create .= ' ' . $row['Extra'];
            }
            $schema_create     .= ',' . $crlf;
        } // end while
        mysql_free_result($result);
        $schema_create         = ereg_replace(',' . $crlf . '$', '', $schema_create);

        $local_query = 'SHOW KEYS FROM ' . backquote($db) . '.' . backquote($table);
        $result      = mysql_query($local_query) or mysql_die('', $local_query);
        while ($row = mysql_fetch_array($result))
        {
            $kname    = $row['Key_name'];
            $comment  = (isset($row['Comment'])) ? $row['Comment'] : '';
            $sub_part = (isset($row['Sub_part'])) ? $row['Sub_part'] : '';

            if ($kname != 'PRIMARY' && $row['Non_unique'] == 0) {
                $kname = "UNIQUE|$kname";
            }
            if ($comment == 'FULLTEXT') {
                $kname = 'FULLTEXT|$kname';
            }
            if (!isset($index[$kname])) {
                $index[$kname] = array();
            }
            if ($sub_part > 1) {
                $index[$kname][] = html_format(backquote($row['Column_name'], $use_backquotes)) . '(' . $sub_part . ')';
            } else {
                $index[$kname][] = html_format(backquote($row['Column_name'], $use_backquotes));
            }
        } // end while
        mysql_free_result($result);

        while (list($x, $columns) = @each($index)) {
            $schema_create     .= ',' . $crlf;
            if ($x == 'PRIMARY') {
                $schema_create .= '   PRIMARY KEY (';
            } else if (substr($x, 0, 6) == 'UNIQUE') {
                $schema_create .= '   UNIQUE ' . substr($x, 7) . ' (';
            } else if (substr($x, 0, 8) == 'FULLTEXT') {
                $schema_create .= '   FULLTEXT ' . substr($x, 9) . ' (';
            } else {
                $schema_create .= '   KEY ' . $x . ' (';
            }
            $schema_create     .= implode($columns, ', ') . ')';
        } // end while

        $schema_create .= $crlf . ')';

        return $schema_create;
    } // end of the 'get_table_def()' function


    /**
     * php >= 4.0.5 only : get the content of $table as a series of INSERT
     * statements.
     * After every row, a custom callback function $handler gets called.
     *
     * Last revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   string   the 'limit' clause to use with the sql query
     * @param   string   the name of the handler (function) to use at the end
     *                   of every row. This handler must accept one parameter
     *                   ($sql_insert)
     *
     * @return  boolean  always true
     *
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     *
     * @access	private
     *
     * @see     get_table_content()
     *
     * @author  staybyte
     */
    function get_table_content_fast($db, $table, $add_query = '', $handler)
    {
        global $use_backquotes;

        $local_query = 'SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query;
        $result      = mysql_query($local_query) or mysql_die('', $local_query);
        if ($result != FALSE) {
            $fields_cnt = mysql_num_fields($result);

            // Checks whether the field is an integer or not
            for ($j = 0; $j < $fields_cnt; $j++) {
                $field_set[$j] = backquote(mysql_field_name($result, $j), $use_backquotes);
                $type          = mysql_field_type($result, $j);
                if ($type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' ||
                    $type == 'bigint'  ||$type == 'timestamp') {
                    $field_num[$j] = TRUE;
                } else {
                    $field_num[$j] = FALSE;
                }
            } // end for

            // Sets the scheme
            if (isset($GLOBALS['showcolumns'])) {
                $fields        = implode(', ', $field_set);
                $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                               . ' (' . html_format($fields) . ') VALUES (';
            } else {
                $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                               . ' VALUES (';
            }
        
            $search     = array("\x0a","\x0d","\x1a"); //\x08\\x09, not required
            $replace    = array("\\n","\\r","\Z");
            $isFirstRow = TRUE;

            @set_time_limit(1200); // 20 Minutes

            while ($row = mysql_fetch_row($result)) {
                for ($j = 0; $j < $fields_cnt; $j++) {
                    if (!isset($row[$j])) {
                        $values[]     = 'NULL';
                    } else if (!empty($row[$j])) {
                        // a number
                        if ($field_num[$j]) {
                            $values[] = $row[$j];
                        }
                        // a string
                        else {
                            $values[] = "'" . str_replace($search, $replace, sql_addslashes($row[$j])) . "'";
                        }
                    } else {
                        $values[]     = "''";
                    } // end if
                } // end for

                // Extended inserts case
                if (isset($GLOBALS['extended_ins'])) {
                    if ($isFirstRow) {
                        $insert_line = $schema_insert . implode(',', $values) . ')';
                        $isFirstRow  = FALSE;
                    } else {
                        $insert_line = '(' . implode(',', $values) . ')';
                    }
                }
                // Other inserts case
                else { 
                   $insert_line = $schema_insert . implode(',', $values) . ')';
                }
                unset($values);

                // Call the handler
                $handler($insert_line);
            } // end while
            
            // Replace last comma by a semi-column in extended inserts case
            if (isset($GLOBALS['extended_ins'])) {
              $GLOBALS['tmp_buffer'] = ereg_replace(',([^,]*)$', ';\\1', $GLOBALS['tmp_buffer']);
            }
        } // end if ($result != FALSE)
        mysql_free_result($result);
    
        return TRUE;
    } // end of the 'get_table_content_fast()' function


    /**
     * php < 4.0.5 only: get the content of $table as a series of INSERT
     * statements.
     * After every row, a custom callback function $handler gets called.
     *
     * Last revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   string   the 'limit' clause to use with the sql query
     * @param   string   the name of the handler (function) to use at the end
     *                   of every row. This handler must accept one parameter
     *                   ($sql_insert)
     *
     * @return  boolean  always true
     *
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     *
     * @access	private
     *
     * @see     get_table_content()
     */
    function get_table_content_old($db, $table, $add_query = '', $handler)
    {
        global $use_backquotes;

        $local_query = 'SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query;
        $result      = mysql_query($local_query) or mysql_die('', $local_query);
        $i           = 0;
        $isFirstRow  = TRUE;
        $fields_cnt  = mysql_num_fields($result);

        while ($row = mysql_fetch_row($result)) {
            @set_time_limit(60); // HaRa
            $table_list     = '(';

            for ($j = 0; $j < $fields_cnt; $j++) {
                $table_list .= backquote(mysql_field_name($result, $j), $use_backquotes) . ', ';
            }

            $table_list     = substr($table_list, 0, -2);
            $table_list     .= ')';

            if (isset($GLOBALS['extended_ins']) && !$isFirstRow) {
                $schema_insert = '(';
            } else {
                if (isset($GLOBALS['showcolumns'])) {
                    $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                                   . ' ' . html_format($table_list) . ' VALUES (';
                } else {
                    $schema_insert = 'INSERT INTO ' . backquote(html_format($table), $use_backquotes)
                                   . ' VALUES (';
                }
                $isFirstRow        = FALSE;
            }

            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= ' NULL,';
                } else if ($row[$j] != '') {
                    $dummy  = '';
                    $srcstr = $row[$j];
                    for ($xx = 0; $xx < strlen($srcstr); $xx++) {
                        $yy = strlen($dummy);
                        if ($srcstr[$xx] == "\\")   $dummy .= "\\\\";
                        if ($srcstr[$xx] == "'")    $dummy .= "\\'";
                        if ($srcstr[$xx] == "\"")   $dummy .= "\\\"";
                        if ($srcstr[$xx] == "\x00") $dummy .= "\\0";
                        if ($srcstr[$xx] == "\x0a") $dummy .= "\\n";
                        if ($srcstr[$xx] == "\x0d") $dummy .= "\\r";
                        if ($srcstr[$xx] == "\x08") $dummy .= "\\b";
                        if ($srcstr[$xx] == "\t")   $dummy .= "\\t";
                        if ($srcstr[$xx] == "\x1a") $dummy .= "\\Z";
                        if (strlen($dummy) == $yy)  $dummy .= $srcstr[$xx];
                    }
                    $schema_insert .= " '" . $dummy . "',";
                } else {
                    $schema_insert .= " '',";
                } // end if
            } // end for
            $schema_insert = ereg_replace(',$', '', $schema_insert);
            $schema_insert .= ')';
            $handler(trim($schema_insert));
            ++$i;
        } // end while
        mysql_free_result($result);

        // Replace last comma by a semi-column in extended inserts case
        if ($i > 0 && isset($GLOBALS['extended_ins'])) {
            $GLOBALS['tmp_buffer'] = ereg_replace(',([^,]*)$', ';\\1', $GLOBALS['tmp_buffer']);
        }

        return TRUE;
    } // end of the 'get_table_content_old()' function


    /**
     * Dispatches between the versions of 'get_table_content' to use depending
     * on the php version
     *
     * Last revision 13 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the current database name
     * @param   string   the current table name
     * @param   integer  the offset on this table
     * @param   integer  the last row to get
     * @param   string   the name of the handler (function) to use at the end
     *                   of every row. This handler must accept one parameter
     *                   ($sql_insert)
     *
     * @access	public
     *
     * @see     get_table_content_fast(), get_table_content_old()
     *
     * @author  staybyte
     */
    function get_table_content($db, $table, $limit_from = 0, $limit_to = 0, $handler)
    {
        // Defines the offsets to use
        if ($limit_from > 0) {
            $limit_from--;
        } else {
            $limit_from = 0;
        }
        if ($limit_to > 0 && $limit_from >= 0) {
            $add_query  = " LIMIT $limit_from, $limit_to";
        } else {
            $add_query  = '';
        }

        // Call the working function depending on the php version
        if (PHP_INT_VERSION >= 40005) {
            get_table_content_fast($db, $table, $add_query, $handler);
        } else {
            get_table_content_old($db, $table, $add_query, $handler);
        }
    } // end of the 'get_table_content()' function


    /**
     * Outputs the content of a table in CSV format
     *
     * Last revision 14 July 2001: Patch for limiting dump size from
     * vinay@sanisoft.com & girish@sanisoft.com
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   integer  the offset on this table
     * @param   integer  the last row to get
     * @param   string   the field separator character
     * @param   string   the optionnal "enclosed by" character
     * @param   string   the handler (function) to call. It must accept one
     *                   parameter ($sql_insert)
     *
     * @global  string   whether to obtain an excel compatible csv format or a
     *                   simple csv one
     *
     * @return  boolean always true
     *
     * @access	public
     */
    function get_table_csv($db, $table, $limit_from = 0, $limit_to = 0, $sep, $enc_by, $esc_by, $handler)
    {
        global $what;

        // Handles the "separator" and the optionnal "enclosed by" characters
        if ($what == 'excel') {
            $sep     = ',';
        } else if (!isset($sep)) {
            $sep     = '';
        } else {
            if (get_magic_quotes_gpc()) {
                $sep = stripslashes($sep);
            }
            $sep     = str_replace('\\t', "\011", $sep);
        }
        if ($what == 'excel') {
            $enc_by  = '"';
        } else if (!isset($enc_by)) {
            $enc_by  = '';
        } else if (get_magic_quotes_gpc()) {
            $enc_by  = stripslashes($enc_by);
        }
        if ($what == 'excel'
            || (empty($esc_by) && $enc_by != '')) {
            // double the "enclosed by" character
            $esc_by  = $enc_by;
        } else if (!isset($esc_by)) {
            $esc_by  = '';
        } else if (get_magic_quotes_gpc()) {
            $esc_by  = stripslashes($esc_by);
        }

        // Defines the offsets to use
        if ($limit_from > 0) {
            $limit_from--;
        } else {
            $limit_from = 0;
        }
        if ($limit_to > 0 && $limit_from >= 0) {
            $add_query  = " LIMIT $limit_from, $limit_to";
        } else {
            $add_query  = '';
        }

        // Gets the data from the database
        $local_query = 'SELECT * FROM ' . backquote($db) . '.' . backquote($table) . $add_query;
        $result      = mysql_query($local_query) or mysql_die('', $local_query);
        $fields_cnt  = mysql_num_fields($result);

        // Format the data
        $i      = 0;
        while ($row = mysql_fetch_row($result)) {
            @set_time_limit(60);
            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= 'NULL';
                }
                else if ($row[$j] != '') {
                    // loic1 : always enclose fields
                    if ($what == 'excel') {
                        $row[$j]   = ereg_replace("\015(\012)?", "\012", $row[$j]);
                    }
                    $schema_insert .= $enc_by
                                   . str_replace($enc_by, $esc_by . $enc_by, $row[$j])
                                   . $enc_by;
                }
                else {
                    $schema_insert .= '';
                }
                if ($j < $fields_cnt-1) {
                    $schema_insert .= $sep;
                }
            } // end for
            $handler(trim($schema_insert));
            ++$i;
        } // end while
        mysql_free_result($result);

        return TRUE;
    } // end of the 'get_table_csv()' function

} // $__LIB_BUILD_DUMP__
?>
