<?php
/* $Id$ */


/**
 * Set of functions used to build dumps of tables
 */



if (!defined('PMA_BUILD_DUMP_LIB_INCLUDED')){
    define('PMA_BUILD_DUMP_LIB_INCLUDED', 1);

    /**
     * Uses the 'htmlspecialchars()' php function on databases, tables and fields
     * name if the dump has to be displayed on screen.
     *
     * @param   string   the string to format
     *
     * @return  string   the formatted string
     *
     * @access  private
     */
    function PMA_htmlFormat($a_string = '')
    {
        return (empty($GLOBALS['asfile']) ? htmlspecialchars($a_string) : $a_string);
    } // end of the 'PMA_htmlFormat()' function


    /**
     * Returns $table's CREATE definition
     *
     * Uses the 'PMA_htmlFormat()' function defined in 'tbl_dump.php3'
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   string   the end of line sequence
     * @param   string   the url to go back in case of error
     *
     * @return  string   the CREATE statement on success
     *
     * @global  boolean  whether to add 'drop' statements or not
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     *
     * @see     PMA_htmlFormat()
     *
     * @access  public
     */
    function PMA_getTableDef($db, $table, $crlf, $error_url)
    {
        global $drop;
        global $use_backquotes;

        $schema_create = '';
        if (!empty($drop)) {
            $schema_create .= 'DROP TABLE IF EXISTS ' . PMA_backquote(PMA_htmlFormat($table), $use_backquotes) . ';' . $crlf;
        }

        // Steve Alberty's patch for complete table dump,
        // modified by Lem9 to allow older MySQL versions to continue to work
        if (PMA_MYSQL_INT_VERSION >= 32321) {
            // Whether to quote table and fields names or not
            if ($use_backquotes) {
                mysql_query('SET SQL_QUOTE_SHOW_CREATE = 1');
            } else {
                mysql_query('SET SQL_QUOTE_SHOW_CREATE = 0');
            }
            $result = mysql_query('SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table));
            if ($result != FALSE && mysql_num_rows($result) > 0) {
                $tmpres        = mysql_fetch_array($result);
                $schema_create .= str_replace("\n", $crlf, PMA_htmlFormat($tmpres[1]));
            }
            mysql_free_result($result);
            return $schema_create;
        } // end if MySQL >= 3.23.20

        // For MySQL < 3.23.20
        $schema_create .= 'CREATE TABLE ' . PMA_htmlFormat(PMA_backquote($table), $use_backquotes) . ' (' . $crlf;

        $local_query   = 'SHOW FIELDS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
        $result        = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        while ($row = mysql_fetch_array($result)) {
            $schema_create     .= '   ' . PMA_htmlFormat(PMA_backquote($row['Field'], $use_backquotes)) . ' ' . $row['Type'];
            if (isset($row['Default']) && $row['Default'] != '') {
                $schema_create .= ' DEFAULT \'' . PMA_htmlFormat(PMA_sqlAddslashes($row['Default'])) . '\'';
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

        $local_query = 'SHOW KEYS FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table);
        $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
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
                $index[$kname][] = PMA_htmlFormat(PMA_backquote($row['Column_name'], $use_backquotes)) . '(' . $sub_part . ')';
            } else {
                $index[$kname][] = PMA_htmlFormat(PMA_backquote($row['Column_name'], $use_backquotes));
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
    } // end of the 'PMA_getTableDef()' function


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
     * @param   string   the url to go back in case of error
     *
     * @return  boolean  always true
     *
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     * @global  integer  the number of records
     * @global  integer  the current record position
     *
     * @access  private
     *
     * @see     PMA_getTableContent()
     *
     * @author  staybyte
     */
    function PMA_getTableContentFast($db, $table, $add_query = '', $handler, $error_url)
    {
        global $use_backquotes;
        global $rows_cnt;
        global $current_row;

        $local_query = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        if ($result != FALSE) {
            $fields_cnt = mysql_num_fields($result);
            $rows_cnt   = mysql_num_rows($result);

            // Checks whether the field is an integer or not
            for ($j = 0; $j < $fields_cnt; $j++) {
                $field_set[$j] = PMA_backquote(mysql_field_name($result, $j), $use_backquotes);
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
                $schema_insert = 'INSERT INTO ' . PMA_backquote(PMA_htmlFormat($table), $use_backquotes)
                               . ' (' . PMA_htmlFormat($fields) . ') VALUES (';
            } else {
                $schema_insert = 'INSERT INTO ' . PMA_backquote(PMA_htmlFormat($table), $use_backquotes)
                               . ' VALUES (';
            }
        
            $search       = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
            $replace      = array('\0', '\n', '\r', '\Z');
            $current_row  = 0;

            @set_time_limit($GLOBALS['cfgExecTimeLimit']);

            while ($row = mysql_fetch_row($result)) {
            	$current_row++;
                for ($j = 0; $j < $fields_cnt; $j++) {
                    if (!isset($row[$j])) {
                        $values[]     = 'NULL';
                    } else if ($row[$j] == '0' || $row[$j] != '') {
                        // a number
                        if ($field_num[$j]) {
                            $values[] = $row[$j];
                        }
                        // a string
                        else {
                            $values[] = "'" . str_replace($search, $replace, PMA_sqlAddslashes($row[$j])) . "'";
                        }
                    } else {
                        $values[]     = "''";
                    } // end if
                } // end for

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
                unset($values);

                // Call the handler
                $handler($insert_line);

                // loic1: send a fake header to bypass browser timeout if data
                //        are bufferized
                if (!empty($GLOBALS['ob_mode'])
                    || (isset($GLOBALS['zip']) || isset($GLOBALS['bzip']) || isset($GLOBALS['gzip']))) {
                    header('Expires: 0');
                }
            } // end while
        } // end if ($result != FALSE)
        mysql_free_result($result);
    
        return TRUE;
    } // end of the 'PMA_getTableContentFast()' function


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
     * @param   string   the url to go back in case of error
     *
     * @return  boolean  always true
     *
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     * @global  integer  the number of records
     * @global  integer  the current record position
     *
     * @access  private
     *
     * @see     PMA_getTableContent()
     */
    function PMA_getTableContentOld($db, $table, $add_query = '', $handler, $error_url)
    {
        global $use_backquotes;
        global $rows_cnt;
        global $current_row;

        $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        $result       = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        $current_row  = 0;
        $fields_cnt   = mysql_num_fields($result);
        $rows_cnt     = mysql_num_rows($result);

        @set_time_limit($GLOBALS['cfgExecTimeLimit']); // HaRa

        while ($row = mysql_fetch_row($result)) {
            $current_row++;
            $table_list     = '(';
            for ($j = 0; $j < $fields_cnt; $j++) {
                $table_list .= PMA_backquote(mysql_field_name($result, $j), $use_backquotes) . ', ';
            }
            $table_list     = substr($table_list, 0, -2);
            $table_list     .= ')';

            if (isset($GLOBALS['extended_ins']) && $current_row > 1) {
                $schema_insert = '(';
            } else {
                if (isset($GLOBALS['showcolumns'])) {
                    $schema_insert = 'INSERT INTO ' . PMA_backquote(PMA_htmlFormat($table), $use_backquotes)
                                   . ' ' . PMA_htmlFormat($table_list) . ' VALUES (';
                } else {
                    $schema_insert = 'INSERT INTO ' . PMA_backquote(PMA_htmlFormat($table), $use_backquotes)
                                   . ' VALUES (';
                }
                $is_first_row      = FALSE;
            }

            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= ' NULL, ';
                } else if ($row[$j] == '0' || $row[$j] != '') {
                    $type          = mysql_field_type($result, $j);
                    // a number
                    if ($type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' ||
                        $type == 'bigint'  ||$type == 'timestamp') {
                        $schema_insert .= $row[$j] . ', ';
                    }
                    // a string
                    else {
                        $dummy  = '';
                        $srcstr = $row[$j];
                        for ($xx = 0; $xx < strlen($srcstr); $xx++) {
                            $yy = strlen($dummy);
                            if ($srcstr[$xx] == '\\')   $dummy .= '\\\\';
                            if ($srcstr[$xx] == '\'')   $dummy .= '\\\'';
//                            if ($srcstr[$xx] == '"')    $dummy .= '\\"';
                            if ($srcstr[$xx] == "\x00") $dummy .= '\0';
                            if ($srcstr[$xx] == "\x0a") $dummy .= '\n';
                            if ($srcstr[$xx] == "\x0d") $dummy .= '\r';
//                            if ($srcstr[$xx] == "\x08") $dummy .= '\b';
//                            if ($srcstr[$xx] == "\t")   $dummy .= '\t';
                            if ($srcstr[$xx] == "\x1a") $dummy .= '\Z';
                            if (strlen($dummy) == $yy)  $dummy .= $srcstr[$xx];
                        }
                        $schema_insert .= "'" . $dummy . "', ";
                    }
                } else {
                    $schema_insert .= "'', ";
                } // end if
            } // end for
            $schema_insert = ereg_replace(', $', '', $schema_insert);
            $schema_insert .= ')';
            $handler(trim($schema_insert));

            // loic1: send a fake header to bypass browser timeout if data are
            //        bufferized
            if (!empty($GLOBALS['ob_mode'])
                && (isset($GLOBALS['zip']) || isset($GLOBALS['bzip']) || isset($GLOBALS['gzip']))) {
                header('Expires: 0');
            }
        } // end while
        mysql_free_result($result);

        return TRUE;
    } // end of the 'PMA_getTableContentOld()' function


    /**
     * Dispatches between the versions of 'getTableContent' to use depending
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
     * @param   string   the url to go back in case of error
     *
     * @access  public
     *
     * @see     PMA_getTableContentFast(), PMA_getTableContentOld()
     *
     * @author  staybyte
     */
    function PMA_getTableContent($db, $table, $limit_from = 0, $limit_to = 0, $handler, $error_url)
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
        if (PMA_PHP_INT_VERSION >= 40005) {
            PMA_getTableContentFast($db, $table, $add_query, $handler, $error_url);
        } else {
            PMA_getTableContentOld($db, $table, $add_query, $handler, $error_url);
        }
    } // end of the 'PMA_getTableContent()' function


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
     * @param   string   the url to go back in case of error
     *
     * @global  string   whether to obtain an excel compatible csv format or a
     *                   simple csv one
     *
     * @return  boolean always true
     *
     * @access  public
     */
    function PMA_getTableCsv($db, $table, $limit_from = 0, $limit_to = 0, $sep, $enc_by, $esc_by, $handler, $error_url)
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
        $local_query = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        $fields_cnt  = mysql_num_fields($result);

        @set_time_limit($GLOBALS['cfgExecTimeLimit']);

        // Format the data
        $i = 0;
        while ($row = mysql_fetch_row($result)) {
            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= 'NULL';
                }
                else if ($row[$j] == '0' || $row[$j] != '') {
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

            // loic1: send a fake header to bypass browser timeout if data are
            //        bufferized
            if (!empty($GLOBALS['ob_mode'])
                && (isset($GLOBALS['zip']) || isset($GLOBALS['bzip']) || isset($GLOBALS['gzip']))) {
                header('Expires: 0');
            }
        } // end while
        mysql_free_result($result);

        return TRUE;
    } // end of the 'PMA_getTableCsv()' function

} // $__PMA_BUILD_DUMP_LIB__
?>
