<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Set of functions used to build dumps of tables
 */



if (!defined('PMA_BUILD_DUMP_LIB_INCLUDED')){
    define('PMA_BUILD_DUMP_LIB_INCLUDED', 1);

    /**
     * Returns $table's CREATE definition
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   string   the end of line sequence
     * @param   string   the url to go back in case of error
     * @param   boolean  whether to include column comments
     *
     * @return  string   the CREATE statement on success
     *
     * @global  boolean  whether to add 'drop' statements or not
     * @global  boolean  whether to use backquotes to allow the use of special
     *                   characters in database, table and fields names or not
     *
     * @access  public
     */
    function PMA_getTableDef($db, $table, $crlf, $error_url, $comments = false)
    {
        global $drop;
        global $use_backquotes;

        $schema_create = '';
        $auto_increment = '';
        $new_crlf = $crlf;

        if (PMA_MYSQL_INT_VERSION >= 32321) {
            $result = PMA_mysql_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . PMA_sqlAddslashes($table) . '\'');
            if ($result != FALSE && mysql_num_rows($result) > 0) {
                $tmpres        = PMA_mysql_fetch_array($result);
                if (!empty($tmpres['Auto_increment'])) {
                    $auto_increment .= ' AUTO_INCREMENT=' . $tmpres['Auto_increment'] . ' ';
                }
                
                if (isset($tmpres['Create_time']) && !empty($tmpres['Create_time'])) {
                    $schema_create .= '# ' . $GLOBALS['strStatCreateTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Create_time'])) . $crlf;
                    $new_crlf = '#' . $crlf . $crlf;
                }

                if (isset($tmpres['Update_time']) && !empty($tmpres['Update_time'])) {
                    $schema_create .= '# ' . $GLOBALS['strStatUpdateTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Update_time'])) . $crlf;
                    $new_crlf = '#' . $crlf . $crlf;
                }

                if (isset($tmpres['Check_time']) && !empty($tmpres['Check_time'])) {
                    $schema_create .= '# ' . $GLOBALS['strStatCheckTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Check_time'])) . $crlf;
                    $new_crlf = '#' . $crlf . $crlf;
                }
            }
            mysql_free_result($result);
        }

        $schema_create .= $new_crlf;
        
        if (!empty($drop)) {
            $schema_create .= 'DROP TABLE IF EXISTS ' . PMA_backquote($table, $use_backquotes) . ';' . $crlf;
        }

        if ($comments) {
            $comments_map = PMA_getComments($db, $table);
        } else {
            $comments_map = array();
        }

        // Steve Alberty's patch for complete table dump,
        // modified by Lem9 to allow older MySQL versions to continue to work
        if (PMA_MYSQL_INT_VERSION >= 32321) {
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
                $schema_create .= str_replace("\n", $crlf, $tmpres[1]);
            }
            
            $schema_create .= $auto_increment;

            // garvin: Because replacing within a direct mysql result is a bit dangerous, just insert comments after that.
            if ($comments && is_array($comments_map) && count($comments_map) > 0) {
                $schema_create .= $crlf . $crlf . '/* COMMENTS FOR TABLE ' . PMA_backquote($table, $use_backquotes) . ':' . $crlf;
                @reset($comments_map);
                while(list($comment_field, $comment) = each($comments_map)) {
                    $schema_create .= '    ' . PMA_backquote($comment_field, $use_backquotes) . $crlf . '        ' . PMA_backquote($comment, $use_backquotes) . $crlf;
                    // omitting html_format is intentional. No use for htmlchars in the dump.
                }
                $schema_create .= '*/';
            }

            mysql_free_result($result);
            return $schema_create;
        } // end if MySQL >= 3.23.21

        // For MySQL < 3.23.20
        $schema_create .= 'CREATE TABLE ' . PMA_backquote($table, $use_backquotes) . ' (' . $crlf;

        $local_query   = 'SHOW FIELDS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
        $result        = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        while ($row = PMA_mysql_fetch_array($result)) {
            $schema_create     .= '   ' . PMA_backquote($row['Field'], $use_backquotes) . ' ' . $row['Type'];
            if (isset($row['Default']) && $row['Default'] != '') {
                $schema_create .= ' DEFAULT \'' . PMA_sqlAddslashes($row['Default']) . '\'';
            }
            if ($row['Null'] != 'YES') {
                $schema_create .= ' NOT NULL';
            }
            if ($row['Extra'] != '') {
                $schema_create .= ' ' . $row['Extra'];
            }

            if ($comments && is_array($comments_map) && isset($comments_map[$row['Field']])) {
                $schema_create .= $crlf . '    /* ' . PMA_backquote($comments_map[$row['Field']], $use_backquotes) . ' */';
                // omitting html_format is intentional. No use for htmlchars in the dump.
            }

            $schema_create     .= ',' . $crlf;
        } // end while
        mysql_free_result($result);
        $schema_create         = ereg_replace(',' . $crlf . '$', '', $schema_create);

        $local_query = 'SHOW KEYS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
        $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        while ($row = PMA_mysql_fetch_array($result))
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
                $index[$kname][] = PMA_backquote($row['Column_name'], $use_backquotes) . '(' . $sub_part . ')';
            } else {
                $index[$kname][] = PMA_backquote($row['Column_name'], $use_backquotes);
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
     * @param   string   the sql_query (optional)
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
    function PMA_getTableContentFast($db, $table, $add_query = '', $handler, $error_url, $sql_query)
    {
        global $use_backquotes;
        global $rows_cnt;
        global $current_row;

        if (!empty($sql_query)) {
            $local_query = $sql_query . $add_query;
            PMA_mysql_select_db($db);
        } else {
            $local_query = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        }
        $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        if ($result != FALSE) {
            $fields_cnt = mysql_num_fields($result);
            $rows_cnt   = mysql_num_rows($result);

            // Checks whether the field is an integer or not
            for ($j = 0; $j < $fields_cnt; $j++) {
                $field_set[$j] = PMA_backquote(PMA_mysql_field_name($result, $j), $use_backquotes);
                $type          = PMA_mysql_field_type($result, $j);
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
                $schema_insert = 'INSERT INTO ' . PMA_backquote($table, $use_backquotes)
                               . ' (' . $fields . ') VALUES (';
            } else {
                $schema_insert = 'INSERT INTO ' . PMA_backquote($table, $use_backquotes)
                               . ' VALUES (';
            }

            $search       = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
            $replace      = array('\0', '\n', '\r', '\Z');
            $current_row  = 0;

            @set_time_limit($GLOBALS['cfg']['ExecTimeLimit']);

            // loic1: send a fake header to bypass browser timeout if data
            //        are bufferized - part 1
            if (!empty($GLOBALS['ob_mode'])
                || (isset($GLOBALS['zip']) || isset($GLOBALS['bzip']) || isset($GLOBALS['gzip']))) {
                $time0    = time();
            }

            while ($row = PMA_mysql_fetch_row($result)) {
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
                //        are bufferized - part 2
                if (isset($time0)) {
                    $time1 = time();
                    if ($time1 >= $time0 + 30) {
                        $time0 = $time1;
                        header('X-pmaPing: Pong');
                    }
                } // end if
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
     * @param   string   the sql query (optional)
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
    function PMA_getTableContentOld($db, $table, $add_query = '', $handler, $error_url, $sql_query)
    {
        global $use_backquotes;
        global $rows_cnt;
        global $current_row;

        if (!empty($sql_query)) {
            $local_query = $sql_query . $add_query;
            PMA_mysql_select_db($db);
        } else {
            $local_query  = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        }
        $result       = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        $current_row  = 0;
        $fields_cnt   = mysql_num_fields($result);
        $rows_cnt     = mysql_num_rows($result);

        @set_time_limit($GLOBALS['cfg']['ExecTimeLimit']); // HaRa

        // loic1: send a fake header to bypass browser timeout if data
        //        are bufferized - part 1
        if (!empty($GLOBALS['ob_mode'])
            || (isset($GLOBALS['zip']) || isset($GLOBALS['bzip']) || isset($GLOBALS['gzip']))) {
            $time0    = time();
        }

        while ($row = PMA_mysql_fetch_row($result)) {
            $current_row++;
            $table_list     = '(';
            for ($j = 0; $j < $fields_cnt; $j++) {
                $table_list .= PMA_backquote(PMA_mysql_field_name($result, $j), $use_backquotes) . ', ';
            }
            $table_list     = substr($table_list, 0, -2);
            $table_list     .= ')';

            if (isset($GLOBALS['extended_ins']) && $current_row > 1) {
                $schema_insert = '(';
            } else {
                if (isset($GLOBALS['showcolumns'])) {
                    $schema_insert = 'INSERT INTO ' . PMA_backquote($table, $use_backquotes)
                                   . ' ' . $table_list . ' VALUES (';
                } else {
                    $schema_insert = 'INSERT INTO ' . PMA_backquote($table, $use_backquotes)
                                   . ' VALUES (';
                }
                $is_first_row      = FALSE;
            }

            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= ' NULL, ';
                } else if ($row[$j] == '0' || $row[$j] != '') {
                    $type          = PMA_mysql_field_type($result, $j);
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

            // loic1: send a fake header to bypass browser timeout if data
            //        are bufferized - part 2
            if (isset($time0)) {
                $time1 = time();
                if ($time1 >= $time0 + 30) {
                    $time0 = $time1;
                    header('X-pmaPing: Pong');
                }
            } // end if
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
     * @param   string   the sql_query (optional)
     *
     * @access  public
     *
     * @see     PMA_getTableContentFast(), PMA_getTableContentOld()
     *
     * @author  staybyte
     */
    function PMA_getTableContent($db, $table, $limit_from = 0, $limit_to = 0, $handler, $error_url, $sql_query)
    {
        // Defines the offsets to use
        if ($limit_to > 0 && $limit_from >= 0) {
            $add_query  = ' LIMIT '
                        . (($limit_from > 0) ? $limit_from . ', ' : '')
                        . $limit_to;
        } else {
            $add_query  = '';
        }

        // Call the working function depending on the php version
        if (PMA_PHP_INT_VERSION >= 40005) {
            PMA_getTableContentFast($db, $table, $add_query, $handler, $error_url, $sql_query);
        } else {
            PMA_getTableContentOld($db, $table, $add_query, $handler, $error_url, $sql_query);
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
     * @param   string   sql query (optional)
     *
     * @global  string   whether to obtain an excel compatible csv format or a
     *                   simple csv one
     *
     * @return  boolean always true
     *
     * @access  public
     */
    function PMA_getTableCsv($db, $table, $limit_from = 0, $limit_to = 0, $sep, $enc_by, $esc_by, $handler, $error_url, $sql_query)
    {
        global $what;

        // Handles the "separator" and the optionnal "enclosed by" characters
        if ($what == 'excel') {
            $sep     = ',';
        } else if (!isset($sep)) {
            $sep     = '';
        } else {
            $sep     = str_replace('\\t', "\011", $sep);
        }
        if ($what == 'excel') {
            $enc_by  = '"';
        } else if (!isset($enc_by)) {
            $enc_by  = '';
        }
        if ($what == 'excel'
            || (empty($esc_by) && $enc_by != '')) {
            // double the "enclosed by" character
            $esc_by  = $enc_by;
        } else if (!isset($esc_by)) {
            $esc_by  = '';
        }

        // Defines the offsets to use
        if ($limit_to > 0 && $limit_from >= 0) {
            $add_query  = ' LIMIT '
                        . (($limit_from > 0) ? $limit_from . ', ' : '')
                        . $limit_to;
        } else {
            $add_query  = '';
        }

        // If required, get fields name at the first line
        if (isset($GLOBALS['showcsvnames']) && $GLOBALS['showcsvnames'] == 'yes') {
            $schema_insert = '';
            $local_query   = 'SHOW COLUMNS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
            $result        = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
            while ($row = PMA_mysql_fetch_array($result)) {
                if ($enc_by == '') {
                    $schema_insert .= $row['Field'];
                } else {
                    $schema_insert .= $enc_by
                                   . str_replace($enc_by, $esc_by . $enc_by, $row['Field'])
                                   . $enc_by;
                }
                $schema_insert     .= $sep;
            } // end while
            $handler(trim(substr($schema_insert, 0, -1)));
        } // end if

        // Gets the data from the database
        if (!empty($sql_query)) {
            $local_query = $sql_query . $add_query;
            PMA_mysql_select_db($db);
        } else {
            $local_query = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        }
        $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        $fields_cnt  = mysql_num_fields($result);

        @set_time_limit($GLOBALS['cfg']['ExecTimeLimit']);

        // Format the data
        $i = 0;
        while ($row = PMA_mysql_fetch_row($result)) {
            $schema_insert = '';
            for ($j = 0; $j < $fields_cnt; $j++) {
                if (!isset($row[$j])) {
                    $schema_insert .= 'NULL';
                }
                else if ($row[$j] == '0' || $row[$j] != '') {
                    // loic1 : always enclose fields
                    if ($what == 'excel') {
                        $row[$j]       = ereg_replace("\015(\012)?", "\012", $row[$j]);
                    }
                    if ($enc_by == '') {
                        $schema_insert .= $row[$j];
                    } else {
                        $schema_insert .= $enc_by
                                       . str_replace($enc_by, $esc_by . $enc_by, $row[$j])
                                       . $enc_by;
                    }
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
                if (!isset($GLOBALS['now'])) {
                    $GLOBALS['now'] = gmdate('D, d M Y H:i:s') . ' GMT';
                }
                header('Expires: ' . $GLOBALS['now']);
            }
        } // end while
        mysql_free_result($result);

        return TRUE;
    } // end of the 'PMA_getTableCsv()' function


    /**
     * Outputs the content of a table in XML format
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   integer  the offset on this table
     * @param   integer  the last row to get
     * @param   string   the end of line sequence
     * @param   string   the url to go back in case of error
     *
     * @return  string   the XML data structure on success
     *
     * @access  public
     */
    function PMA_getTableXML($db, $table, $limit_from = 0, $limit_to = 0, $crlf, $error_url, $sql_query) {
        $local_query = 'SHOW COLUMNS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
        $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        for ($i = 0; $row = PMA_mysql_fetch_array($result, MYSQL_ASSOC); $i++) {
            $columns[$i] = $row['Field'];
        }
        $columns_cnt     = count($columns);
        unset($i);
        mysql_free_result($result);

        // Defines the offsets to use
        if ($limit_to > 0 && $limit_from >= 0) {
            $add_query  = ' LIMIT '
                        . (($limit_from > 0) ? $limit_from . ', ' : '')
                        . $limit_to;
        } else {
            $add_query  = '';
        }

        if (!empty($sql_query)) {
            $local_query = $sql_query . $add_query;
            PMA_mysql_select_db($db);
        } else {
            $local_query = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . $add_query;
        }
        $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        $buffer      = '  <!-- ' . $GLOBALS['strTable'] . ' ' . $table . ' -->' . $crlf;
        while ($record = PMA_mysql_fetch_array($result, MYSQL_ASSOC)) {
            $buffer         .= '    <' . $table . '>' . $crlf;
            for ($i = 0; $i < $columns_cnt; $i++) {
                // There is no way to dectect a "NULL" value with PHP3
                if (!function_exists('is_null') || !is_null($record[$columns[$i]])) {
                    $buffer .= '        <' . $columns[$i] . '>' . htmlspecialchars($record[$columns[$i]])
                            .  '</' . $columns[$i] . '>' . $crlf;
                }
            }
            $buffer         .= '    </' . $table . '>' . $crlf;
        }
        mysql_free_result($result);

        return $buffer;
    } // end of the 'PMA_getTableXML()' function






   /**
     * Outputs the content of a table in LaTeX table/sideways table environment
     *
     * @param   string   the database name
     * @param   string   the table name
     * @param   string   the environment name to be used for the table
     * @param   integer  the offset on this table
     * @param   integer  the last row to get
     * @param   string   the end of line sequence
     * @param   string   the url to go back in case of error
     * @param   string   sql query (optional)
     *
     * @return  string   the LaTeX table environment
     *
     * @access  public
     */
   function PMA_getTableLatex($db, $table, $environment, $limit_from, $limit_to, $crlf, $error_url, $sql_query) {

        $local_query = 'SHOW COLUMNS FROM ' . PMA_backquote($table) . ' FROM ' . PMA_backquote($db);
        $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);
        for ($i = 0; $row = PMA_mysql_fetch_array($result, MYSQL_ASSOC); $i++) {
            $columns[$i] = $row['Field'];
        }
        $columns_cnt     = count($columns);
        unset($i);
        unset($local_query);
        mysql_free_result($result);

        $tex_escape = array("$", "%", "{", "}",  "&",  "#", "_", "^");

        if (!empty($sql_query)) {
            $local_query = $sql_query . $add_query;
            PMA_mysql_select_db($db);
        } else {
            $local_query = 'select * from ' . PMA_backquote($db) . '.' . PMA_backquote($table);
        }
        $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $error_url);

        $buffer      = '\\begin{table} ' . $crlf
                     . ' \\begin{longtable}{|';

        for($index=0;$index<$columns_cnt;$index++) {
           $buffer .= 'c|';
        }
        $buffer .= '} ' . $crlf ;

        $buffer .= ' \\hline \\endhead \\hline \\endfoot \\hline ' . $crlf;

        // print the whole table
        while ($record = PMA_mysql_fetch_array($result, MYSQL_ASSOC)) {

            // print each row
            for($i = 0; $i < $columns_cnt; $i++) {
                if (!function_exists('is_null') || !is_null($record[$columns[$i]])) {
                    $column_value = $record[$columns[$i]];

                    //    $ % { } & # _ ^
                    // escaping special characters
                    for($k=0;$k<count($tex_escape);$k++) {
                        $column_value = str_replace($tex_escape[$k], '\\' . $tex_escape[$k], $column_value);
                    }

                    // last column ... no need for & character
                    if($i == ($columns_cnt - 1)) {
                        $buffer .= $column_value;
                    } else {
                        $buffer .= $column_value . " & ";
                    }
                }
            }
            $buffer .= ' \\\\ \\hline ' . $crlf;
        }

        $buffer .= ' \\end{longtable} \\end{table}' . $crlf;

        mysql_free_result($result);
        return $buffer;

    } // end getTableLatex






} // $__PMA_BUILD_DUMP_LIB__
?>
