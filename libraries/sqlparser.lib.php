<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
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
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Minimum inclusion? (i.e. for the stylesheet builder)
 */
if (! defined('PMA_MINIMUM_COMMON')) {
    /**
     * Include the string library as we use it heavily
     */
    require_once './libraries/string.lib.php';

    /**
     * Include data for the SQL Parser
     */
    require_once './libraries/sqlparser.data.php';
    require_once './libraries/mysql_charsets.lib.php';
    if (!isset($mysql_charsets)) {
        $mysql_charsets = array();
        $mysql_charsets_count = 0;
        $mysql_collations_flat = array();
        $mysql_collations_count = 0;
    }

    if (!defined('DEBUG_TIMING')) {
        // currently we don't need the $pos (token position in query)
        // for other purposes than LIMIT clause verification,
        // so many calls to this function do not include the 4th parameter
        function PMA_SQP_arrayAdd(&$arr, $type, $data, &$arrsize, $pos = 0)
        {
            $arr[] = array('type' => $type, 'data' => $data, 'pos' => $pos);
            $arrsize++;
        } // end of the "PMA_SQP_arrayAdd()" function
    } else {
        function PMA_SQP_arrayAdd(&$arr, $type, $data, &$arrsize, $pos = 0)
        {
            global $timer;

            $t     = $timer;
            $arr[] = array('type' => $type, 'data' => $data, 'pos' => $pos, 'time' => $t);
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
    function PMA_SQP_resetError()
    {
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
    function PMA_SQP_getErrorString()
    {
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
    function PMA_SQP_isError()
    {
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
        $debugstr .= 'SVN: $Id$' . "\n";
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
     * @global array    List of available character sets
     * @global array    List of available collations
     * @global integer  Character sets count
     * @global integer  Collations count
     *
     * @access public
     */
    function PMA_SQP_parse($sql)
    {
        global $cfg;
        global $PMA_SQPdata_column_attrib, $PMA_SQPdata_reserved_word, $PMA_SQPdata_column_type, $PMA_SQPdata_function_name,
               $PMA_SQPdata_column_attrib_cnt, $PMA_SQPdata_reserved_word_cnt, $PMA_SQPdata_column_type_cnt, $PMA_SQPdata_function_name_cnt;
        global $mysql_charsets, $mysql_collations_flat, $mysql_charsets_count, $mysql_collations_count;
        global $PMA_SQPdata_forbidden_word, $PMA_SQPdata_forbidden_word_cnt;

        // rabus: Convert all line feeds to Unix style
        $sql = str_replace("\r\n", "\n", $sql);
        $sql = str_replace("\r", "\n", $sql);

        $len = PMA_strlen($sql);
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
        $punct_user              = '@';
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

        $previous_was_space   = false;
        $this_was_space       = false;
        $previous_was_bracket = false;
        $this_was_bracket     = false;
        $previous_was_punct   = false;
        $this_was_punct       = false;
        $previous_was_listsep = false;
        $this_was_listsep     = false;
        $previous_was_quote   = false;
        $this_was_quote       = false;

        while ($count2 < $len) {
            $c      = PMA_substr($sql, $count2, 1);
            $count1 = $count2;

            $previous_was_space = $this_was_space;
            $this_was_space = false;
            $previous_was_bracket = $this_was_bracket;
            $this_was_bracket = false;
            $previous_was_punct = $this_was_punct;
            $this_was_punct = false;
            $previous_was_listsep = $this_was_listsep;
            $this_was_listsep = false;
            $previous_was_quote = $this_was_quote;
            $this_was_quote = false;

            if (($c == "\n")) {
                $this_was_space = true;
                $count2++;
                PMA_SQP_arrayAdd($sql_array, 'white_newline', '', $arraysize);
                continue;
            }

            // Checks for white space
            if (PMA_STR_isSpace($c)) {
                $this_was_space = true;
                $count2++;
                continue;
            }

            // Checks for comment lines.
            // MySQL style #
            // C style /* */
            // ANSI style --
            if (($c == '#')
                || (($count2 + 1 < $len) && ($c == '/') && (PMA_substr($sql, $count2 + 1, 1) == '*'))
                || (($count2 + 2 == $len) && ($c == '-') && (PMA_substr($sql, $count2 + 1, 1) == '-'))
                || (($count2 + 2 < $len) && ($c == '-') && (PMA_substr($sql, $count2 + 1, 1) == '-') && ((PMA_substr($sql, $count2 + 2, 1) <= ' ')))) {
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
                $str    = PMA_substr($sql, $count1, $count2 - $count1);
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
                                  . 'STR: ' . htmlspecialchars($quotetype);
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
                    } elseif (($pos + 1 < $len) && (PMA_substr($sql, $pos, 1) == $quotetype) && (PMA_substr($sql, $pos + 1, 1) == $quotetype)) {
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
                        $this_was_quote = true;
                        break;
                    case '"':
                        $type .= 'double';
                        $this_was_quote = true;
                        break;
                    case '`':
                        $type .= 'backtick';
                        $this_was_quote = true;
                        break;
                    default:
                        break;
                } // end switch
                $data = PMA_substr($sql, $count1, $count2 - $count1);
                PMA_SQP_arrayAdd($sql_array, $type, $data, $arraysize);
                continue;
            }

            // Checks for brackets
            if (PMA_STR_strInStr($c, $bracket_list)) {
                // All bracket tokens are only one item long
                $this_was_bracket = true;
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

            /* DEBUG
            echo '<pre>1';
            var_dump(PMA_STR_isSqlIdentifier($c, false));
            var_dump($c == '@');
            var_dump($c == '.');
            var_dump(PMA_STR_isDigit(PMA_substr($sql, $count2 + 1, 1)));
            var_dump($previous_was_space);
            var_dump($previous_was_bracket);
            var_dump($previous_was_listsep);
            echo '</pre>';
            */

            // Checks for identifier (alpha or numeric)
            if (PMA_STR_isSqlIdentifier($c, false)
             || $c == '@'
             || ($c == '.'
              && PMA_STR_isDigit(PMA_substr($sql, $count2 + 1, 1))
              && ($previous_was_space || $previous_was_bracket || $previous_was_listsep))) {

                /* DEBUG
                echo PMA_substr($sql, $count2);
                echo '<hr />';
                */

                $count2++;

                /**
                 * @todo a @ can also be present in expressions like
                 * FROM 'user'@'%' or  TO 'user'@'%'
                 * in this case, the @ is wrongly marked as alpha_variable
                 */
                $is_identifier           = $previous_was_punct;
                $is_sql_variable         = $c == '@' && ! $previous_was_quote;
                $is_user                 = $c == '@' && $previous_was_quote;
                $is_digit                = !$is_identifier && !$is_sql_variable && PMA_STR_isDigit($c);
                $is_hex_digit            = $is_digit && $c == '0' && $count2 < $len && PMA_substr($sql, $count2, 1) == 'x';
                $is_float_digit          = $c == '.';
                $is_float_digit_exponent = FALSE;

                /* DEBUG
                echo '<pre>2';
                var_dump($is_identifier);
                var_dump($is_sql_variable);
                var_dump($is_digit);
                var_dump($is_float_digit);
                echo '</pre>';
                 */

                // Nijel: Fast skip is especially needed for huge BLOB data, requires PHP at least 4.3.0:
                if (PMA_PHP_INT_VERSION >= 40300) {
                    if ($is_hex_digit) {
                        $count2++;
                        $pos = strspn($sql, '0123456789abcdefABCDEF', $count2);
                        if ($pos > $count2) {
                            $count2 = $pos;
                        }
                        unset($pos);
                    } elseif ($is_digit) {
                        $pos = strspn($sql, '0123456789', $count2);
                        if ($pos > $count2) {
                            $count2 = $pos;
                        }
                        unset($pos);
                    }
                }

                while (($count2 < $len) && PMA_STR_isSqlIdentifier(PMA_substr($sql, $count2, 1), ($is_sql_variable || $is_digit))) {
                    $c2 = PMA_substr($sql, $count2, 1);
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
                                      . 'STR: ' . htmlspecialchars(PMA_substr($sql, $count1, $count2 - $count1));
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
                $str  = PMA_substr($sql, $count1, $l);

                $type = '';
                if ($is_digit || $is_float_digit || $is_hex_digit) {
                    $type     = 'digit';
                    if ($is_float_digit) {
                        $type .= '_float';
                    } elseif ($is_hex_digit) {
                        $type .= '_hex';
                    } else {
                        $type .= '_integer';
                    }
                } elseif ($is_user) {
                    $type = 'punct_user';
                } elseif ($is_sql_variable != FALSE) {
                    $type = 'alpha_variable';
                } else {
                    $type = 'alpha';
                } // end if... else....
                PMA_SQP_arrayAdd($sql_array, $type, $str, $arraysize, $count2);

                continue;
            }

            // Checks for punct
            if (PMA_STR_strInStr($c, $allpunct_list)) {
                while (($count2 < $len) && PMA_STR_strInStr(PMA_substr($sql, $count2, 1), $allpunct_list)) {
                    $count2++;
                }
                $l = $count2 - $count1;
                if ($l == 1) {
                    $punct_data = $c;
                } else {
                    $punct_data = PMA_substr($sql, $count1, $l);
                }

                // Special case, sometimes, althought two characters are
                // adjectent directly, they ACTUALLY need to be seperate
                /* DEBUG
                echo '<pre>';
                var_dump($l);
                var_dump($punct_data);
                echo '</pre>';
                */

                if ($l == 1) {
                    $t_suffix         = '';
                    switch ($punct_data) {
                        case $punct_queryend:
                            $t_suffix = '_queryend';
                            break;
                        case $punct_qualifier:
                            $t_suffix = '_qualifier';
                            $this_was_punct = true;
                            break;
                        case $punct_listsep:
                            $this_was_listsep = true;
                            $t_suffix = '_listsep';
                            break;
                        default:
                            break;
                    }
                    PMA_SQP_arrayAdd($sql_array, 'punct' . $t_suffix, $punct_data, $arraysize);
                } elseif (PMA_STR_binarySearchInArr($punct_data, $allpunct_list_pair, $allpunct_list_pair_size)) {
                    // Ok, we have one of the valid combined punct expressions
                    PMA_SQP_arrayAdd($sql_array, 'punct', $punct_data, $arraysize);
                } else {
                    // Bad luck, lets split it up more
                    $first  = $punct_data[0];
                    $first2 = $punct_data[0] . $punct_data[1];
                    $last2  = $punct_data[$l - 2] . $punct_data[$l - 1];
                    $last   = $punct_data[$l - 1];
                    if (($first == ',') || ($first == ';') || ($first == '.') || ($first == '*')) {
                        $count2     = $count1 + 1;
                        $punct_data = $first;
                    } elseif (($last2 == '/*') || (($last2 == '--') && ($count2 == $len || PMA_substr($sql, $count2, 1) <= ' '))) {
                        $count2     -= 2;
                        $punct_data = PMA_substr($sql, $count1, $count2 - $count1);
                    } elseif (($last == '-') || ($last == '+') || ($last == '!')) {
                        $count2--;
                        $punct_data = PMA_substr($sql, $count1, $count2 - $count1);
                    /**
                     * @todo for negation operator, split in 2 tokens ?
                     * "select x&~1 from t"
                     * becomes "select x & ~ 1 from t" ?
                     */

                    } elseif ($last != '~') {
                        $debugstr =  $GLOBALS['strSQPBugUnknownPunctuation'] . ' @ ' . ($count1+1) . "\n"
                                  . 'STR: ' . htmlspecialchars($punct_data);
                        PMA_SQP_throwError($debugstr, $sql);
                        return $sql;
                    }
                    PMA_SQP_arrayAdd($sql_array, 'punct', $punct_data, $arraysize);
                    continue;
                } // end if... elseif... else
                continue;
            }

            // DEBUG
            $count2++;

            $debugstr = 'C1 C2 LEN: ' . $count1 . ' ' . $count2 . ' ' . $len .  "\n"
                      . 'STR: ' . PMA_substr($sql, $count1, $count2 - $count1) . "\n";
            PMA_SQP_bug($debugstr, $sql);
            return $sql;

        } // end while ($count2 < $len)

        /*
        echo '<pre>';
        print_r($sql_array);
        echo '</pre>';
        */

        if ($arraysize > 0) {
            $t_next           = $sql_array[0]['type'];
            $t_prev           = '';
            $t_bef_prev       = '';
            $t_cur            = '';
            $d_next           = $sql_array[0]['data'];
            $d_prev           = '';
            $d_bef_prev       = '';
            $d_cur            = '';
            $d_next_upper     = $t_next == 'alpha' ? strtoupper($d_next) : $d_next;
            $d_prev_upper     = '';
            $d_bef_prev_upper = '';
            $d_cur_upper      = '';
        }

        for ($i = 0; $i < $arraysize; $i++) {
            $t_bef_prev       = $t_prev;
            $t_prev           = $t_cur;
            $t_cur            = $t_next;
            $d_bef_prev       = $d_prev;
            $d_prev           = $d_cur;
            $d_cur            = $d_next;
            $d_bef_prev_upper = $d_prev_upper;
            $d_prev_upper     = $d_cur_upper;
            $d_cur_upper      = $d_next_upper;
            if (($i + 1) < $arraysize) {
                $t_next = $sql_array[$i + 1]['type'];
                $d_next = $sql_array[$i + 1]['data'];
                $d_next_upper = $t_next == 'alpha' ? strtoupper($d_next) : $d_next;
            } else {
                $t_next       = '';
                $d_next       = '';
                $d_next_upper = '';
            }

            //DEBUG echo "[prev: <b>".$d_prev."</b> ".$t_prev."][cur: <b>".$d_cur."</b> ".$t_cur."][next: <b>".$d_next."</b> ".$t_next."]<br />";

            if ($t_cur == 'alpha') {
                $t_suffix     = '_identifier';
                if (($t_next == 'punct_qualifier') || ($t_prev == 'punct_qualifier')) {
                    $t_suffix = '_identifier';
                } elseif (($t_next == 'punct_bracket_open_round')
                  && PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_function_name, $PMA_SQPdata_function_name_cnt)) {
                    /**
                     * @todo 2005-10-16: in the case of a CREATE TABLE containing
                     * a TIMESTAMP, since TIMESTAMP() is also a function, it's
                     * found here and the token is wrongly marked as alpha_functionName.
                     * But we compensate for this when analysing for timestamp_not_null
                     * later in this script.
                     *
                     * Same applies to CHAR vs. CHAR() function.
                     */
                    $t_suffix = '_functionName';
                    /* There are functions which might be as well column types */
                    if (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_column_type, $PMA_SQPdata_column_type_cnt)) {
                    }
                } elseif (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_column_type, $PMA_SQPdata_column_type_cnt)) {
                    $t_suffix = '_columnType';

                    /**
                     * Temporary fix for BUG #621357
                     *
                     * @todo FIX PROPERLY NEEDS OVERHAUL OF SQL TOKENIZER
                     */
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

                } elseif (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_reserved_word, $PMA_SQPdata_reserved_word_cnt)) {
                    $t_suffix = '_reservedWord';
                } elseif (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_column_attrib, $PMA_SQPdata_column_attrib_cnt)) {
                    $t_suffix = '_columnAttrib';
                    // INNODB is a MySQL table type, but in "SHOW INNODB STATUS",
                    // it should be regarded as a reserved word.
                    if ($d_cur_upper == 'INNODB' && $d_prev_upper == 'SHOW' && $d_next_upper == 'STATUS') {
                        $t_suffix = '_reservedWord';
                    }

                    if ($d_cur_upper == 'DEFAULT' && $d_next_upper == 'CHARACTER') {
                        $t_suffix = '_reservedWord';
                    }
                    // Binary as character set
                    if ($d_cur_upper == 'BINARY' && (
                      ($d_bef_prev_upper == 'CHARACTER' && $d_prev_upper == 'SET')
                      || ($d_bef_prev_upper == 'SET' && $d_prev_upper == '=')
                      || ($d_bef_prev_upper == 'CHARSET' && $d_prev_upper == '=')
                      || $d_prev_upper == 'CHARSET'
                      ) && PMA_STR_binarySearchInArr($d_cur, $mysql_charsets, count($mysql_charsets))) {
                        $t_suffix = '_charset';
                    }
                } elseif (PMA_STR_binarySearchInArr($d_cur, $mysql_charsets, $mysql_charsets_count)
                  || PMA_STR_binarySearchInArr($d_cur, $mysql_collations_flat, $mysql_collations_count)
                  || ($d_cur{0} == '_' && PMA_STR_binarySearchInArr(substr($d_cur, 1), $mysql_charsets, $mysql_charsets_count))) {
                    $t_suffix = '_charset';
                } else {
                    // Do nothing
                }
                // check if present in the list of forbidden words
                if ($t_suffix == '_reservedWord' && PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_forbidden_word, $PMA_SQPdata_forbidden_word_cnt)) {
                    $sql_array[$i]['forbidden'] = TRUE;
                } else {
                    $sql_array[$i]['forbidden'] = FALSE;
                }
                $sql_array[$i]['type'] .= $t_suffix;
            }
        } // end for

        // Stores the size of the array inside the array, as count() is a slow
        // operation.
        $sql_array['len'] = $arraysize;

        // DEBUG echo 'After parsing<pre>'; print_r($sql_array); echo '</pre>';
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
        if (strcmp($whatWeWant, $toCheck) == 0) {
            return TRUE;
        } else {
            if (strpos($whatWeWant, $typeSeperator) === FALSE) {
                return strncmp($whatWeWant, $toCheck, strpos($toCheck, $typeSeperator)) == 0;
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
        if ($arr == array()) {
            return array();
        }
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
            'limit_clause'   => '',
            'where_clause'   => '',
            'where_clause_identifiers'   => array(),
            'unsorted_query' => '',
            'queryflags'     => array(),
            'select_expr'    => array(),
            'table_ref'      => array(),
            'foreign_keys'   => array(),
            'create_table_fields' => array()
        );
        $subresult_empty = $subresult;
        $seek_queryend         = FALSE;
        $seen_end_of_table_ref = FALSE;
        $number_of_brackets_in_extract = 0;
        $number_of_brackets_in_group_concat = 0;

        $number_of_brackets = 0;
        $in_subquery = false;
        $seen_subquery = false;
        $seen_from = false;

        // for SELECT EXTRACT(YEAR_MONTH FROM CURDATE())
        // we must not use CURDATE as a table_ref
        // so we track wether we are in the EXTRACT()
        $in_extract          = FALSE;

        // for GROUP_CONCAT(...)
        $in_group_concat     = FALSE;

/* Description of analyzer results by lem9
 *
 * db, table, column, alias
 * ------------------------
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
 * queryflags
 * ----------
 *
 * In $subresult, array 'queryflags' is filled, according to what we
 * find in the query.
 *
 * Currently, those are generated:
 *
 * ['queryflags']['need_confirm'] = 1; if the query needs confirmation
 * ['queryflags']['select_from'] = 1;  if this is a real SELECT...FROM
 * ['queryflags']['distinct'] = 1;     for a DISTINCT
 * ['queryflags']['union'] = 1;        for a UNION
 * ['queryflags']['join'] = 1;         for a JOIN
 * ['queryflags']['offset'] = 1;       for the presence of OFFSET
 * ['queryflags']['procedure'] = 1;    for the presence of PROCEDURE
 *
 * query clauses
 * -------------
 *
 * The select is splitted in those clauses:
 * ['select_expr_clause']
 * ['from_clause']
 * ['group_by_clause']
 * ['order_by_clause']
 * ['having_clause']
 * ['where_clause']
 * ['limit_clause']
 *
 * The identifiers of the WHERE clause are put into the array
 * ['where_clause_identifier']
 *
 * For a SELECT, the whole query without the ORDER BY clause is put into
 * ['unsorted_query']
 *
 * foreign keys
 * ------------
 * The CREATE TABLE may contain FOREIGN KEY clauses, so they get
 * analyzed and ['foreign_keys'] is an array filled with
 * the constraint name, the index list,
 * the REFERENCES table name and REFERENCES index list,
 * and ON UPDATE | ON DELETE clauses
 *
 * position_of_first_select
 * ------------------------
 *
 * The array index of the first SELECT we find. Will be used to
 * insert a SQL_CALC_FOUND_ROWS.
 *
 * create_table_fields
 * -------------------
 *
 * For now, mostly used to detect the DEFAULT CURRENT_TIMESTAMP and
 * ON UPDATE CURRENT_TIMESTAMP clauses of the CREATE TABLE query.
 * An array, each element is the identifier name.
 * Note that for now, the timestamp_not_null element is created
 * even for non-TIMESTAMP fields.
 *
 * Sub-elements: ['type'] which contains the column type
 *               optional (currently they are never false but can be absent):
 *               ['default_current_timestamp'] boolean
 *               ['on_update_current_timestamp'] boolean
 *               ['timestamp_not_null'] boolean
 *
 * section_before_limit, section_after_limit
 * -----------------------------------------
 *
 * Marks the point of the query where we can insert a LIMIT clause;
 * so the section_before_limit will contain the left part before
 * a possible LIMIT clause
 *
 *
 * End of description of analyzer results
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
//DEBUG echo "Loop1 <b>"  . $arr[$i]['data'] . "</b> (" . $arr[$i]['type'] . ")<br />";

            // High speed seek for locating the end of the current query
            if ($seek_queryend == TRUE) {
                if ($arr[$i]['type'] == 'punct_queryend') {
                    $seek_queryend = FALSE;
                } else {
                    continue;
                } // end if (type == punct_queryend)
            } // end if ($seek_queryend)

            /**
             * Note: do not split if this is a punct_queryend for the first and only query
             * @todo when we find a UNION, should we split in another subresult?
             */
            if ($arr[$i]['type'] == 'punct_queryend' && ($i + 1 != $size)) {
                $result[]  = $subresult;
                $subresult = $subresult_empty;
                continue;
            } // end if (type == punct_queryend)

// ==============================================================
            if ($arr[$i]['type'] == 'punct_bracket_open_round') {
                $number_of_brackets++;
                if ($in_extract) {
                    $number_of_brackets_in_extract++;
                }
                if ($in_group_concat) {
                    $number_of_brackets_in_group_concat++;
                }
            }
// ==============================================================
            if ($arr[$i]['type'] == 'punct_bracket_close_round') {
                $number_of_brackets--;
                if ($number_of_brackets == 0) {
                    $in_subquery = false;
                }
                if ($in_extract) {
                    $number_of_brackets_in_extract--;
                    if ($number_of_brackets_in_extract == 0) {
                       $in_extract = FALSE;
                    }
                }
                if ($in_group_concat) {
                    $number_of_brackets_in_group_concat--;
                    if ($number_of_brackets_in_group_concat == 0) {
                       $in_group_concat = FALSE;
                    }
                }
            }

            if ($in_subquery) {
                /**
                 * skip the subquery to avoid setting
                 * select_expr or table_ref with the contents
                 * of this subquery; this is to avoid a bug when
                 * trying to edit the results of
                 * select * from child where not exists (select id from
                 * parent where child.parent_id = parent.id);
                 */
                continue;
            }
// ==============================================================
            if ($arr[$i]['type'] == 'alpha_functionName') {
                $upper_data = strtoupper($arr[$i]['data']);
                if ($upper_data =='EXTRACT') {
                    $in_extract = TRUE;
                    $number_of_brackets_in_extract = 0;
                }
                if ($upper_data =='GROUP_CONCAT') {
                    $in_group_concat = TRUE;
                    $number_of_brackets_in_group_concat = 0;
                }
            }

// ==============================================================
            if ($arr[$i]['type'] == 'alpha_reservedWord'
//             && $arr[$i]['forbidden'] == FALSE) {
            ) {
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
                /**
                 * @todo reset for each query?
                 */

                if ($upper_data == 'SELECT') {
                    if ($number_of_brackets > 0) {
                        $in_subquery = true;
                        $seen_subquery = true;
                        // this is a subquery so do not analyze inside it
                        continue;
                    }
                    $seen_from = FALSE;
                    $previous_was_identifier = FALSE;
                    $current_select_expr = -1;
                    $seen_end_of_table_ref = FALSE;
                } // end if (data == SELECT)

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
            if ($arr[$i]['type'] == 'quote_backtick'
             || $arr[$i]['type'] == 'quote_double'
             || $arr[$i]['type'] == 'quote_single'
             || $arr[$i]['type'] == 'alpha_identifier'
             || ($arr[$i]['type'] == 'alpha_reservedWord'
                && $arr[$i]['forbidden'] == FALSE)) {

                switch ($arr[$i]['type']) {
                    case 'alpha_identifier':
                    case 'alpha_reservedWord':
                        /**
                         * this is not a real reservedWord, because it's not
                         * present in the list of forbidden words, for example
                         * "storage" which can be used as an identifier
                         *
                         * @todo avoid the pretty printing in color in this case
                         */
                        $identifier = $arr[$i]['data'];
                        break;

                    case 'quote_backtick':
                    case 'quote_double':
                    case 'quote_single':
                        $identifier = PMA_unQuote($arr[$i]['data']);
                        break;
                } // end switch

                if ($subresult['querytype'] == 'SELECT' 
                 && ! $in_group_concat
                 && ! ($seen_subquery && $arr[$i - 1]['type'] == 'punct_bracket_close_round')) {
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
            } // end if (quote_backtick or double quote or alpha_identifier)

// ===================================
            if ($arr[$i]['type'] == 'punct_qualifier') {
                // to be able to detect an identifier following another
                $previous_was_identifier = FALSE;
                continue;
            } // end if (punct_qualifier)

            /**
             * @todo check if 3 identifiers following one another -> error
             */

            //    s a v e    a    s e l e c t    e x p r
            // finding a list separator or FROM
            // means that we must save the current chain of identifiers
            // into a select expression

            // for now, we only save a select expression if it contains
            // at least one identifier, as we are interested in checking
            // the columns and table names, so in "select * from persons",
            // the "*" is not saved

            if (isset($chain) && !$seen_end_of_table_ref
             && ((!$seen_from && $arr[$i]['type'] == 'punct_listsep')
              || ($arr[$i]['type'] == 'alpha_reservedWord' && $upper_data == 'FROM'))) {
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

                if (isset($alias_for_select_expr) && strlen($alias_for_select_expr)) {
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

                /**
                 * @todo explain this:
                 */
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
            // or we are at the last token
            // or we just got a reserved word
            /**
             * @todo there could be another query after this one
             */

            if (isset($chain) && $seen_from && $save_table_ref
             && ($arr[$i]['type'] == 'punct_listsep'
               || ($arr[$i]['type'] == 'alpha_reservedWord' && $upper_data!="AS")
               || $seen_end_of_table_ref
               || $i==$size-1)) {

                $size_chain = count($chain);
                $current_table_ref++;
                $subresult['table_ref'][$current_table_ref] = array(
                  'expr'            => '',
                  'db'              => '',
                  'table_name'      => '',
                  'table_alias'     => '',
                  'table_true_name' => ''
                 );
                if (isset($alias_for_table_ref) && strlen($alias_for_table_ref)) {
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

            if (isset($current_table_ref) && ($seen_end_of_table_ref || $i == $size-1) && $subresult != $subresult_empty) {
                for ($tr=0; $tr <= $current_table_ref; $tr++) {
                    $alias = $subresult['table_ref'][$tr]['table_alias'];
                    $truename = $subresult['table_ref'][$tr]['table_true_name'];
                    for ($se=0; $se <= $current_select_expr; $se++) {
                        if (isset($alias) && strlen($alias) && $subresult['select_expr'][$se]['table_true_name']
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

            /**
             * no need to check the end of table ref if we already did
             *
             * @todo maybe add "&& $seen_from"
             */
            if (!$seen_end_of_table_ref) {
                // if this is the last token, it implies that we have
                // seen the end of table references
                // Check for the end of table references
                //
                // Note: if we are analyzing a GROUP_CONCAT clause,
                // we might find a word that seems to indicate that
                // we have found the end of table refs (like ORDER)
                // but it's a modifier of the GROUP_CONCAT so
                // it's not the real end of table refs
                if (($i == $size-1)
                 || ($arr[$i]['type'] == 'alpha_reservedWord'
                 && !$in_group_concat
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

        //DEBUG
        /*
          if (isset($current_select_expr)) {
           for ($trace=0; $trace<=$current_select_expr; $trace++) {
               echo "<br />";
               reset ($subresult['select_expr'][$trace]);
               while (list ($key, $val) = each ($subresult['select_expr'][$trace]))
                   echo "sel expr $trace $key => $val<br />\n";
               }
          }

          if (isset($current_table_ref)) {
           echo "current_table_ref = " . $current_table_ref . "<br>";
           for ($trace=0; $trace<=$current_table_ref; $trace++) {

               echo "<br />";
               reset ($subresult['table_ref'][$trace]);
               while (list ($key, $val) = each ($subresult['table_ref'][$trace]))
               echo "table ref $trace $key => $val<br />\n";
               }
          }
        */
        // -------------------------------------------------------


        // loop #2: - queryflags
        //          - querytype (for queries != 'SELECT')
        //          - section_before_limit, section_after_limit
        //
        // we will also need this queryflag in loop 2
        // so set it here
        if (isset($current_table_ref) && $current_table_ref > -1) {
            $subresult['queryflags']['select_from'] = 1;
        }

        $section_before_limit = '';
        $section_after_limit = ''; // truly the section after the limit clause
        $seen_reserved_word = FALSE;
        $seen_group = FALSE;
        $seen_order = FALSE;
        $seen_order_by = FALSE;
        $in_group_by = FALSE; // true when we are inside the GROUP BY clause
        $in_order_by = FALSE; // true when we are inside the ORDER BY clause
        $in_having = FALSE; // true when we are inside the HAVING clause
        $in_select_expr = FALSE; // true when we are inside the select expr clause
        $in_where = FALSE; // true when we are inside the WHERE clause
        $seen_limit = FALSE; // true if we have seen a LIMIT clause
        $in_limit = FALSE; // true when we are inside the LIMIT clause
        $after_limit = FALSE; // true when we are after the LIMIT clause
        $in_from = FALSE; // true when we are in the FROM clause
        $in_group_concat = FALSE;
        $first_reserved_word = '';
        $current_identifier = '';
        $unsorted_query = $arr['raw']; // in case there is no ORDER BY

        for ($i = 0; $i < $size; $i++) {
//DEBUG echo "Loop2 <b>"  . $arr[$i]['data'] . "</b> (" . $arr[$i]['type'] . ")<br />";

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
                    if ($upper_data == 'DROP' && $first_reserved_word == 'ALTER') {
                        $subresult['queryflags']['need_confirm'] = 1;
                    }
                }

                if ($upper_data == 'LIMIT') {
                    $section_before_limit = substr($arr['raw'], 0, $arr[$i]['pos'] - 5);
                    $in_limit = TRUE;
                    $seen_limit = TRUE;
                    $limit_clause = '';
                    $in_order_by = FALSE; // @todo maybe others to set FALSE
                }

                if ($upper_data == 'PROCEDURE') {
                    $subresult['queryflags']['procedure'] = 1;
                    $in_limit = FALSE;
                    $after_limit = TRUE;
                }
                /**
                 * @todo set also to FALSE if we find FOR UPDATE or LOCK IN SHARE MODE
                 */
                if ($upper_data == 'SELECT') {
                    $in_select_expr = TRUE;
                    $select_expr_clause = '';
                }
                if ($upper_data == 'DISTINCT' && !$in_group_concat) {
                    $subresult['queryflags']['distinct'] = 1;
                }

                if ($upper_data == 'UNION') {
                    $subresult['queryflags']['union'] = 1;
                }

                if ($upper_data == 'JOIN') {
                    $subresult['queryflags']['join'] = 1;
                }

                if ($upper_data == 'OFFSET') {
                    $subresult['queryflags']['offset'] = 1;
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
                if ($upper_data == 'ORDER' && !$in_group_concat) {
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
                        $seen_order_by = TRUE;
                        // here we assume that the ORDER BY keywords took
                        // exactly 8 characters
                        $unsorted_query = substr($arr['raw'], 0, $arr[$i]['pos'] - 8);
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


            // do not add a space after a function name
            /**
             * @todo can we combine loop 2 and loop 1? some code is repeated here...
             */

            $sep = ' ';
            if ($arr[$i]['type'] == 'alpha_functionName') {
                $sep='';
                $upper_data = strtoupper($arr[$i]['data']);
                if ($upper_data =='GROUP_CONCAT') {
                    $in_group_concat = TRUE;
                    $number_of_brackets_in_group_concat = 0;
                }
            }

            if ($arr[$i]['type'] == 'punct_bracket_open_round') {
                if ($in_group_concat) {
                    $number_of_brackets_in_group_concat++;
                }
            }
            if ($arr[$i]['type'] == 'punct_bracket_close_round') {
                if ($in_group_concat) {
                    $number_of_brackets_in_group_concat--;
                    if ($number_of_brackets_in_group_concat == 0) {
                        $in_group_concat = FALSE;
                    }
                }
            }

            // do not add a space after an identifier if followed by a dot
            if ($arr[$i]['type'] == 'alpha_identifier' && $i < $size - 1 && $arr[$i + 1]['data'] == '.') {
                $sep = '';
            }

            // do not add a space after a dot if followed by an identifier
            if ($arr[$i]['data'] == '.' && $i < $size - 1 && $arr[$i + 1]['type'] == 'alpha_identifier') {
                $sep = '';
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
                // add a space only before ASC or DESC
                // not around the dot between dbname and tablename
                if ($arr[$i]['type'] == 'alpha_reservedWord') {
                    $order_by_clause .= $sep;
                }
                $order_by_clause .= $arr[$i]['data'];
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

            // to grab the rest of the query after the ORDER BY clause
            if (isset($subresult['queryflags']['select_from'])
             && $subresult['queryflags']['select_from'] == 1
             && ! $in_order_by
             && $seen_order_by
             && $upper_data != 'BY') {
                $unsorted_query .= $arr[$i]['data'];
                if ($arr[$i]['type'] != 'punct_bracket_open_round'
                 && $arr[$i]['type'] != 'punct_bracket_close_round'
                 && $arr[$i]['type'] != 'punct') {
                    $unsorted_query .= $sep;
                }
            }
            
            if ($in_limit) {
                if ($upper_data == 'OFFSET') {
                    $limit_clause .= $sep;
                }
		        $limit_clause .= $arr[$i]['data'];
                if ($upper_data == 'LIMIT' || $upper_data == 'OFFSET') {
                    $limit_clause .= $sep;
                }
            }
            if ($after_limit && $seen_limit) { 
                $section_after_limit .= $arr[$i]['data'] . $sep;
            }

            // clear $upper_data for next iteration
            $upper_data='';

        } // end for $i (loop #2)
        if (empty($section_before_limit)) {
            $section_before_limit = $arr['raw'];
        }

        // -----------------------------------------------------
        // loop #3: foreign keys and MySQL 4.1.2+ TIMESTAMP options
        // (for now, check only the first query)
        // (for now, identifiers are assumed to be backquoted)

        // If we find that we are dealing with a CREATE TABLE query,
        // we look for the next punct_bracket_open_round, which
        // introduces the fields list. Then, when we find a
        // quote_backtick, it must be a field, so we put it into
        // the create_table_fields array. Even if this field is
        // not a timestamp, it will be useful when logic has been
        // added for complete field attributes analysis.

        $seen_foreign = FALSE;
        $seen_references = FALSE;
        $seen_constraint = FALSE;
        $foreign_key_number = -1;
        $seen_create_table = FALSE;
        $seen_create = FALSE;
        $in_create_table_fields = FALSE;
        $brackets_level = 0;
        $in_timestamp_options = FALSE;
        $seen_default = FALSE;

        for ($i = 0; $i < $size; $i++) {
        // DEBUG echo "Loop 3 <b>" . $arr[$i]['data'] . "</b> " . $arr[$i]['type'] . "<br />";

            if ($arr[$i]['type'] == 'alpha_reservedWord') {
                $upper_data = strtoupper($arr[$i]['data']);

                if ($upper_data == 'NOT' && $in_timestamp_options) {
                    $create_table_fields[$current_identifier]['timestamp_not_null'] = TRUE;

                }

                if ($upper_data == 'CREATE') {
                    $seen_create = TRUE;
                }

                if ($upper_data == 'TABLE' && $seen_create) {
                    $seen_create_table = TRUE;
                    $create_table_fields = array();
                }

                if ($upper_data == 'CURRENT_TIMESTAMP') {
                    if ($in_timestamp_options) {
                        if ($seen_default) {
                            $create_table_fields[$current_identifier]['default_current_timestamp'] = TRUE;
                        }
                    }
                }

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


                // Cases covered:

                // [ON DELETE {CASCADE | SET NULL | NO ACTION | RESTRICT}]
                // [ON UPDATE {CASCADE | SET NULL | NO ACTION | RESTRICT}]

                // but we set ['on_delete'] or ['on_cascade'] to
                // CASCADE | SET_NULL | NO_ACTION | RESTRICT

                // ON UPDATE CURRENT_TIMESTAMP

                if ($upper_data == 'ON') {
                    if (isset($arr[$i+1]) && $arr[$i+1]['type'] == 'alpha_reservedWord') {
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
                              && strtoupper($arr[$i+2]['data'])=='NO'))
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
                            } elseif ($third_upper_data == 'CURRENT_TIMESTAMP') {
                                if ($clause == 'on_update'
                                && $in_timestamp_options) {
                                    $create_table_fields[$current_identifier]['on_update_current_timestamp'] = TRUE;
                                    $seen_default = FALSE;
                                }

                            } else {
                                $value = '';
                            }
                            if (!empty($value)) {
                                $foreign[$foreign_key_number][$clause] = $value;
                            }
                            unset($clause);
                        } // endif (isset($clause))
                    }
                }

            } // end of reserved words analysis


            if ($arr[$i]['type'] == 'punct_bracket_open_round') {
                $brackets_level++;
                if ($seen_create_table && $brackets_level == 1) {
                    $in_create_table_fields = TRUE;
                }
            }


            if ($arr[$i]['type'] == 'punct_bracket_close_round') {
                $brackets_level--;
                if ($seen_references) {
                    $seen_references = FALSE;
                }
                if ($seen_create_table && $brackets_level == 0) {
                    $in_create_table_fields = FALSE;
                }
            }

            if (($arr[$i]['type'] == 'alpha_columnAttrib')) {
                $upper_data = strtoupper($arr[$i]['data']);
                if ($seen_create_table && $in_create_table_fields) {
                    if ($upper_data == 'DEFAULT') {
                        $seen_default = TRUE;
                    }
                }
            }

            /**
             * @see @todo 2005-10-16 note: the "or" part here is a workaround for a bug
             */
            if (($arr[$i]['type'] == 'alpha_columnType') || ($arr[$i]['type'] == 'alpha_functionName' && $seen_create_table)) {
                $upper_data = strtoupper($arr[$i]['data']);
                if ($seen_create_table && $in_create_table_fields && isset($current_identifier)) {
                    $create_table_fields[$current_identifier]['type'] = $upper_data;
                    if ($upper_data == 'TIMESTAMP') {
                        $arr[$i]['type'] = 'alpha_columnType';
                        $in_timestamp_options = TRUE;
                    } else {
                        $in_timestamp_options = FALSE;
                        if ($upper_data == 'CHAR') {
                            $arr[$i]['type'] = 'alpha_columnType';
                        }
                    }
                }
            }


            if ($arr[$i]['type'] == 'quote_backtick' || $arr[$i]['type'] == 'alpha_identifier') {

                if ($arr[$i]['type'] == 'quote_backtick') {
                    // remove backquotes
                    $identifier = PMA_unQuote($arr[$i]['data']);
                } else {
                    $identifier = $arr[$i]['data'];
                }

                if ($seen_create_table && $in_create_table_fields) {
                    $current_identifier = $identifier;
                    // warning: we set this one even for non TIMESTAMP type
                    $create_table_fields[$current_identifier]['timestamp_not_null'] = FALSE;
                }

                if ($seen_constraint) {
                    $foreign[$foreign_key_number]['constraint'] = $identifier;
                }

                if ($seen_foreign && $brackets_level > 0) {
                    $foreign[$foreign_key_number]['index_list'][] = $identifier;
                }

                if ($seen_references) {
                    // here, the first bracket level corresponds to the
                    // bracket of CREATE TABLE
                    // so if we are on level 2, it must be the index list
                    // of the foreign key REFERENCES
                    if ($brackets_level > 1) {
                        $foreign[$foreign_key_number]['ref_index_list'][] = $identifier;
                    } else {
                        // for MySQL 4.0.18, identifier is
                        // `table` or `db`.`table`
                        // the first pass will pick the db name
                        // the next pass will execute the else and pick the
                        // db name in $db_table[0]
                        if ($arr[$i+1]['type'] == 'punct_qualifier') {
                                $foreign[$foreign_key_number]['ref_db_name'] = $identifier;
                        } else {
                        // for MySQL 4.0.16, identifier is
                        // `table` or `db.table`
                            $db_table = explode('.', $identifier);
                            if (isset($db_table[1])) {
                                $foreign[$foreign_key_number]['ref_db_name'] = $db_table[0];
                                $foreign[$foreign_key_number]['ref_table_name'] = $db_table[1];
                            } else {
                                $foreign[$foreign_key_number]['ref_table_name'] = $db_table[0];
                            }
                        }
                    }
                }
            }
        } // end for $i (loop #3)


        // Fill the $subresult array

        if (isset($create_table_fields)) {
            $subresult['create_table_fields'] = $create_table_fields;
        }

        if (isset($foreign)) {
            $subresult['foreign_keys'] = $foreign;
        }

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
        if (isset($limit_clause)) {
            $subresult['limit_clause'] = $limit_clause;
        }
        if (isset($where_clause)) {
            $subresult['where_clause'] = $where_clause;
        }
        if (isset($unsorted_query) && !empty($unsorted_query)) {
            $subresult['unsorted_query'] = $unsorted_query;
        }
        if (isset($where_clause_identifiers)) {
            $subresult['where_clause_identifiers'] = $where_clause_identifiers;
        }

        if (isset($position_of_first_select)) {
            $subresult['position_of_first_select'] = $position_of_first_select;
            $subresult['section_before_limit'] = $section_before_limit;
            $subresult['section_after_limit'] = $section_after_limit;
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
     * @todo check why adding a "\n" after the </span> would cause extra blanks
     * to be displayed: SELECT p . person_name
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
            $class = 'syntax_' . PMA_substr($arr['type'], 0, $i) . ' ';
        }

        $class     .= 'syntax_' . $arr['type'];

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
        //DEBUG echo 'in Format<pre>'; print_r($arr); echo '</pre>';
        // then check for an array
        if (!is_array($arr)) {
            return htmlspecialchars($arr);
        }
        // first check for the SQL parser having hit an error
        if (PMA_SQP_isError()) {
            return htmlspecialchars($arr['raw']);
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
            'AS',
            'ASC',
            'DESC',
            'DISTINCT',
            'DUPLICATE',
            'HOUR',
            'INTERVAL',
            'IS',
            'LIKE',
            'NOT',
            'NULL',
            'ON',
            'REGEXP'
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
// DEBUG echo "Loop format <b>" . $arr[$i]['data'] . "</b> " . $arr[$i]['type'] . "<br />";
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
                //array_push($typearr, null);
                $typearr[4] = '';
            }

            for ($j=0; $j<4; $j++) {
                $typearr[$j] = $typearr[$j + 1];
            }

            switch ($typearr[2]) {
                case 'white_newline':
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
                case 'punct_user':
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
                    // COLLATE LATIN1_GENERAL_CI DEFAULT
                    if ($typearr[1] == 'alpha_identifier' || $typearr[1] == 'alpha_charset') {
                        $before .= ' ';
                    }
                    if (($typearr[3] == 'alpha_columnAttrib') || ($typearr[3] == 'quote_single') || ($typearr[3] == 'digit_integer')) {
                        $after     .= ' ';
                    }
                    // workaround for
                    // AUTO_INCREMENT = 31DEFAULT_CHARSET = utf-8

                    if ($typearr[2] == 'alpha_columnAttrib' && $typearr[3] == 'alpha_reservedWord') {
                        $before .= ' ';
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
                    // do not uppercase the reserved word if we are calling
                    // this function in query_only mode, because we need
                    // the original query (otherwise we get problems with
                    // semi-reserved words like "storage" which is legal
                    // as an identifier name)

                    if ($mode != 'query_only') {
                        $arr[$i]['data'] = strtoupper($arr[$i]['data']);
                    }

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
                            // the alpha_identifier exception is there to
                            // catch cases like
                            // GRANT SELECT ON mydb.mytable TO myuser@localhost
                            // (else, we get mydb.mytableTO)
                            //
                            // the quote_single exception is there to
                            // catch cases like
                            // GRANT ... TO 'marc'@'domain.com' IDENTIFIED...
                            /**
                             * @todo fix all cases and find why this happens
                             */

                            if (!$in_priv_list || $typearr[1] == 'alpha_identifier' || $typearr[1] == 'quote_single' || $typearr[1] == 'white_newline') {
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
                    /**
                     * @todo could there be other types preceding a digit?
                     */
                    if ($typearr[1] == 'alpha_reservedWord') {
                        $after .= ' ';
                    }
                    if ($infunction && $typearr[3] == 'punct_bracket_close_round') {
                        $after     .= ' ';
                    }
                    if ($typearr[1] == 'alpha_columnAttrib') {
                        $before .= ' ';
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
                    if ($typearr[1] != 'punct_user') {
                        $before        .= ' ';
                    }
                    if ($infunction && $typearr[3] == 'punct_bracket_close_round') {
                        $after     .= ' ';
                    }
                    break;
                case 'quote_backtick':
                    // here we check for punct_user to handle correctly
                    // DEFINER = `username`@`%`
                    // where @ is the punct_user and `%` is the quote_backtick 
                    if ($typearr[3] != 'punct_qualifier' && $typearr[3] != 'alpha_variable' && $typearr[3] != 'punct_user') {
                        $after     .= ' ';
                    }
                    if ($typearr[1] != 'punct_qualifier' && $typearr[1] != 'alpha_variable' && $typearr[1] != 'punct_user') {
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
    foreach ($cfg['SQP']['fmtColor'] AS $key => $col) {
        $css_string .= PMA_SQP_buildCssRule('syntax_' . $key, 'color', $col);
    }

    for ($i = 0; $i < 8; $i++) {
        $css_string .= PMA_SQP_buildCssRule('syntax_indent' . $i, 'margin-left', ($i * $cfg['SQP']['fmtInd']) . $cfg['SQP']['fmtIndUnit']);
    }

    return $css_string;
} // end of the "PMA_SQP_buildCssData()" function

if (! defined('PMA_MINIMUM_COMMON')) {
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
     * @todo WRITE THIS!
     * @param  array   The SQL queries list
     *
     * @return string  The SQL queries in text format
     *
     * @access public
     */
    function PMA_SQP_formatText($arr)
    {
         return PMA_SQP_formatNone($arr);
    } // end of the "PMA_SQP_formatText()" function
} // end if: minimal common.lib needed?

?>
