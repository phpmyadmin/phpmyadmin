<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/** SQL Parser Functions for phpMyAdmin
 *
 * Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
 * http://www.orbis-terrarum.net/?l=people.robbat2
 *
 * These functions define an SQL parser system, capable of understanding and
 * extracting data from a MySQL type SQL query.
 *
 * The basic procedure for using the new SQL parser:
 * On any page that needs to extract data from a query or to pretty-print a
 * query, you need code like this up at the top:
 *
 * ($sql contains the query)
 * $parsed_sql = PMA_SQP_parse($sql);
 *
 * If you want to extract data from it then, you just need to run
 * $sql_info = PMA_SQP_analyze($parsed_sql);
 *
 * lem9: See comments in PMA_SQP_analyze for the returned info
 *       from the analyzer.
 *
 * If you want a pretty-printed version of the query, do:
 * $string = PMA_SQP_formatHtml($parsed_sql);
 * (note that that you need to have syntax.css.php included somehow in your
 * page for it to work, I recommend '<link rel="stylesheet" type="text/css"
 * href="syntax.css.php" />' at the moment.)
 */


/**
 * Minimum inclusion? (i.e. for the stylesheet builder)
 */

if (!isset($is_minimum_common)) {
    $is_minimum_common = FALSE;
}

if ($is_minimum_common == FALSE) {
    /**
     * Include the string library as we use it heavily
     */
    require_once('./libraries/string.lib.php');

    /**
     * Include data for the SQL Parser
     */
    require_once('./libraries/sqlparser.data.php');

    if (!defined('DEBUG_TIMING')) {
        function PMA_SQP_arrayAdd(&$arr, $type, $data, &$arrsize)
        {
            $arr[] = array('type' => $type, 'data' => $data);
            $arrsize++;
        } // end of the "PMA_SQP_arrayAdd()" function
    } else {
        function PMA_SQP_arrayAdd(&$arr, $type, $data, &$arrsize)
        {
            global $timer;

            $t     = $timer;
            $arr[] = array('type' => $type, 'data' => $data , 'time' => $t);
            $timer = microtime();
            $arrsize++;
        } // end of the "PMA_SQP_arrayAdd()" function
    } // end if... else...


    /**
     * Reset the error variable for the SQL parser
     *
     * @access public
     */
    // Added, Robbat2 - 13 Janurary 2003, 2:59PM
    function PMA_SQP_resetError() {
        global $SQP_errorString;
        $SQP_errorString = '';
        unset($SQP_errorString);
    }

    /**
     * Get the contents of the error variable for the SQL parser
     *
     * @return string Error string from SQL parser
     *
     * @access public
     */
    // Added, Robbat2 - 13 Janurary 2003, 2:59PM
    function PMA_SQP_getErrorString() {
        global $SQP_errorString;
        return isset($SQP_errorString) ? $SQP_errorString : '';
    }

    /**
     * Check if the SQL parser hit an error
     *
     * @return boolean error state
     *
     * @access public
     */
    // Added, Robbat2 - 13 Janurary 2003, 2:59PM
    function PMA_SQP_isError() {
        global $SQP_errorString;
        return isset($SQP_errorString) && !empty($SQP_errorString);
    }

    /**
     * Set an error message for the system
     *
     * @param  string  The error message
     * @param  string  The failing SQL query
     *
     * @access private
     * @scope SQL Parser internal
     */
    // Revised, Robbat2 - 13 Janurary 2003, 2:59PM
    function PMA_SQP_throwError($message, $sql)
    {

        global $SQP_errorString;
        $SQP_errorString = '<p>'.$GLOBALS['strSQLParserUserError'] . '</p>' . "\n"
            . '<pre>' . "\n"
            . 'ERROR: ' . $message . "\n"
            . 'SQL: ' . htmlspecialchars($sql) .  "\n"
            . '</pre>' . "\n";

    } // end of the "PMA_SQP_throwError()" function


    /**
     * Do display the bug report
     *
     * @param  string  The error message
     * @param  string  The failing SQL query
     *
     * @access public
     */
    function PMA_SQP_bug($message, $sql)
    {
        global $SQP_errorString;
        $debugstr = 'ERROR: ' . $message . "\n";
        $debugstr .= 'CVS: $Id$' . "\n";
        $debugstr .= 'MySQL: '.PMA_MYSQL_STR_VERSION . "\n";
        $debugstr .= 'USR OS, AGENT, VER: ' . PMA_USR_OS . ' ' . PMA_USR_BROWSER_AGENT . ' ' . PMA_USR_BROWSER_VER . "\n";
        $debugstr .= 'PMA: ' . PMA_VERSION . "\n";
        $debugstr .= 'PHP VER,OS: ' . PMA_PHP_STR_VERSION . ' ' . PHP_OS . "\n";
        $debugstr .= 'LANG: ' . $GLOBALS['lang'] . "\n";
        $debugstr .= 'SQL: ' . htmlspecialchars($sql);

        $encodedstr     = $debugstr;
        if (@function_exists('gzcompress')) {
            $encodedstr = gzcompress($debugstr, 9);
        }
        $encodedstr     = preg_replace("/(\015\012)|(\015)|(\012)/", '<br />' . "\n", chunk_split(base64_encode($encodedstr)));

        $SQP_errorString .= $GLOBALS['strSQLParserBugMessage'] . '<br />' . "\n"
             . '----' . $GLOBALS['strBeginCut'] . '----' . '<br />' . "\n"
             . $encodedstr . "\n"
             . '----' . $GLOBALS['strEndCut'] . '----' . '<br />' . "\n";

        $SQP_errorString .= '----' . $GLOBALS['strBeginRaw'] . '----<br />' . "\n"
             . '<pre>' . "\n"
             . $debugstr
             . '</pre>' . "\n"
             . '----' . $GLOBALS['strEndRaw'] . '----<br />' . "\n";

    } // end of the "PMA_SQP_bug()" function


    /**
     * Parses the SQL queries
     *
     * @param  string   The SQL query list
     *
     * @return mixed    Most of times, nothing...
     *
     * @global array    The current PMA configuration
     * @global array    MySQL column attributes
     * @global array    MySQL reserved words
     * @global array    MySQL column types
     * @global array    MySQL function names
     * @global integer  MySQL column attributes count
     * @global integer  MySQL reserved words count
     * @global integer  MySQL column types count
     * @global integer  MySQL function names count
     *
     * @access public
     */
    function PMA_SQP_parse($sql)
    {
        global $cfg;
        global $PMA_SQPdata_column_attrib, $PMA_SQPdata_reserved_word, $PMA_SQPdata_column_type, $PMA_SQPdata_function_name,
        $PMA_SQPdata_column_attrib_cnt, $PMA_SQPdata_reserved_word_cnt, $PMA_SQPdata_column_type_cnt, $PMA_SQPdata_function_name_cnt;

        // rabus: Convert all line feeds to Unix style
        $sql = str_replace("\r\n", "\n", $sql);
        $sql = str_replace("\r", "\n", $sql);

        $len = $GLOBALS['PMA_strlen']($sql);
        if ($len == 0) {
            return array();
        }

        $sql_array               = array();
        $sql_array['raw']        = $sql;
        $count1                  = 0;
        $count2                  = 0;
        $punct_queryend          = ';';
        $punct_qualifier         = '.';
        $punct_listsep           = ',';
        $punct_level_plus        = '(';
        $punct_level_minus       = ')';
        $digit_floatdecimal      = '.';
        $digit_hexset            = 'x';
        $bracket_list            = '()[]{}';
        $allpunct_list           =  '-,;:!?/.^~\*&%+<=>|';
        $allpunct_list_pair      = array (
            0 => '!=',
            1 => '&&',
            2 => ':=',
            3 => '<<',
            4 => '<=',
            5 => '<=>',
            6 => '<>',
            7 => '>=',
            8 => '>>',
            9 => '||'
        );
        $allpunct_list_pair_size = 10; //count($allpunct_list_pair);
        $quote_list              = '\'"`';
        $arraysize               = 0;

        while ($count2 < $len) {
            $c      = $sql[$count2];
            $count1 = $count2;

            if (($c == "\n")) {
                $count2++;
                PMA_SQP_arrayAdd($sql_array, 'white_newline', '', $arraysize);
                continue;
            }

            // Checks for white space
            if (PMA_STR_isSpace($c)) {
                $count2++;
                continue;
            }

            // Checks for comment lines.
            // MySQL style #
            // C style /* */
            // ANSI style --
            if (($c == '#')
                || (($count2 + 1 < $len) && ($c == '/') && ($sql[$count2 + 1] == '*'))
                || (($count2 + 2 < $len) && ($c == '-') && ($sql[$count2 + 1] == '-') && (($sql[$count2 + 2] == ' ') || ($sql[$count2 + 2] == "\n")))) {
                $count2++;
                $pos  = 0;
                $type = 'bad';
                switch ($c) {
                    case '#':
                        $type = 'mysql';
                    case '-':
                        $type = 'ansi';
                        $pos  = $GLOBALS['PMA_strpos']($sql, "\n", $count2);
                        break;
                    case '/':
                        $type = 'c';
                        $pos  = $GLOBALS['PMA_strpos']($sql, '*/', $count2);
                        $pos  += 2;
                        break;
                    default:
                        break;
                } // end switch
                $count2 = ($pos < $count2) ? $len : $pos;
                $str    = $GLOBALS['PMA_substr']($sql, $count1, $count2 - $count1);
                PMA_SQP_arrayAdd($sql_array, 'comment_' . $type, $str, $arraysize);
                continue;
            } // end if

            // Checks for something inside quotation marks
            if (PMA_STR_strInStr($c, $quote_list)) {
                $startquotepos   = $count2;
                $quotetype       = $c;
                $count2++;
                $escaped         = FALSE;
                $escaped_escaped = FALSE;
                $pos             = $count2;
                $oldpos          = 0;
                do {
                    $oldpos = $pos;
                    $pos    = $GLOBALS['PMA_strpos'](' ' . $sql, $quotetype, $oldpos + 1) - 1;
                    // ($pos === FALSE)
                    if ($pos < 0) {
                        $debugstr = $GLOBALS['strSQPBugUnclosedQuote'] . ' @ ' . $startquotepos. "\n"
                                  . 'STR: ' . $quotetype;
                        PMA_SQP_throwError($debugstr, $sql);
                        return $sql;
                    }

                    // If the quote is the first character, it can't be
                    // escaped, so don't do the rest of the code
                    if ($pos == 0) {
                        break;
                    }

                    // Checks for MySQL escaping using a \
                    // And checks for ANSI escaping using the $quotetype character
                    if (($pos < $len) && PMA_STR_charIsEscaped($sql, $pos)) {
                        $pos ++;
                        continue;
                    } else if (($pos + 1 < $len) && ($sql[$pos] == $quotetype) && ($sql[$pos + 1] == $quotetype)) {
                        $pos = $pos + 2;
                        continue;
                    } else {
                        break;
                    }
                } while ($len > $pos); // end do

                $count2       = $pos;
                $count2++;
                $type         = 'quote_';
                switch ($quotetype) {
                    case '\'':
                        $type .= 'single';
                        break;
                    case '"':
                        $type .= 'double';
                        break;
                    case '`':
                        $type .= 'backtick';
                        break;
                    default:
                        break;
                } // end switch
                $data = $GLOBALS['PMA_substr']($sql, $count1, $count2 - $count1);
                PMA_SQP_arrayAdd($sql_array, $type, $data, $arraysize);
                continue;
            }

            // Checks for brackets
            if (PMA_STR_strInStr($c, $bracket_list)) {
                // All bracket tokens are only one item long
                $count2++;
                $type_type     = '';
                if (PMA_STR_strInStr($c, '([{')) {
                    $type_type = 'open';
                } else {
                    $type_type = 'close';
                }

                $type_style     = '';
                if (PMA_STR_strInStr($c, '()')) {
                    $type_style = 'round';
                } elseif (PMA_STR_strInStr($c, '[]')) {
                    $type_style = 'square';
                } else {
                    $type_style = 'curly';
                }

                $type = 'punct_bracket_' . $type_type . '_' . $type_style;
                PMA_SQP_arrayAdd($sql_array, $type, $c, $arraysize);
                continue;
            }

            // Checks for punct
            if (PMA_STR_strInStr($c, $allpunct_list)) {
                while (($count2 < $len) && PMA_STR_strInStr($sql[$count2], $allpunct_list)) {
                    $count2++;
                }
                $l = $count2 - $count1;
                if ($l == 1) {
                    $punct_data = $c;
                } else {
                    $punct_data = $GLOBALS['PMA_substr']($sql, $count1, $l);
                }

                // Special case, sometimes, althought two characters are
                // adjectent directly, they ACTUALLY need to be seperate
                if ($l == 1) {
                    $t_suffix         = '';
                    switch ($punct_data) {
                        case $punct_queryend:
                            $t_suffix = '_queryend';
                            break;
                        case $punct_qualifier:
                            $t_suffix = '_qualifier';
                            break;
                        case $punct_listsep:
                            $t_suffix = '_listsep';
                            break;
                        default:
                            break;
                    }
                    PMA_SQP_arrayAdd($sql_array, 'punct' . $t_suffix, $punct_data, $arraysize);
                }
                else if (PMA_STR_binarySearchInArr($punct_data, $allpunct_list_pair, $allpunct_list_pair_size)) {
                    // Ok, we have one of the valid combined punct expressions
                    PMA_SQP_arrayAdd($sql_array, 'punct', $punct_data, $arraysize);
                }
                else {
                    // Bad luck, lets split it up more
                    $first  = $punct_data[0];
                    $first2 = $punct_data[0] . $punct_data[1];
                    $last2  = $punct_data[$l - 2] . $punct_data[$l - 1];
                    $last   = $punct_data[$l - 1];
                    if (($first == ',') || ($first == ';') || ($first == '.') || ($first == '*')) {
                        $count2     = $count1 + 1;
                        $punct_data = $first;
                    } else if (($last2 == '/*') || ($last2 == '--')) {
                        $count2     -= 2;
                        $punct_data = $GLOBALS['PMA_substr']($sql, $count1, $count2 - $count1);
                    } else if (($last == '-') || ($last == '+') || ($last == '!')) {
                        $count2--;
                        $punct_data = $GLOBALS['PMA_substr']($sql, $count1, $count2 - $count1);
                    // TODO: for negation operator, split in 2 tokens ?
                    // "select x&~1 from t"
                    // becomes "select x & ~ 1 from t" ?

                    } else if ($last != '~') {
                        $debugstr =  $GLOBALS['strSQPBugUnknownPunctuation'] . ' @ ' . ($count1+1) . "\n"
                                  . 'STR: ' . $punct_data;
                        PMA_SQP_throwError($debugstr, $sql);
                        return $sql;
                    }
                    PMA_SQP_arrayAdd($sql_array, 'punct', $punct_data, $arraysize);
                    continue;
                } // end if... else if... else
                continue;
            }

            // Checks for alpha
            if (PMA_STR_isSqlIdentifier($c, FALSE) || ($c == '@')) {
                $count2 ++;

                //TODO: a @ can also be present in expressions like
                // FROM 'user'@'%'
                // in this case, the @ is wrongly marked as alpha_variable

                $is_sql_variable         = ($c == '@');
                $is_digit                = (!$is_sql_variable) && PMA_STR_isDigit($c);
                $is_hex_digit            = ($is_digit) && ($c == '0') && ($count2 < $len) && ($sql[$count2] == 'x');
                $is_float_digit          = FALSE;
                $is_float_digit_exponent = FALSE;

                if ($is_hex_digit) {
                    $count2++;
                }

                while (($count2 < $len) && PMA_STR_isSqlIdentifier($sql[$count2], ($is_sql_variable || $is_digit))) {
                    $c2 = $sql[$count2];
                    if ($is_sql_variable && ($c2 == '.')) {
                        $count2++;
                        continue;
                    }
                    if ($is_digit && (!$is_hex_digit) && ($c2 == '.')) {
                        $count2++;
                        if (!$is_float_digit) {
                            $is_float_digit = TRUE;
                            continue;
                        } else {
                            $debugstr = $GLOBALS['strSQPBugInvalidIdentifer'] . ' @ ' . ($count1+1) . "\n"
                                      . 'STR: ' . $GLOBALS['PMA_substr']($sql, $count1, $count2 - $count1);
                            PMA_SQP_throwError($debugstr, $sql);
                            return $sql;
                        }
                    }
                    if ($is_digit && (!$is_hex_digit) && (($c2 == 'e') || ($c2 == 'E'))) {
                        if (!$is_float_digit_exponent) {
                            $is_float_digit_exponent = TRUE;
                            $is_float_digit          = TRUE;
                            $count2++;
                            continue;
                        } else {
                            $is_digit                = FALSE;
                            $is_float_digit          = FALSE;
                        }
                    }
                    if (($is_hex_digit && PMA_STR_isHexDigit($c2)) || ($is_digit && PMA_STR_isDigit($c2))) {
                        $count2++;
                        continue;
                    } else {
                        $is_digit     = FALSE;
                        $is_hex_digit = FALSE;
                    }

                    $count2++;
                } // end while

                $l    = $count2 - $count1;
                $str  = $GLOBALS['PMA_substr']($sql, $count1, $l);

                $type = '';
                if ($is_digit) {
                    $type     = 'digit';
                    if ($is_float_digit) {
                        $type .= '_float';
                    } else if ($is_hex_digit) {
                        $type .= '_hex';
                    } else {
                        $type .= '_integer';
                    }
                }
                else {
                    if ($is_sql_variable != FALSE) {
                        $type = 'alpha_variable';
                    } else {
                        $type = 'alpha';
                    }
                } // end if... else....
                PMA_SQP_arrayAdd($sql_array, $type, $str, $arraysize);

                continue;
            }

            // DEBUG
            $count2++;

            $debugstr = 'C1 C2 LEN: ' . $count1 . ' ' . $count2 . ' ' . $len .  "\n"
                      . 'STR: ' . $GLOBALS['PMA_substr']($sql, $count1, $count2 - $count1) . "\n";
            PMA_SQP_bug($debugstr, $sql);
            return $sql;

        } // end while ($count2 < $len)


        if ($arraysize > 0) {
          $t_next       = $sql_array[0]['type'];
          $t_prev       = '';
          $t_cur        = '';
          $d_next       = $sql_array[0]['data'];
          $d_prev       = '';
          $d_cur        = '';
          $d_next_upper = $t_next == 'alpha' ? strtoupper($d_next) : $d_next;
          $d_prev_upper = '';
          $d_cur_upper  = '';
        }

        for ($i = 0; $i < $arraysize; $i++) {
          $t_prev       = $t_cur;
          $t_cur        = $t_next;
          $d_prev       = $d_cur;
          $d_cur        = $d_next;
          $d_prev_upper = $d_cur_upper;
          $d_cur_upper  = $d_next_upper;
          if (($i + 1) < $arraysize) {
            $t_next = $sql_array[$i + 1]['type'];
            $d_next = $sql_array[$i + 1]['data'];
            $d_next_upper = $t_next == 'alpha' ? strtoupper($d_next) : $d_next;
          } else {
            $t_next       = '';
            $d_next       = '';
            $d_next_upper = '';
          }

          //DEBUG echo "[prev: <b>".$d_prev."</b> ".$t_prev."][cur: <b>".$d_cur."</b> ".$t_cur."][next: <b>".$d_next."</b> ".$t_next."]<br>";

          if ($t_cur == 'alpha') {
            $t_suffix     = '_identifier';
            if (($t_next == 'punct_qualifier') || ($t_prev == 'punct_qualifier')) {
              $t_suffix = '_identifier';
            } else if (($t_next == 'punct_bracket_open_round')
            && PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_function_name, $PMA_SQPdata_function_name_cnt)) {
              $t_suffix = '_functionName';
            } else if (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_column_type, $PMA_SQPdata_column_type_cnt))  {
              $t_suffix = '_columnType';

              // Temporary fix for BUG #621357
              //TODO FIX PROPERLY NEEDS OVERHAUL OF SQL TOKENIZER
              if ($d_cur_upper == 'SET' && $t_next != 'punct_bracket_open_round') {
                $t_suffix = '_reservedWord';
              }
              //END OF TEMPORARY FIX

              // CHARACTER is a synonym for CHAR, but can also be meant as
              // CHARACTER SET. In this case, we have a reserved word.
              if ($d_cur_upper == 'CHARACTER' && $d_next_upper == 'SET') {
                $t_suffix = '_reservedWord';
              }

              // experimental
              // current is a column type, so previous must not be
              // a reserved word but an identifier
              // CREATE TABLE SG_Persons (first varchar(64))

              //if ($sql_array[$i-1]['type'] =='alpha_reservedWord') {
              //    $sql_array[$i-1]['type'] = 'alpha_identifier';
              //}

            } else if (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_reserved_word, $PMA_SQPdata_reserved_word_cnt)) {
              $t_suffix = '_reservedWord';
            } else if (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_column_attrib, $PMA_SQPdata_column_attrib_cnt)) {
              $t_suffix = '_columnAttrib';
              // INNODB is a MySQL table type, but in "SHOW INNODB STATUS",
              // it should be regarded as a reserved word.
              if ($d_cur_upper == 'INNODB' && $d_prev_upper == 'SHOW' && $d_next_upper == 'STATUS') {
                $t_suffix = '_reservedWord';
              }
            } else {
              // Do nothing
            }
            $sql_array[$i]['type'] .= $t_suffix;
          }
        } // end for

        // Stores the size of the array inside the array, as count() is a slow
        // operation.
        $sql_array['len'] = $arraysize;

        // Sends the data back
        return $sql_array;
    } // end of the "PMA_SQP_parse()" function

   /**
    * Checks for token types being what we want...
    *
    * @param  string String of type that we have
    * @param  string String of type that we want
    *
    * @return boolean result of check
    *
    * @access private
    */
    function PMA_SQP_typeCheck($toCheck, $whatWeWant)
    {
        $typeSeperator = '_';
        if(strcmp($whatWeWant, $toCheck) == 0) {
            return TRUE;
        } else {
            //if(strpos($whatWeWant, $typeSeperator) === FALSE) {
            // PHP3 compatible (works unless there is a real ff character)
            if(!strpos("\xff" . $whatWeWant, $typeSeperator)) {
                return strncmp($whatWeWant, $toCheck , strpos($toCheck, $typeSeperator)) == 0;
            } else {
                return FALSE;
            }
        }
    }


    /**
     * Analyzes SQL queries
     *
     * @param  array   The SQL queries
     *
     * @return array   The analyzed SQL queries
     *
     * @access public
     */
    function PMA_SQP_analyze($arr)
    {
        $result          = array();
        $size            = $arr['len'];
        $subresult       = array(
            'querytype'      => '',
            'select_expr_clause'=> '', // the whole stuff between SELECT and FROM , except DISTINCT
            'position_of_first_select' => '', // the array index
            'from_clause'=> '',
            'group_by_clause'=> '',
            'order_by_clause'=> '',
            'having_clause'  => '',
            'where_clause'   => '',
            'where_clause_identifiers'   => array(),
            'queryflags'     => array(),
            'select_expr'    => array(),
            'table_ref'      => array(),
            'foreign_keys'   => array()
        );
        $subresult_empty = $subresult;
        $seek_queryend         = FALSE;
        $seen_end_of_table_ref = FALSE;

        // for SELECT EXTRACT(YEAR_MONTH FROM CURDATE())
        // we must not use CURDATE as a table_ref
        // so we track wether we are in the EXTRACT()
        $in_extract          = FALSE;

/* Description of analyzer results
 *
 * lem9: db, table, column, alias
 *      ------------------------
 *
 * Inside the $subresult array, we create ['select_expr'] and ['table_ref'] arrays.
 *
 * The SELECT syntax (simplified) is
 *
 * SELECT
 *    select_expression,...
 *    [FROM [table_references]
 *
 *
 * ['select_expr'] is filled with each expression, the key represents the
 * expression position in the list (0-based) (so we don't lose track of
 * multiple occurences of the same column).
 *
 * ['table_ref'] is filled with each table ref, same thing for the key.
 *
 * I create all sub-values empty, even if they are
 * not present (for example no select_expression alias).
 *
 * There is a debug section at the end of loop #1, if you want to
 * see the exact contents of select_expr and table_ref
 *
 * lem9: queryflags
 *       ----------
 *
 * In $subresult, array 'queryflags' is filled, according to what we
 * find in the query.
 *
 * Currently, those are generated:
 *
 * ['queryflags']['need_confirm'] = 1; if the query needs confirmation
 * ['queryflags']['select_from'] = 1; if this is a real SELECT...FROM
 * ['queryflags']['distinct'] = 1;    for a DISTINCT
 * ['queryflags']['union'] = 1;       for a UNION
 *
 * lem9:  query clauses
 *        -------------
 *
 * The select is splitted in those clauses:
 * ['select_expr_clause']
 * ['from_clause']
 * ['group_by_clause']
 * ['order_by_clause']
 * ['having_clause']
 * ['where_clause']
 *
 * and the identifiers of the where clause are put into the array
 * ['where_clause_identifier']
 *
 * lem9:   foreign keys
 *         ------------
 * The CREATE TABLE may contain FOREIGN KEY clauses, so they get
 * analyzed and ['foreign_keys'] is an array filled with
 * the constraint name, the index list,
 * the REFERENCES table name and REFERENCES index list,
 * and ON UPDATE | ON DELETE clauses
 *
 * lem9: position_of_first_select
 *       ------------------------
 *
 * The array index of the first SELECT we find. Will be used to
 * insert a SQL_CALC_FOUND_ROWS.
 */

        // must be sorted
        // TODO: current logic checks for only one word, so I put only the
        // first word of the reserved expressions that end a table ref;
        // maybe this is not ok (the first word might mean something else)
//        $words_ending_table_ref = array(
//            'FOR UPDATE',
//            'GROUP BY',
//            'HAVING',
//            'LIMIT',
//            'LOCK IN SHARE MODE',
//            'ORDER BY',
//            'PROCEDURE',
//            'UNION',
//            'WHERE'
//        );
        $words_ending_table_ref = array(
            'FOR',
            'GROUP',
            'HAVING',
            'LIMIT',
            'LOCK',
            'ORDER',
            'PROCEDURE',
            'UNION',
            'WHERE'
        );
        $words_ending_table_ref_cnt = 9; //count($words_ending_table_ref);

        $words_ending_clauses = array(
            'FOR',
            'LIMIT',
            'LOCK',
            'PROCEDURE',
            'UNION'
        );
        $words_ending_clauses_cnt = 5; //count($words_ending_clauses);




        // must be sorted
        $supported_query_types = array(
            'SELECT'
            /*
            // Support for these additional query types will come later on.
            'DELETE',
            'INSERT',
            'REPLACE',
            'TRUNCATE',
            'UPDATE'
            'EXPLAIN',
            'DESCRIBE',
            'SHOW',
            'CREATE',
            'SET',
            'ALTER'
            */
        );
        $supported_query_types_cnt = count($supported_query_types);

        // loop #1 for each token: select_expr, table_ref for SELECT

        for ($i = 0; $i < $size; $i++) {
//echo "trace <b>"  . $arr[$i]['data'] . "</b> (" . $arr[$i]['type'] . ")<br>";

            // High speed seek for locating the end of the current query
            if ($seek_queryend == TRUE) {
                if ($arr[$i]['type'] == 'punct_queryend') {
                    $seek_queryend = FALSE;
                } else {
                    continue;
                } // end if (type == punct_queryend)
            } // end if ($seek_queryend)

            // TODO: when we find a UNION, should we split
            // in another subresult?
            if ($arr[$i]['type'] == 'punct_queryend') {
                $result[]  = $subresult;
                $subresult = $subresult_empty;
                continue;
            } // end if (type == punct_queryend)

// ==============================================================
            if ($arr[$i]['type'] == 'punct_bracket_open_round') {
                if ($in_extract) {
                    $number_of_brackets_in_extract++;
                }
            }
// ==============================================================
            if ($arr[$i]['type'] == 'punct_bracket_close_round') {
                if ($in_extract) {
                    $number_of_brackets_in_extract--;
                    if ($number_of_brackets_in_extract == 0) {
                       $in_extract = FALSE;
                    }
                }
            }
// ==============================================================
            if ($arr[$i]['type'] == 'alpha_functionName') {
                $upper_data = strtoupper($arr[$i]['data']);
                if ($upper_data =='EXTRACT') {
                    $in_extract = TRUE;
                    $number_of_brackets_in_extract = 0;
                }
            }

// ==============================================================
            if ($arr[$i]['type'] == 'alpha_reservedWord') {
                // We don't know what type of query yet, so run this
                if ($subresult['querytype'] == '') {
                    $subresult['querytype'] = strtoupper($arr[$i]['data']);
                } // end if (querytype was empty)

                // Check if we support this type of query
                if (!PMA_STR_binarySearchInArr($subresult['querytype'], $supported_query_types, $supported_query_types_cnt)) {
                    // Skip ahead to the next one if we don't
                    $seek_queryend = TRUE;
                    continue;
                } // end if (query not supported)

                // upper once
                $upper_data = strtoupper($arr[$i]['data']);
                //TODO: reset for each query?

                if ($upper_data == 'SELECT') {
                    $seen_from = FALSE;
                    $previous_was_identifier = FALSE;
                    $current_select_expr = -1;
                    $seen_end_of_table_ref = FALSE;
                } // end if ( data == SELECT)

                if ($upper_data =='FROM' && !$in_extract) {
                    $current_table_ref = -1;
                    $seen_from = TRUE;
                    $previous_was_identifier = FALSE;
                    $save_table_ref = TRUE;
                } // end if (data == FROM)

                // here, do not 'continue' the loop, as we have more work for
                // reserved words below
            } // end if (type == alpha_reservedWord)

// ==============================
            if (($arr[$i]['type'] == 'quote_backtick')
             || ($arr[$i]['type'] == 'quote_double')
             || ($arr[$i]['type'] == 'quote_single')
             || ($arr[$i]['type'] == 'alpha_identifier')) {

                switch ($arr[$i]['type']) {
                    case 'alpha_identifier':
                        $identifier = $arr[$i]['data'];
                        break;

                //TODO: check embedded double quotes or backticks?
                // and/or remove just the first and last character?
                    case 'quote_backtick':
                        $identifier = str_replace('`','',$arr[$i]['data']);
                        break;
                    case 'quote_double':
                        $identifier = str_replace('"','',$arr[$i]['data']);
                        break;
                    case 'quote_single':
                        $identifier = str_replace("'","",$arr[$i]['data']);
                        break;
                } // end switch

                if ($subresult['querytype'] == 'SELECT') {
                    if (!$seen_from) {
                        if ($previous_was_identifier && isset($chain)) {
                            // found alias for this select_expr, save it
                            // but only if we got something in $chain
                            // (for example, SELECT COUNT(*) AS cnt
                            // puts nothing in $chain, so we avoid
                            // setting the alias)
                            $alias_for_select_expr = $identifier;
                        } else {
                            $chain[] = $identifier;
                            $previous_was_identifier = TRUE;

                        } // end if !$previous_was_identifier
                    } else {
                        // ($seen_from)
                        if ($save_table_ref && !$seen_end_of_table_ref) {
                            if ($previous_was_identifier) {
                                // found alias for table ref
                                // save it for later
                                $alias_for_table_ref = $identifier;
                            } else {
                                $chain[] = $identifier;
                                $previous_was_identifier = TRUE;

                            } // end if ($previous_was_identifier)
                        } // end if ($save_table_ref &&!$seen_end_of_table_ref)
                    } // end if (!$seen_from)
                } // end if (querytype SELECT)
            } // end if ( quote_backtick or double quote or alpha_identifier)

// ===================================
            if ($arr[$i]['type'] == 'punct_qualifier') {
                // to be able to detect an identifier following another
                $previous_was_identifier = FALSE;
                continue;
            } // end if (punct_qualifier)

            // TODO: check if 3 identifiers following one another -> error

            //    s a v e    a    s e l e c t    e x p r
            // finding a list separator or FROM
            // means that we must save the current chain of identifiers
            // into a select expression

            // for now, we only save a select expression if it contains
            // at least one identifier, as we are interested in checking
            // the columns and table names, so in "select * from persons",
            // the "*" is not saved

            if (isset($chain) && !$seen_end_of_table_ref
               && (   (!$seen_from
                   && $arr[$i]['type'] == 'punct_listsep')
                  || ($arr[$i]['type'] == 'alpha_reservedWord' && $upper_data == 'FROM')) ) {
                $size_chain = count($chain);
                $current_select_expr++;
                $subresult['select_expr'][$current_select_expr] = array(
                  'expr' => '',
                  'alias' => '',
                  'db'   => '',
                  'table_name' => '',
                  'table_true_name' => '',
                  'column' => ''
                 );

                if (!empty($alias_for_select_expr)) {
                    // we had found an alias for this select expression
                    $subresult['select_expr'][$current_select_expr]['alias'] = $alias_for_select_expr;
                    unset($alias_for_select_expr);
                }
                // there is at least a column
                $subresult['select_expr'][$current_select_expr]['column'] = $chain[$size_chain - 1];
                $subresult['select_expr'][$current_select_expr]['expr'] = $chain[$size_chain - 1];

                // maybe a table
                if ($size_chain > 1) {
                    $subresult['select_expr'][$current_select_expr]['table_name'] = $chain[$size_chain - 2];
                    // we assume for now that this is also the true name
                    $subresult['select_expr'][$current_select_expr]['table_true_name'] = $chain[$size_chain - 2];
                    $subresult['select_expr'][$current_select_expr]['expr']
                     = $subresult['select_expr'][$current_select_expr]['table_name']
                      . '.' . $subresult['select_expr'][$current_select_expr]['expr'];
                } // end if ($size_chain > 1)

                // maybe a db
                if ($size_chain > 2) {
                    $subresult['select_expr'][$current_select_expr]['db'] = $chain[$size_chain - 3];
                    $subresult['select_expr'][$current_select_expr]['expr']
                     = $subresult['select_expr'][$current_select_expr]['db']
                      . '.' . $subresult['select_expr'][$current_select_expr]['expr'];
                } // end if ($size_chain > 2)
                unset($chain);

                // TODO: explain this:
                if (($arr[$i]['type'] == 'alpha_reservedWord')
                 && ($upper_data != 'FROM')) {
                    $previous_was_identifier = TRUE;
                }

            } // end if (save a select expr)


            //======================================
            //    s a v e    a    t a b l e    r e f
            //======================================

            // maybe we just saw the end of table refs
            // but the last table ref has to be saved
            // or we are at the last token (TODO: there could be another
            // query after this one)
            // or we just got a reserved word

            if (isset($chain) && $seen_from && $save_table_ref
             && ($arr[$i]['type'] == 'punct_listsep'
               || ($arr[$i]['type'] == 'alpha_reservedWord' && $upper_data!="AS")
               || $seen_end_of_table_ref
               || $i==$size-1 )) {

                $size_chain = count($chain);
                $current_table_ref++;
                $subresult['table_ref'][$current_table_ref] = array(
                  'expr'            => '',
                  'db'              => '',
                  'table_name'      => '',
                  'table_alias'     => '',
                  'table_true_name' => ''
                 );
                if (!empty($alias_for_table_ref)) {
                    $subresult['table_ref'][$current_table_ref]['table_alias'] = $alias_for_table_ref;
                    unset($alias_for_table_ref);
                }
                $subresult['table_ref'][$current_table_ref]['table_name'] = $chain[$size_chain - 1];
                // we assume for now that this is also the true name
                $subresult['table_ref'][$current_table_ref]['table_true_name'] = $chain[$size_chain - 1];
                $subresult['table_ref'][$current_table_ref]['expr']
                     = $subresult['table_ref'][$current_table_ref]['table_name'];
                // maybe a db
                if ($size_chain > 1) {
                    $subresult['table_ref'][$current_table_ref]['db'] = $chain[$size_chain - 2];
                    $subresult['table_ref'][$current_table_ref]['expr']
                     = $subresult['table_ref'][$current_table_ref]['db']
                      . '.' . $subresult['table_ref'][$current_table_ref]['expr'];
                } // end if ($size_chain > 1)

                // add the table alias into the whole expression
                $subresult['table_ref'][$current_table_ref]['expr']
                 .= ' ' . $subresult['table_ref'][$current_table_ref]['table_alias'];

                unset($chain);
                $previous_was_identifier = TRUE;
                //continue;

            } // end if (save a table ref)


            // when we have found all table refs,
            // for each table_ref alias, put the true name of the table
            // in the corresponding select expressions

            if (isset($current_table_ref) && ($seen_end_of_table_ref || $i == $size-1)) {
                for ($tr=0; $tr <= $current_table_ref; $tr++) {
                    $alias = $subresult['table_ref'][$tr]['table_alias'];
                    $truename = $subresult['table_ref'][$tr]['table_true_name'];
                    for ($se=0; $se <= $current_select_expr; $se++) {
                        if (!empty($alias) && $subresult['select_expr'][$se]['table_true_name']
                           == $alias) {
                            $subresult['select_expr'][$se]['table_true_name']
                             = $truename;
                        } // end if (found the alias)
                    } // end for (select expressions)

                } // end for (table refs)
            } // end if (set the true names)


           // e n d i n g    l o o p  #1
           // set the $previous_was_identifier to FALSE if the current
           // token is not an identifier
           if (($arr[$i]['type'] != 'alpha_identifier')
            && ($arr[$i]['type'] != 'quote_double')
            && ($arr[$i]['type'] != 'quote_single')
            && ($arr[$i]['type'] != 'quote_backtick')) {
               $previous_was_identifier = FALSE;
           } // end if

           // however, if we are on AS, we must keep the $previous_was_identifier
           if (($arr[$i]['type'] == 'alpha_reservedWord')
            && ($upper_data == 'AS'))  {
               $previous_was_identifier = TRUE;
           }

           if (($arr[$i]['type'] == 'alpha_reservedWord')
            && ($upper_data =='ON' || $upper_data =='USING')) {
               $save_table_ref = FALSE;
           } // end if (data == ON)

           if (($arr[$i]['type'] == 'alpha_reservedWord')
            && ($upper_data =='JOIN' || $upper_data =='FROM')) {
               $save_table_ref = TRUE;
           } // end if (data == JOIN)

           // no need to check the end of table ref if we already did
           // TODO: maybe add "&& $seen_from"
           if (!$seen_end_of_table_ref) {
               // if this is the last token, it implies that we have
               // seen the end of table references
               // Check for the end of table references
               if (($i == $size-1)
               || ($arr[$i]['type'] == 'alpha_reservedWord'
                  && PMA_STR_binarySearchInArr($upper_data, $words_ending_table_ref, $words_ending_table_ref_cnt))) {
                   $seen_end_of_table_ref = TRUE;

                   // to be able to save the last table ref, but do not
                   // set it true if we found a word like "ON" that has
                   // already set it to false
                   if (isset($save_table_ref) && $save_table_ref != FALSE) {
                      $save_table_ref = TRUE;
                   } //end if

               } // end if (check for end of table ref)
           } //end if (!$seen_end_of_table_ref)

           if ($seen_end_of_table_ref) {
               $save_table_ref = FALSE;
           } // end if

        } // end for $i (loop #1)

        // -------------------------------------------------------
        // This is a big hunk of debugging code by Marc for this.
        // -------------------------------------------------------
        /*
          if (isset($current_select_expr)) {
           for ($trace=0; $trace<=$current_select_expr; $trace++) {

           echo "<br>";
           reset ($subresult['select_expr'][$trace]);
           while (list ($key, $val) = each ($subresult['select_expr'][$trace]))
           echo "sel expr $trace $key => $val<br />\n";
           }
          }

          if (isset($current_table_ref)) {
           for ($trace=0; $trace<=$current_table_ref; $trace++) {

           echo "<br>";
           reset ($subresult['table_ref'][$trace]);
           while (list ($key, $val) = each ($subresult['table_ref'][$trace]))
           echo "table ref $trace $key => $val<br />\n";
           }
          }
        */
        // -------------------------------------------------------


        // loop #2: for queryflags
        //          ,querytype (for queries != 'SELECT')
        //
        // we will also need this queryflag in loop 2
        // so set it here
        if (isset($current_table_ref) && $current_table_ref > -1) {
            $subresult['queryflags']['select_from'] = 1;
        }

        $seen_reserved_word = FALSE;
        $seen_group = FALSE;
        $seen_order = FALSE;
        $in_group_by = FALSE; // true when we are into the GROUP BY clause
        $in_order_by = FALSE; // true when we are into the ORDER BY clause
        $in_having = FALSE; // true when we are into the HAVING clause
        $in_select_expr = FALSE; // true when we are into the select expr clause
        $in_where = FALSE; // true when we are into the WHERE clause
        $in_from = FALSE;

        for ($i = 0; $i < $size; $i++) {
//DEBUG echo "trace loop2 <b>"  . $arr[$i]['data'] . "</b> (" . $arr[$i]['type'] . ")<br>";

           // need_confirm
           //
           // check for reserved words that will have to generate
           // a confirmation request later in sql.php
           // the cases are:
           //   DROP TABLE
           //   DROP DATABASE
           //   ALTER TABLE... DROP
           //   DELETE FROM...
           //
           // this code is not used for confirmations coming from functions.js

           // TODO: check for punct_queryend

           if ($arr[$i]['type'] == 'alpha_reservedWord') {
               $upper_data = strtoupper($arr[$i]['data']);
               if (!$seen_reserved_word) {
                   $first_reserved_word = $upper_data;
                   $subresult['querytype'] = $upper_data;
                   $seen_reserved_word = TRUE;

                   // if the first reserved word is DROP or DELETE,
                   // we know this is a query that needs to be confirmed
                   if ($first_reserved_word=='DROP'
                       || $first_reserved_word == 'DELETE'
                       || $first_reserved_word == 'TRUNCATE') {
                      $subresult['queryflags']['need_confirm'] = 1;
                   }

                   if ($first_reserved_word=='SELECT'){
                       $position_of_first_select = $i;
                   }

               } else {
                   if ($upper_data=='DROP' && $first_reserved_word=='ALTER') {
                      $subresult['queryflags']['need_confirm'] = 1;
                   }
               }

               if ($upper_data == 'SELECT') {
                   $in_select_expr = TRUE;
                   $select_expr_clause = '';
               }
               if ($upper_data == 'DISTINCT') {
                      $subresult['queryflags']['distinct'] = 1;
               }

               if ($upper_data == 'UNION') {
                      $subresult['queryflags']['union'] = 1;
               }

               // if this is a real SELECT...FROM
               if ($upper_data == 'FROM' && isset($subresult['queryflags']['select_from']) && $subresult['queryflags']['select_from'] == 1) {
                   $in_from = TRUE;
                   $from_clause = '';
                   $in_select_expr = FALSE;
               }


               // (we could have less resetting of variables to FALSE
               // if we trust that the query respects the standard
               // MySQL order for clauses)

               // we use $seen_group and $seen_order because we are looking
               // for the BY
               if ($upper_data == 'GROUP') {
                   $seen_group = TRUE;
                   $seen_order = FALSE;
                   $in_having = FALSE;
                   $in_order_by = FALSE;
                   $in_where = FALSE;
                   $in_select_expr = FALSE;
                   $in_from = FALSE;
               }
               if ($upper_data == 'ORDER') {
                   $seen_order = TRUE;
                   $seen_group = FALSE;
                   $in_having = FALSE;
                   $in_group_by = FALSE;
                   $in_where = FALSE;
                   $in_select_expr = FALSE;
                   $in_from = FALSE;
               }
               if ($upper_data == 'HAVING') {
                   $in_having = TRUE;
                   $having_clause = '';
                   $seen_group = FALSE;
                   $seen_order = FALSE;
                   $in_group_by = FALSE;
                   $in_order_by = FALSE;
                   $in_where = FALSE;
                   $in_select_expr = FALSE;
                   $in_from = FALSE;
               }

               if ($upper_data == 'WHERE') {
                   $in_where = TRUE;
                   $where_clause = '';
                   $where_clause_identifiers = array();
                   $seen_group = FALSE;
                   $seen_order = FALSE;
                   $in_group_by = FALSE;
                   $in_order_by = FALSE;
                   $in_having = FALSE;
                   $in_select_expr = FALSE;
                   $in_from = FALSE;
               }

               if ($upper_data == 'BY') {
                   if ($seen_group) {
                       $in_group_by = TRUE;
                       $group_by_clause = '';
                   }
                   if ($seen_order) {
                       $in_order_by = TRUE;
                       $order_by_clause = '';
                   }
               }

               // if we find one of the words that could end the clause
               if (PMA_STR_binarySearchInArr($upper_data, $words_ending_clauses, $words_ending_clauses_cnt)) {

                   $in_group_by = FALSE;
                   $in_order_by = FALSE;
                   $in_having   = FALSE;
                   $in_where    = FALSE;
                   $in_select_expr = FALSE;
                   $in_from = FALSE;
               }

           } // endif (reservedWord)


           // do not add a blank after a function name

           $sep=' ';
           if ($arr[$i]['type'] == 'alpha_functionName') {
               $sep='';
           }

           if ($in_select_expr && $upper_data != 'SELECT' && $upper_data != 'DISTINCT') {
               $select_expr_clause .= $arr[$i]['data'] . $sep;
           }
           if ($in_from && $upper_data != 'FROM') {
               $from_clause .= $arr[$i]['data'] . $sep;
           }
           if ($in_group_by && $upper_data != 'GROUP' && $upper_data != 'BY') {
               $group_by_clause .= $arr[$i]['data'] . $sep;
           }
           if ($in_order_by && $upper_data != 'ORDER' && $upper_data != 'BY') {
               $order_by_clause .= $arr[$i]['data'] . $sep;
           }
           if ($in_having && $upper_data != 'HAVING') {
               $having_clause .= $arr[$i]['data'] . $sep;
           }
           if ($in_where && $upper_data != 'WHERE') {
               $where_clause .= $arr[$i]['data'] . $sep;

               if (($arr[$i]['type'] == 'quote_backtick')
                || ($arr[$i]['type'] == 'alpha_identifier')) {
                   $where_clause_identifiers[] = $arr[$i]['data'];
               }
           }

           // clear $upper_data for next iteration
           $upper_data='';

        } // end for $i (loop #2)

        // -----------------------------------------------------
        // loop #3: foreign keys
        // (for now, check only the first query)
        // (for now, identifiers must be backquoted)

        $seen_foreign = FALSE;
        $seen_references = FALSE;
        $seen_constraint = FALSE;
        $in_bracket = FALSE;
        $foreign_key_number = -1;

        for ($i = 0; $i < $size; $i++) {
        // DEBUG echo "<b>" . $arr[$i]['data'] . "</b> " . $arr[$i]['type'] . "<br>";
            if ($arr[$i]['type'] == 'alpha_reservedWord') {
               $upper_data = strtoupper($arr[$i]['data']);

               if ($upper_data == 'CONSTRAINT') {
                   $foreign_key_number++;
                   $seen_foreign = FALSE;
                   $seen_references = FALSE;
                   $seen_constraint = TRUE;
               }
               if ($upper_data == 'FOREIGN') {
                   $seen_foreign = TRUE;
                   $seen_references = FALSE;
                   $seen_constraint = FALSE;
               }
               if ($upper_data == 'REFERENCES') {
                   $seen_foreign = FALSE;
                   $seen_references = TRUE;
                   $seen_constraint = FALSE;
               }


              // [ON DELETE {CASCADE | SET NULL | NO ACTION | RESTRICT}]
              // [ON UPDATE {CASCADE | SET NULL | NO ACTION | RESTRICT}]

              // but we set ['on_delete'] or ['on_cascade'] to
              // CASCADE | SET_NULL | NO_ACTION | RESTRICT

               if ($upper_data == 'ON') {
                   unset($clause);
                   if ($arr[$i+1]['type'] == 'alpha_reservedWord') {
                       $second_upper_data = strtoupper($arr[$i+1]['data']);
                       if ($second_upper_data == 'DELETE') {
                            $clause = 'on_delete';
                       }
                       if ($second_upper_data == 'UPDATE') {
                            $clause = 'on_update';
                       }
                       if (isset($clause)
                       && ($arr[$i+2]['type'] == 'alpha_reservedWord'

             // ugly workaround because currently, NO is not
             // in the list of reserved words in sqlparser.data
             // (we got a bug report about not being able to use
             // 'no' as an identifier)
                           || ($arr[$i+2]['type'] == 'alpha_identifier'
                              && strtoupper($arr[$i+2]['data'])=='NO') )
                          ) {
                          $third_upper_data = strtoupper($arr[$i+2]['data']);
                          if ($third_upper_data == 'CASCADE'
                           || $third_upper_data == 'RESTRICT') {
                              $value = $third_upper_data;
                          } elseif ($third_upper_data == 'SET'
                                 || $third_upper_data == 'NO') {
                              if ($arr[$i+3]['type'] == 'alpha_reservedWord') {
                                  $value = $third_upper_data . '_' . strtoupper($arr[$i+3]['data']);
                              }
                          }
                          $foreign[$foreign_key_number][$clause] = $value;
                       }
                   }
               }

            }

            if ($arr[$i]['type'] == 'punct_bracket_open_round') {
                $in_bracket = TRUE;
            }

            if ($arr[$i]['type'] == 'punct_bracket_close_round') {
                $in_bracket = FALSE;
                if ($seen_references) {
                    $seen_references = FALSE;
                }
            }

            if (($arr[$i]['type'] == 'quote_backtick')) {

                if ($seen_constraint) {
                    // remove backquotes
                    $identifier = str_replace('`','',$arr[$i]['data']);
                    $foreign[$foreign_key_number]['constraint'] = $identifier;
                }
                if ($seen_foreign && $in_bracket) {
                    // remove backquotes
                    $identifier = str_replace('`','',$arr[$i]['data']);
                    $foreign[$foreign_key_number]['index_list'][] = $identifier;
                }

                if ($seen_references) {
                    $identifier = str_replace('`','',$arr[$i]['data']);
                    if ($in_bracket) {
                        $foreign[$foreign_key_number]['ref_index_list'][] = $identifier;
                    } else {
                        // identifier can be table or db.table
                        $db_table = explode('.',$identifier);
                        if (isset($db_table[1])) {
                            $foreign[$foreign_key_number]['ref_db_name'] = $db_table[0];
                            $foreign[$foreign_key_number]['ref_table_name'] = $db_table[1];
                        } else {
                            $foreign[$foreign_key_number]['ref_table_name'] = $db_table[0];
                        }
                    }
                }
            }
        } // end for $i (loop #3)

        if (isset($foreign)) {
            $subresult['foreign_keys'] = $foreign;
        }
        //DEBUG print_r($foreign);

        if (isset($select_expr_clause)) {
            $subresult['select_expr_clause'] = $select_expr_clause;
        }
        if (isset($from_clause)) {
            $subresult['from_clause'] = $from_clause;
        }
        if (isset($group_by_clause)) {
            $subresult['group_by_clause'] = $group_by_clause;
        }
        if (isset($order_by_clause)) {
            $subresult['order_by_clause'] = $order_by_clause;
        }
        if (isset($having_clause)) {
            $subresult['having_clause'] = $having_clause;
        }
        if (isset($where_clause)) {
            $subresult['where_clause'] = $where_clause;
        }
        if (isset($where_clause_identifiers)) {
            $subresult['where_clause_identifiers'] = $where_clause_identifiers;
        }

        if (isset($position_of_first_select)) {
            $subresult['position_of_first_select'] = $position_of_first_select;
        }


        // They are naughty and didn't have a trailing semi-colon,
        // then still handle it properly
        if ($subresult['querytype'] != '') {
            $result[] = $subresult;
        }
        return $result;
    } // end of the "PMA_SQP_analyze()" function


    /**
     * Colorizes SQL queries html formatted
     *
     * @param  array   The SQL queries html formatted
     *
     * @return array   The colorized SQL queries
     *
     * @access public
     */
    function PMA_SQP_formatHtml_colorize($arr)
    {
        $i         = $GLOBALS['PMA_strpos']($arr['type'], '_');
        $class     = '';
        if ($i > 0) {
            $class = 'syntax_' . $GLOBALS['PMA_substr']($arr['type'], 0, $i) . ' ';
        }

        $class     .= 'syntax_' . $arr['type'];

        //TODO: check why adding a "\n" after the </span> would cause extra
        //      blanks to be displayed:
        //      SELECT p . person_name

        return '<span class="' . $class . '">' . htmlspecialchars($arr['data']) . '</span>';
    } // end of the "PMA_SQP_formatHtml_colorize()" function


    /**
     * Formats SQL queries to html
     *
     * @param  array   The SQL queries
     * @param  string  mode
     * @param  integer starting token
     * @param  integer number of tokens to format, -1 = all
     *
     * @return string  The formatted SQL queries
     *
     * @access public
     */
    function PMA_SQP_formatHtml($arr, $mode='color', $start_token=0,
        $number_of_tokens=-1)
    {
        // first check for the SQL parser having hit an error
        if (PMA_SQP_isError()) {
            return $arr;
        }
        // then check for an array
        if (!is_array($arr)) {
            return $arr;
        }
        // else do it properly
        switch ($mode) {
            case 'color':
                $str                                = '<span class="syntax">';
                $html_line_break                    = '<br />';
                break;
            case 'query_only':
                $str                                = '';
                $html_line_break                    = "\n";
                break;
            case 'text':
                $str                                = '';
                $html_line_break                    = '<br />';
                break;
        } // end switch
        $indent                                     = 0;
        $bracketlevel                               = 0;
        $functionlevel                              = 0;
        $infunction                                 = FALSE;
        $space_punct_listsep                        = ' ';
        $space_punct_listsep_function_name          = ' ';
        // $space_alpha_reserved_word = '<br />'."\n";
        $space_alpha_reserved_word                  = ' ';

        $keywords_with_brackets_1before            = array(
            'INDEX',
            'KEY',
            'ON',
            'USING'
        );
        $keywords_with_brackets_1before_cnt        = 4;

        $keywords_with_brackets_2before            = array(
            'IGNORE',
            'INDEX',
            'INTO',
            'KEY',
            'PRIMARY',
            'PROCEDURE',
            'REFERENCES',
            'UNIQUE',
            'USE'
        );
        // $keywords_with_brackets_2before_cnt = count($keywords_with_brackets_2before);
        $keywords_with_brackets_2before_cnt        = 9;

        // These reserved words do NOT get a newline placed near them.
        $keywords_no_newline               = array(
            'AND',
            'AS',
            'ASC',
            'DESC',
            'DISTINCT',
            'HOUR',
            'INTERVAL',
            'IS',
            'NOT',
            'NULL',
            'ON',
            'OR'
        );
        $keywords_no_newline_cnt           = 12;

        // These reserved words introduce a privilege list
        $keywords_priv_list                = array(
            'GRANT',
            'REVOKE'
        );
        $keywords_priv_list_cnt            = 2;

        if ($number_of_tokens == -1) {
            $arraysize = $arr['len'];
        } else {
            $arraysize = $number_of_tokens;
        }
        $typearr   = array();
        if ($arraysize >= 0) {
            $typearr[0] = '';
            $typearr[1] = '';
            $typearr[2] = '';
            //$typearr[3] = $arr[0]['type'];
            $typearr[3] = $arr[$start_token]['type'];
        }

        $in_priv_list = FALSE;
        for ($i = $start_token; $i < $arraysize; $i++) {
// DEBUG echo "<b>" . $arr[$i]['data'] . "</b> " . $arr[$i]['type'] . "<br />";
            $before = '';
            $after  = '';
            $indent = 0;
            // array_shift($typearr);
            /*
            0 prev2
            1 prev
            2 current
            3 next
            */
            if (($i + 1) < $arraysize) {
                // array_push($typearr, $arr[$i + 1]['type']);
                $typearr[4] = $arr[$i + 1]['type'];
            } else {
                //array_push($typearr, NULL);
                $typearr[4] = '';
            }

            for ($j=0; $j<4; $j++) {
                $typearr[$j] = $typearr[$j + 1];
            }

            switch ($typearr[2]) {
                case 'white_newline':
//                    $after      = '<br />';
                    $before     = '';
                    break;
                case 'punct_bracket_open_round':
                    $bracketlevel++;
                    $infunction = FALSE;
                    // Make sure this array is sorted!
                    if (($typearr[1] == 'alpha_functionName') || ($typearr[1] == 'alpha_columnType') || ($typearr[1] == 'punct')
                        || ($typearr[3] == 'digit_integer') || ($typearr[3] == 'digit_hex') || ($typearr[3] == 'digit_float')
                        || (($typearr[0] == 'alpha_reservedWord')
                            && PMA_STR_binarySearchInArr(strtoupper($arr[$i - 2]['data']), $keywords_with_brackets_2before, $keywords_with_brackets_2before_cnt))
                        || (($typearr[1] == 'alpha_reservedWord')
                            && PMA_STR_binarySearchInArr(strtoupper($arr[$i - 1]['data']), $keywords_with_brackets_1before, $keywords_with_brackets_1before_cnt))
                        ) {
                        $functionlevel++;
                        $infunction = TRUE;
                        $after      .= ' ';
                    } else {
                        $indent++;
                        $after      .= ($mode != 'query_only' ? '<div class="syntax_indent' . $indent . '">' : ' ');
                    }
                    break;
                case 'alpha_identifier':
                    if (($typearr[1] == 'punct_qualifier') || ($typearr[3] == 'punct_qualifier')) {
                        $after      = '';
                        $before     = '';
                    }
                    if (($typearr[3] == 'alpha_columnType') || ($typearr[3] == 'alpha_identifier')) {
                        $after      .= ' ';
                    }
                    break;
                case 'punct_qualifier':
                    $before         = '';
                    $after          = '';
                    break;
                case 'punct_listsep':
                    if ($infunction == TRUE) {
                        $after      .= $space_punct_listsep_function_name;
                    } else {
                        $after      .= $space_punct_listsep;
                    }
                    break;
                case 'punct_queryend':
                    if (($typearr[3] != 'comment_mysql') && ($typearr[3] != 'comment_ansi') && $typearr[3] != 'comment_c') {
                        $after     .= $html_line_break;
                        $after     .= $html_line_break;
                    }
                    $space_punct_listsep               = ' ';
                    $space_punct_listsep_function_name = ' ';
                    $space_alpha_reserved_word         = ' ';
                    $in_priv_list                      = FALSE;
                    break;
                case 'comment_mysql':
                case 'comment_ansi':
                    $after         .= $html_line_break;
                    break;
                case 'punct':
                    $before         .= ' ';
                    // workaround for
                    // select * from mytable limit 0,-1
                    // (a side effect of this workaround is that
                    // select 20 - 9
                    // becomes
                    // select 20 -9
                    // )
                    if ($typearr[3] != 'digit_integer') {
                       $after        .= ' ';
                    }
                    break;
                case 'punct_bracket_close_round':
                    $bracketlevel--;
                    if ($infunction == TRUE) {
                        $functionlevel--;
                        $after     .= ' ';
                        $before    .= ' ';
                    } else {
                        $indent--;
                        $before    .= ($mode != 'query_only' ? '</div>' : ' ');
                    }
                    $infunction    = ($functionlevel > 0) ? TRUE : FALSE;
                    break;
                case 'alpha_columnType':
                    if ($typearr[3] == 'alpha_columnAttrib') {
                        $after     .= ' ';
                    }
                    if ($typearr[1] == 'alpha_columnType') {
                        $before    .= ' ';
                    }
                    break;
                case 'alpha_columnAttrib':

                    // ALTER TABLE tbl_name AUTO_INCREMENT = 1
                    if ($typearr[1] == 'alpha_identifier') {
                        $before .= ' ';
                    }
                    if (($typearr[3] == 'alpha_columnAttrib') || ($typearr[3] == 'quote_single') || ($typearr[3] == 'digit_integer')) {
                        $after     .= ' ';
                    }
                    // workaround for
                    // select * from mysql.user where binary user="root"
                    // binary is marked as alpha_columnAttrib
                    // but should be marked as a reserved word
                    if (strtoupper($arr[$i]['data']) == 'BINARY'
                      && $typearr[3] == 'alpha_identifier') {
                        $after     .= ' ';
                    }
                    break;
                case 'alpha_reservedWord':
                    $arr[$i]['data'] = strtoupper($arr[$i]['data']);
                    if ((($typearr[1] != 'alpha_reservedWord')
                        || (($typearr[1] == 'alpha_reservedWord')
                            && PMA_STR_binarySearchInArr(strtoupper($arr[$i - 1]['data']), $keywords_no_newline, $keywords_no_newline_cnt)))
                        && ($typearr[1] != 'punct_level_plus')
                        && (!PMA_STR_binarySearchInArr($arr[$i]['data'], $keywords_no_newline, $keywords_no_newline_cnt))) {
                        // do not put a space before the first token, because
                        // we use a lot of eregi() checking for the first
                        // reserved word at beginning of query
                        // so do not put a newline before
                        //
                        // also we must not be inside a privilege list
                        if ($i > 0) {
                            if (!$in_priv_list) {
                                $before    .= $space_alpha_reserved_word;
                            }
                        } else {
                        // on first keyword, check if it introduces a
                        // privilege list
                            if (PMA_STR_binarySearchInArr($arr[$i]['data'], $keywords_priv_list, $keywords_priv_list_cnt)) {
                                $in_priv_list = TRUE;
                            }
                        }
                    } else {
                        $before    .= ' ';
                    }

                    switch ($arr[$i]['data']) {
                        case 'CREATE':
                            if (!$in_priv_list) {
                                $space_punct_listsep       = $html_line_break;
                                $space_alpha_reserved_word = ' ';
                            }
                            break;
                        case 'EXPLAIN':
                        case 'DESCRIBE':
                        case 'SET':
                        case 'ALTER':
                        case 'DELETE':
                        case 'SHOW':
                        case 'DROP':
                        case 'UPDATE':
                        case 'TRUNCATE':
                        case 'ANALYZE':
                        case 'ANALYSE':
                            if (!$in_priv_list) {
                                $space_punct_listsep       = $html_line_break;
                                $space_alpha_reserved_word = ' ';
                            }
                            break;
                        case 'INSERT':
                        case 'REPLACE':
                            if (!$in_priv_list) {
                                $space_punct_listsep       = $html_line_break;
                                $space_alpha_reserved_word = $html_line_break;
                            }
                            break;
                        case 'VALUES':
                            $space_punct_listsep       = ' ';
                            $space_alpha_reserved_word = $html_line_break;
                            break;
                        case 'SELECT':
                            $space_punct_listsep       = ' ';
                            $space_alpha_reserved_word = $html_line_break;
                            break;
                        default:
                            break;
                    } // end switch ($arr[$i]['data'])

                    $after         .= ' ';
                    break;
                case 'digit_integer':
                case 'digit_float':
                case 'digit_hex':
                    //TODO: could there be other types preceding a digit?
                    if ($typearr[1] == 'alpha_reservedWord') {
                        $after .= ' ';
                    }
                    if ($infunction && $typearr[3] == 'punct_bracket_close_round') {
                        $after     .= ' ';
                    }
                    break;
                case 'alpha_variable':
                    $after      = ' ';
                    break;
                case 'quote_double':
                case 'quote_single':
                    // workaround: for the query
                    // REVOKE SELECT ON `base2\_db`.* FROM 'user'@'%'
                    // the @ is incorrectly marked as alpha_variable
                    // in the parser, and here, the '%' gets a blank before,
                    // which is a syntax error
                    if ($typearr[1]!='alpha_variable') {
                        $before        .= ' ';
                    }
                    if ($infunction && $typearr[3] == 'punct_bracket_close_round') {
                        $after     .= ' ';
                    }
                    break;
                case 'quote_backtick':
                    if ($typearr[3] != 'punct_qualifier') {
                        $after     .= ' ';
                    }
                    if ($typearr[1] != 'punct_qualifier') {
                        $before    .= ' ';
                    }
                    break;
                default:
                    break;
            } // end switch ($typearr[2])

/*
            if ($typearr[3] != 'punct_qualifier') {
                $after             .= ' ';
            }
            $after                 .= "\n";
*/
            $str .= $before . ($mode=='color' ? PMA_SQP_formatHTML_colorize($arr[$i]) : $arr[$i]['data']). $after;
        } // end for
        if ($mode=='color') {
            $str .= '</span>';
        }

        return $str;
    } // end of the "PMA_SQP_formatHtml()" function
}

/**
 * Builds a CSS rule used for html formatted SQL queries
 *
 * @param  string  The class name
 * @param  string  The property name
 * @param  string  The property value
 *
 * @return string  The CSS rule
 *
 * @access public
 *
 * @see    PMA_SQP_buildCssData()
 */
function PMA_SQP_buildCssRule($classname, $property, $value)
{
    $str     = '.' . $classname . ' {';
    if ($value != '') {
        $str .= $property . ': ' . $value . ';';
    }
    $str     .= '}' . "\n";

    return $str;
} // end of the "PMA_SQP_buildCssRule()" function


/**
 * Builds CSS rules used for html formatted SQL queries
 *
 * @return string  The CSS rules set
 *
 * @access public
 *
 * @global array   The current PMA configuration
 *
 * @see    PMA_SQP_buildCssRule()
 */
function PMA_SQP_buildCssData()
{
    global $cfg;

    $css_string     = '';
    foreach($cfg['SQP']['fmtColor'] AS $key => $col) {
        $css_string .= PMA_SQP_buildCssRule('syntax_' . $key, 'color', $col);
    }

    for ($i = 0; $i < 8; $i++) {
        $css_string .= PMA_SQP_buildCssRule('syntax_indent' . $i, 'margin-left', ($i * $cfg['SQP']['fmtInd']) . $cfg['SQP']['fmtIndUnit']);
    }

    return $css_string;
} // end of the "PMA_SQP_buildCssData()" function

if ($is_minimum_common == FALSE) {
    /**
     * Gets SQL queries with no format
     *
     * @param  array   The SQL queries list
     *
     * @return string  The SQL queries with no format
     *
     * @access public
     */
    function PMA_SQP_formatNone($arr)
    {
        $formatted_sql = htmlspecialchars($arr['raw']);
        $formatted_sql = preg_replace("@((\015\012)|(\015)|(\012)){3,}@", "\n\n", $formatted_sql);

        return $formatted_sql;
    } // end of the "PMA_SQP_formatNone()" function


    /**
     * Gets SQL queries in text format
     *
     * @param  array   The SQL queries list
     *
     * @return string  The SQL queries in text format
     *
     * @access public
     */
    function PMA_SQP_formatText($arr)
    {
        /**
         * TODO WRITE THIS!
         */
         return PMA_SQP_formatNone($arr);
    } // end of the "PMA_SQP_formatText()" function
} // end if: minimal common.lib needed?

?>
