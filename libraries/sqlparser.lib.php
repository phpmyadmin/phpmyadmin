<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/** SQL Parser Functions for phpMyAdmin
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
 * See comments in PMA_SQP_analyze for the returned info
 * from the analyzer.
 *
 * If you want a pretty-printed version of the query, do:
 * $string = PMA_SQP_formatHtml($parsed_sql);
 * (note that that you need to have syntax.css.php included somehow in your
 * page for it to work, I recommend '<link rel="stylesheet" type="text/css"
 * href="syntax.css.php" />' at the moment.)
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Include the string library as we use it heavily
 */
require_once './libraries/string.lib.php';

/**
 * Include data for the SQL Parser
 */
require_once './libraries/sqlparser.data.php';

/**
 * Charset information
 */
if (!defined('TESTSUITE') && ! PMA_DRIZZLE) {
    include_once './libraries/mysql_charsets.lib.php';
}
if (! isset($mysql_charsets)) {
    $mysql_charsets = array();
    $mysql_collations_flat = array();
}

/**
 * Stores parsed elemented of query to array.
 *
 * Currently we don't need the $pos (token position in query)
 * for other purposes than LIMIT clause verification,
 * so many calls to this function do not include the 4th parameter
 *
 * @param array  &$arr     Array to store element
 * @param string $type     Type of element
 * @param string $data     Data (text) of element
 * @param int    &$arrsize Size of array
 * @param int    $pos      Position of an element
 *
 * @return nothing
 */
function PMA_SQP_arrayAdd(&$arr, $type, $data, &$arrsize, $pos = 0)
{
    $arr[] = array('type' => $type, 'data' => $data, 'pos' => $pos);
    $arrsize++;
} // end of the "PMA_SQP_arrayAdd()" function

/**
 * Reset the error variable for the SQL parser
 *
 * @access public
 *
 * @return nothing
 */
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
function PMA_SQP_isError()
{
    global $SQP_errorString;
    return isset($SQP_errorString) && !empty($SQP_errorString);
}

/**
 * Set an error message for the system
 *
 * @param string $message The error message
 * @param string $sql     The failing SQL query
 *
 * @return nothing
 *
 * @access private
 * @scope SQL Parser internal
 */
function PMA_SQP_throwError($message, $sql)
{
    global $SQP_errorString;
    $SQP_errorString = '<p>'.__('There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem') . '</p>' . "\n"
        . '<pre>' . "\n"
        . 'ERROR: ' . $message . "\n"
        . 'SQL: ' . htmlspecialchars($sql) .  "\n"
        . '</pre>' . "\n";

} // end of the "PMA_SQP_throwError()" function


/**
 * Do display the bug report
 *
 * @param string $message The error message
 * @param string $sql     The failing SQL query
 *
 * @return nothing
 *
 * @access public
 */
function PMA_SQP_bug($message, $sql)
{
    global $SQP_errorString;
    $debugstr = 'ERROR: ' . $message . "\n";
    $debugstr .= 'MySQL: '.PMA_MYSQL_STR_VERSION . "\n";
    $debugstr .= 'USR OS, AGENT, VER: ' . PMA_USR_OS . ' ';
    $debugstr .= PMA_USR_BROWSER_AGENT . ' ' . PMA_USR_BROWSER_VER . "\n";
    $debugstr .= 'PMA: ' . PMA_VERSION . "\n";
    $debugstr .= 'PHP VER,OS: ' . PMA_PHP_STR_VERSION . ' ' . PHP_OS . "\n";
    $debugstr .= 'LANG: ' . $GLOBALS['lang'] . "\n";
    $debugstr .= 'SQL: ' . htmlspecialchars($sql);

    $encodedstr     = $debugstr;
    if (@function_exists('gzcompress')) {
        $encodedstr = gzcompress($debugstr, 9);
    }
    $encodedstr     = preg_replace(
        "/(\015\012)|(\015)|(\012)/",
        '<br />' . "\n",
        chunk_split(base64_encode($encodedstr))
    );


    $SQP_errorString .= __('There is a chance that you may have found a bug in the SQL parser. Please examine your query closely, and check that the quotes are correct and not mis-matched. Other possible failure causes may be that you are uploading a file with binary outside of a quoted text area. You can also try your query on the MySQL command line interface. The MySQL server error output below, if there is any, may also help you in diagnosing the problem. If you still have problems or if the parser fails where the command line interface succeeds, please reduce your SQL query input to the single query that causes problems, and submit a bug report with the data chunk in the CUT section below:')
         . '<br />' . "\n"
         . '----' . __('BEGIN CUT') . '----' . '<br />' . "\n"
         . $encodedstr . "\n"
         . '----' . __('END CUT') . '----' . '<br />' . "\n";

    $SQP_errorString .= '----' . __('BEGIN RAW') . '----<br />' . "\n"
         . '<pre>' . "\n"
         . $debugstr
         . '</pre>' . "\n"
         . '----' . __('END RAW') . '----<br />' . "\n";

} // end of the "PMA_SQP_bug()" function


/**
 * Parses the SQL queries
 *
 * @param string $sql The SQL query list
 *
 * @return mixed Most of times, nothing...
 *
 * @global array    The current PMA configuration
 * @global array    MySQL column attributes
 * @global array    MySQL reserved words
 * @global array    MySQL column types
 * @global array    MySQL function names
 * @global array    List of available character sets
 * @global array    List of available collations
 *
 * @access public
 */
function PMA_SQP_parse($sql)
{
    static $PMA_SQPdata_column_attrib, $PMA_SQPdata_reserved_word;
    static $PMA_SQPdata_column_type;
    static $PMA_SQPdata_function_name, $PMA_SQPdata_forbidden_word;
    global $mysql_charsets, $mysql_collations_flat;

    // Convert all line feeds to Unix style
    $sql = str_replace("\r\n", "\n", $sql);
    $sql = str_replace("\r", "\n", $sql);

    $len = PMA_strlen($sql);
    if ($len == 0) {
        return array();
    }

    // Create local hashtables
    if (!isset($PMA_SQPdata_column_attrib)) {
        $PMA_SQPdata_column_attrib  = array_flip(
            $GLOBALS['PMA_SQPdata_column_attrib']
        );
        $PMA_SQPdata_function_name  = array_flip(
            $GLOBALS['PMA_SQPdata_function_name']
        );
        $PMA_SQPdata_reserved_word  = array_flip(
            $GLOBALS['PMA_SQPdata_reserved_word']
        );
        $PMA_SQPdata_forbidden_word = array_flip(
            $GLOBALS['PMA_SQPdata_forbidden_word']
        );
        $PMA_SQPdata_column_type    = array_flip(
            $GLOBALS['PMA_SQPdata_column_type']
        );
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
    $allpunct_list_pair      = array(
        '!=' => 1,
        '&&' => 1,
        ':=' => 1,
        '<<' => 1,
        '<=' => 1,
        '<=>' => 1,
        '<>' => 1,
        '>=' => 1,
        '>>' => 1,
        '||' => 1,
        '==' => 1
    );
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
        $next_c = PMA_substr($sql, $count2 + 1, 1);
        if (($c == '#')
            || (($count2 + 1 < $len) && ($c == '/') && ($next_c == '*'))
            || (($count2 + 2 == $len) && ($c == '-') && ($next_c == '-'))
            || (($count2 + 2 < $len) && ($c == '-') && ($next_c == '-') && ((PMA_substr($sql, $count2 + 2, 1) <= ' ')))
        ) {
            $count2++;
            $pos  = 0;
            $type = 'bad';
            switch ($c) {
            case '#':
                $type = 'mysql';
            case '-':
                $type = 'ansi';
                $pos  = PMA_strpos($sql, "\n", $count2);
                break;
            case '/':
                $type = 'c';
                $pos  = PMA_strpos($sql, '*/', $count2);
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
        if (PMA_strpos($quote_list, $c) !== false) {
            $startquotepos   = $count2;
            $quotetype       = $c;
            $count2++;
            $pos             = $count2;
            $oldpos          = 0;
            do {
                $oldpos = $pos;
                $pos    = PMA_strpos(' ' . $sql, $quotetype, $oldpos + 1) - 1;
                // ($pos === false)
                if ($pos < 0) {
                    if ($c == '`') {
                        /*
                         * Behave same as MySQL and accept end of query as end of backtick.
                         * I know this is sick, but MySQL behaves like this:
                         *
                         * SELECT * FROM `table
                         *
                         * is treated like
                         *
                         * SELECT * FROM `table`
                         */
                        $pos_quote_separator = PMA_strpos(' ' . $sql, $GLOBALS['sql_delimiter'], $oldpos + 1) - 1;
                        if ($pos_quote_separator < 0) {
                            $len += 1;
                            $sql .= '`';
                            $sql_array['raw'] .= '`';
                            $pos = $len;
                        } else {
                            $len += 1;
                            $sql = PMA_substr($sql, 0, $pos_quote_separator) . '`' . PMA_substr($sql, $pos_quote_separator);
                            $sql_array['raw'] = $sql;
                            $pos = $pos_quote_separator;
                        }
                        if (class_exists('PMA_Message') && $GLOBALS['is_ajax_request'] != true) {
                            PMA_Message::notice(__('Automatically appended backtick to the end of query!'))->display();
                        }
                    } else {
                        $debugstr = __('Unclosed quote') . ' @ ' . $startquotepos. "\n"
                                  . 'STR: ' . htmlspecialchars($quotetype);
                        PMA_SQP_throwError($debugstr, $sql);
                        return $sql_array;
                    }
                }

                // If the quote is the first character, it can't be
                // escaped, so don't do the rest of the code
                if ($pos == 0) {
                    break;
                }

                // Checks for MySQL escaping using a \
                // And checks for ANSI escaping using the $quotetype character
                if (($pos < $len) && PMA_STR_charIsEscaped($sql, $pos) && $c != '`') {
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
        if (PMA_strpos($bracket_list, $c) !== false) {
            // All bracket tokens are only one item long
            $this_was_bracket = true;
            $count2++;
            $type_type     = '';
            if (PMA_strpos('([{', $c) !== false) {
                $type_type = 'open';
            } else {
                $type_type = 'close';
            }

            $type_style     = '';
            if (PMA_strpos('()', $c) !== false) {
                $type_style = 'round';
            } elseif (PMA_strpos('[]', $c) !== false) {
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
            && ($previous_was_space || $previous_was_bracket || $previous_was_listsep))
        ) {
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
            $is_float_digit_exponent = false;

            /* DEBUG
            echo '<pre>2';
            var_dump($is_identifier);
            var_dump($is_sql_variable);
            var_dump($is_digit);
            var_dump($is_float_digit);
            echo '</pre>';
             */

            // Fast skip is especially needed for huge BLOB data
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

            while (($count2 < $len) && PMA_STR_isSqlIdentifier(PMA_substr($sql, $count2, 1), ($is_sql_variable || $is_digit))) {
                $c2 = PMA_substr($sql, $count2, 1);
                if ($is_sql_variable && ($c2 == '.')) {
                    $count2++;
                    continue;
                }
                if ($is_digit && (!$is_hex_digit) && ($c2 == '.')) {
                    $count2++;
                    if (!$is_float_digit) {
                        $is_float_digit = true;
                        continue;
                    } else {
                        $debugstr = __('Invalid Identifer') . ' @ ' . ($count1+1) . "\n"
                                  . 'STR: ' . htmlspecialchars(PMA_substr($sql, $count1, $count2 - $count1));
                        PMA_SQP_throwError($debugstr, $sql);
                        return $sql_array;
                    }
                }
                if ($is_digit && (!$is_hex_digit) && (($c2 == 'e') || ($c2 == 'E'))) {
                    if (!$is_float_digit_exponent) {
                        $is_float_digit_exponent = true;
                        $is_float_digit          = true;
                        $count2++;
                        continue;
                    } else {
                        $is_digit                = false;
                        $is_float_digit          = false;
                    }
                }
                if (($is_hex_digit && PMA_STR_isHexDigit($c2)) || ($is_digit && PMA_STR_isDigit($c2))) {
                    $count2++;
                    continue;
                } else {
                    $is_digit     = false;
                    $is_hex_digit = false;
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
            } elseif ($is_sql_variable != false) {
                $type = 'alpha_variable';
            } else {
                $type = 'alpha';
            } // end if... else....
            PMA_SQP_arrayAdd($sql_array, $type, $str, $arraysize, $count2);

            continue;
        }

        // Checks for punct
        if (PMA_strpos($allpunct_list, $c) !== false) {
            while (($count2 < $len) && PMA_strpos($allpunct_list, PMA_substr($sql, $count2, 1)) !== false) {
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
            } elseif ($punct_data == $GLOBALS['sql_delimiter'] || isset($allpunct_list_pair[$punct_data])) {
                // Ok, we have one of the valid combined punct expressions
                PMA_SQP_arrayAdd($sql_array, 'punct', $punct_data, $arraysize);
            } else {
                // Bad luck, lets split it up more
                $first  = $punct_data[0];
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
                } elseif ($last != '~') {
                    /**
                     * @todo for negation operator, split in 2 tokens ?
                     * "select x&~1 from t"
                     * becomes "select x & ~ 1 from t" ?
                     */
                    $debugstr =  __('Unknown Punctuation String') . ' @ ' . ($count1+1) . "\n"
                              . 'STR: ' . htmlspecialchars($punct_data);
                    PMA_SQP_throwError($debugstr, $sql);
                    return $sql_array;
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
        return $sql_array;

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

        //DEBUG echo "[prev: <strong>".$d_prev."</strong> ".$t_prev."][cur: <strong>".$d_cur."</strong> ".$t_cur."][next: <strong>".$d_next."</strong> ".$t_next."]<br />";

        if ($t_cur == 'alpha') {
            $t_suffix     = '_identifier';
            // for example: `thebit` bit(8) NOT NULL DEFAULT b'0'
            if ($t_prev == 'alpha' && $d_prev == 'DEFAULT' && $d_cur == 'b' && $t_next == 'quote_single') {
                $t_suffix = '_bitfield_constant_introducer';
            } elseif (($t_next == 'punct_qualifier') || ($t_prev == 'punct_qualifier')) {
                $t_suffix = '_identifier';
            } elseif (($t_next == 'punct_bracket_open_round')
              && isset($PMA_SQPdata_function_name[$d_cur_upper])) {
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
            } elseif (isset($PMA_SQPdata_column_type[$d_cur_upper])) {
                $t_suffix = '_columnType';

                /**
                 * Temporary fix for bugs #621357 and #2027720
                 *
                 * @todo FIX PROPERLY NEEDS OVERHAUL OF SQL TOKENIZER
                 */
                if (($d_cur_upper == 'SET' || $d_cur_upper == 'BINARY') && $t_next != 'punct_bracket_open_round') {
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

            } elseif (isset($PMA_SQPdata_reserved_word[$d_cur_upper])) {
                $t_suffix = '_reservedWord';
            } elseif (isset($PMA_SQPdata_column_attrib[$d_cur_upper])) {
                $t_suffix = '_columnAttrib';
                // INNODB is a MySQL table type, but in "SHOW INNODB STATUS",
                // it should be regarded as a reserved word.
                if ($d_cur_upper == 'INNODB'
                    && $d_prev_upper == 'SHOW'
                    && $d_next_upper == 'STATUS'
                ) {
                    $t_suffix = '_reservedWord';
                }

                if ($d_cur_upper == 'DEFAULT' && $d_next_upper == 'CHARACTER') {
                    $t_suffix = '_reservedWord';
                }
                // Binary as character set
                if ($d_cur_upper == 'BINARY'
                    && (($d_bef_prev_upper == 'CHARACTER' && $d_prev_upper == 'SET')
                    || ($d_bef_prev_upper == 'SET' && $d_prev_upper == '=')
                    || ($d_bef_prev_upper == 'CHARSET' && $d_prev_upper == '=')
                    || $d_prev_upper == 'CHARSET')
                    && in_array($d_cur, $mysql_charsets)
                ) {
                    $t_suffix = '_charset';
                }
            } elseif (in_array($d_cur, $mysql_charsets)
              || in_array($d_cur, $mysql_collations_flat)
              || ($d_cur{0} == '_' && in_array(substr($d_cur, 1), $mysql_charsets))) {
                $t_suffix = '_charset';
            } else {
                // Do nothing
            }
            // check if present in the list of forbidden words
            if ($t_suffix == '_reservedWord'
                && isset($PMA_SQPdata_forbidden_word[$d_cur_upper])
            ) {
                $sql_array[$i]['forbidden'] = true;
            } else {
                $sql_array[$i]['forbidden'] = false;
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
 * @param string $toCheck    String of type that we have
 * @param string $whatWeWant String of type that we want
 *
 * @return boolean result of check
 *
 * @access private
 */
function PMA_SQP_typeCheck($toCheck, $whatWeWant)
{
    $typeSeparator = '_';
    if (strcmp($whatWeWant, $toCheck) == 0) {
        return true;
    } else {
        if (strpos($whatWeWant, $typeSeparator) === false) {
            return strncmp(
                $whatWeWant, $toCheck,
                strpos($toCheck, $typeSeparator)
            ) == 0;
        } else {
            return false;
        }
    }
}


/**
 * Analyzes SQL queries
 *
 * @param array $arr The SQL queries
 *
 * @return array   The analyzed SQL queries
 *
 * @access public
 */
function PMA_SQP_analyze($arr)
{
    if ($arr == array() || ! isset($arr['len'])) {
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
        'limit_clause'  => '',
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
    $seek_queryend         = false;
    $seen_end_of_table_ref = false;
    $number_of_brackets_in_extract = 0;
    $number_of_brackets_in_group_concat = 0;

    $number_of_brackets = 0;
    $in_subquery = false;
    $seen_subquery = false;
    $seen_from = false;

    // for SELECT EXTRACT(YEAR_MONTH FROM CURDATE())
    // we must not use CURDATE as a table_ref
    // so we track whether we are in the EXTRACT()
    $in_extract          = false;

    // for GROUP_CONCAT(...)
    $in_group_concat     = false;

    /* Description of analyzer results
     *
     * db, table, column, alias
     * ------------------------
     *
     * Inside the $subresult array, we create ['select_expr'] and ['table_ref']
     * arrays.
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
     * ['limit_clause']
     * ['where_clause']
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
     * Used to detect the DEFAULT CURRENT_TIMESTAMP and
     * ON UPDATE CURRENT_TIMESTAMP clauses of the CREATE TABLE query.
     * Also used to store the default value of the field.
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
        'FOR' => 1,
        'GROUP' => 1,
        'HAVING' => 1,
        'LIMIT' => 1,
        'LOCK' => 1,
        'ORDER' => 1,
        'PROCEDURE' => 1,
        'UNION' => 1,
        'WHERE' => 1
    );

    $words_ending_clauses = array(
        'FOR' => 1,
        'LIMIT' => 1,
        'LOCK' => 1,
        'PROCEDURE' => 1,
        'UNION' => 1
    );

    $supported_query_types = array(
        'SELECT' => 1,
        /*
        // Support for these additional query types will come later on.
        'DELETE' => 1,
        'INSERT' => 1,
        'REPLACE' => 1,
        'TRUNCATE' => 1,
        'UPDATE' => 1,
        'EXPLAIN' => 1,
        'DESCRIBE' => 1,
        'SHOW' => 1,
        'CREATE' => 1,
        'SET' => 1,
        'ALTER' => 1
        */
    );

    // loop #1 for each token: select_expr, table_ref for SELECT

    for ($i = 0; $i < $size; $i++) {
        //DEBUG echo "Loop1 <strong>"  . $arr[$i]['data']
        //. "</strong> (" . $arr[$i]['type'] . ")<br />";

        // High speed seek for locating the end of the current query
        if ($seek_queryend == true) {
            if ($arr[$i]['type'] == 'punct_queryend') {
                $seek_queryend = false;
            } else {
                continue;
            } // end if (type == punct_queryend)
        } // end if ($seek_queryend)

        /**
         * Note: do not split if this is a punct_queryend for the first and only
         * query
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
                    $in_extract = false;
                }
            }
            if ($in_group_concat) {
                $number_of_brackets_in_group_concat--;
                if ($number_of_brackets_in_group_concat == 0) {
                    $in_group_concat = false;
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
                $in_extract = true;
                $number_of_brackets_in_extract = 0;
            }
            if ($upper_data =='GROUP_CONCAT') {
                $in_group_concat = true;
                $number_of_brackets_in_group_concat = 0;
            }
        }

        // ==============================================================
        if ($arr[$i]['type'] == 'alpha_reservedWord') {
            // We don't know what type of query yet, so run this
            if ($subresult['querytype'] == '') {
                $subresult['querytype'] = strtoupper($arr[$i]['data']);
            } // end if (querytype was empty)

            // Check if we support this type of query
            if (!isset($supported_query_types[$subresult['querytype']])) {
                // Skip ahead to the next one if we don't
                $seek_queryend = true;
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
                $seen_from = false;
                $previous_was_identifier = false;
                $current_select_expr = -1;
                $seen_end_of_table_ref = false;
            } // end if (data == SELECT)

            if ($upper_data =='FROM' && !$in_extract) {
                $current_table_ref = -1;
                $seen_from = true;
                $previous_was_identifier = false;
                $save_table_ref = true;
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
            && $arr[$i]['forbidden'] == false)
        ) {
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
                $identifier = PMA_Util::unQuote($arr[$i]['data']);
                break;
            } // end switch

            if ($subresult['querytype'] == 'SELECT'
                && ! $in_group_concat
                && ! ($seen_subquery && $arr[$i - 1]['type'] == 'punct_bracket_close_round')
            ) {
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
                        $previous_was_identifier = true;

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
                            $previous_was_identifier = true;

                        } // end if ($previous_was_identifier)
                    } // end if ($save_table_ref &&!$seen_end_of_table_ref)
                } // end if (!$seen_from)
            } // end if (querytype SELECT)
        } // end if (quote_backtick or double quote or alpha_identifier)

        // ===================================
        if ($arr[$i]['type'] == 'punct_qualifier') {
            // to be able to detect an identifier following another
            $previous_was_identifier = false;
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
            || ($arr[$i]['type'] == 'alpha_reservedWord' && $upper_data == 'FROM'))
        ) {
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
                && ($upper_data != 'FROM')
            ) {
                $previous_was_identifier = true;
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
            || ($arr[$i]['type'] == 'alpha_reservedWord' && $upper_data != "AS")
            || $seen_end_of_table_ref
            || $i == $size - 1)
        ) {

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
            $previous_was_identifier = true;
            //continue;

        } // end if (save a table ref)


        // when we have found all table refs,
        // for each table_ref alias, put the true name of the table
        // in the corresponding select expressions

        if (isset($current_table_ref)
            && ($seen_end_of_table_ref || $i == $size-1)
            && $subresult != $subresult_empty
        ) {
            for ($tr=0; $tr <= $current_table_ref; $tr++) {
                $alias = $subresult['table_ref'][$tr]['table_alias'];
                $truename = $subresult['table_ref'][$tr]['table_true_name'];
                for ($se=0; $se <= $current_select_expr; $se++) {
                    if (isset($alias)
                        && strlen($alias)
                        && $subresult['select_expr'][$se]['table_true_name'] == $alias
                    ) {
                        $subresult['select_expr'][$se]['table_true_name'] = $truename;
                    } // end if (found the alias)
                } // end for (select expressions)

            } // end for (table refs)
        } // end if (set the true names)


        // e n d i n g    l o o p  #1
        // set the $previous_was_identifier to false if the current
        // token is not an identifier
        if (($arr[$i]['type'] != 'alpha_identifier')
            && ($arr[$i]['type'] != 'quote_double')
            && ($arr[$i]['type'] != 'quote_single')
            && ($arr[$i]['type'] != 'quote_backtick')
        ) {
            $previous_was_identifier = false;
        } // end if

        // however, if we are on AS, we must keep the $previous_was_identifier
        if (($arr[$i]['type'] == 'alpha_reservedWord')
            && ($upper_data == 'AS')
        ) {
            $previous_was_identifier = true;
        }

        if (($arr[$i]['type'] == 'alpha_reservedWord')
            && ($upper_data =='ON' || $upper_data =='USING')
        ) {
            $save_table_ref = false;
        } // end if (data == ON)

        if (($arr[$i]['type'] == 'alpha_reservedWord')
            && ($upper_data =='JOIN' || $upper_data =='FROM')
        ) {
            $save_table_ref = true;
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
                && isset($words_ending_table_ref[$upper_data]))
            ) {
                $seen_end_of_table_ref = true;
                // to be able to save the last table ref, but do not
                // set it true if we found a word like "ON" that has
                // already set it to false
                if (isset($save_table_ref) && $save_table_ref != false) {
                    $save_table_ref = true;
                } //end if

            } // end if (check for end of table ref)
        } //end if (!$seen_end_of_table_ref)

        if ($seen_end_of_table_ref) {
            $save_table_ref = false;
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
    $seen_reserved_word = false;
    $seen_group = false;
    $seen_order = false;
    $seen_order_by = false;
    $in_group_by = false; // true when we are inside the GROUP BY clause
    $in_order_by = false; // true when we are inside the ORDER BY clause
    $in_having = false; // true when we are inside the HAVING clause
    $in_select_expr = false; // true when we are inside the select expr clause
    $in_where = false; // true when we are inside the WHERE clause
    $seen_limit = false; // true if we have seen a LIMIT clause
    $in_limit = false; // true when we are inside the LIMIT clause
    $after_limit = false; // true when we are after the LIMIT clause
    $in_from = false; // true when we are in the FROM clause
    $in_group_concat = false;
    $first_reserved_word = '';
    $current_identifier = '';
    $unsorted_query = $arr['raw']; // in case there is no ORDER BY
    $number_of_brackets = 0;
    $in_subquery = false;

    for ($i = 0; $i < $size; $i++) {
        //DEBUG echo "Loop2 <strong>"  . $arr[$i]['data']
        //. "</strong> (" . $arr[$i]['type'] . ")<br />";

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

        if ($arr[$i]['type'] == 'punct_bracket_open_round') {
            $number_of_brackets++;
        }

        if ($arr[$i]['type'] == 'punct_bracket_close_round') {
            $number_of_brackets--;
            if ($number_of_brackets == 0) {
                $in_subquery = false;
            }
        }

        if ($arr[$i]['type'] == 'alpha_reservedWord') {
            $upper_data = strtoupper($arr[$i]['data']);

            if ($upper_data == 'SELECT' && $number_of_brackets > 0) {
                $in_subquery = true;
            }

            if (!$seen_reserved_word) {
                $first_reserved_word = $upper_data;
                $subresult['querytype'] = $upper_data;
                $seen_reserved_word = true;

                // if the first reserved word is DROP or DELETE,
                // we know this is a query that needs to be confirmed
                if ($first_reserved_word=='DROP'
                    || $first_reserved_word == 'DELETE'
                    || $first_reserved_word == 'TRUNCATE'
                ) {
                    $subresult['queryflags']['need_confirm'] = 1;
                }

                if ($first_reserved_word=='SELECT') {
                    $position_of_first_select = $i;
                }

            } else {
                if ($upper_data == 'DROP' && $first_reserved_word == 'ALTER') {
                    $subresult['queryflags']['need_confirm'] = 1;
                }
            }

            if ($upper_data == 'LIMIT' && ! $in_subquery) {
                $section_before_limit = substr($arr['raw'], 0, $arr[$i]['pos'] - 5);
                $in_limit = true;
                $seen_limit = true;
                $limit_clause = '';
                $in_order_by = false; // @todo maybe others to set false
            }

            if ($upper_data == 'PROCEDURE') {
                $subresult['queryflags']['procedure'] = 1;
                $in_limit = false;
                $after_limit = true;
            }
            /**
             * @todo set also to false if we find FOR UPDATE or LOCK IN SHARE MODE
             */
            if ($upper_data == 'SELECT') {
                $in_select_expr = true;
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
            if ($upper_data == 'FROM'
                && isset($subresult['queryflags']['select_from'])
                && $subresult['queryflags']['select_from'] == 1
            ) {
                $in_from = true;
                $from_clause = '';
                $in_select_expr = false;
            }


            // (we could have less resetting of variables to false
            // if we trust that the query respects the standard
            // MySQL order for clauses)

            // we use $seen_group and $seen_order because we are looking
            // for the BY
            if ($upper_data == 'GROUP') {
                $seen_group = true;
                $seen_order = false;
                $in_having = false;
                $in_order_by = false;
                $in_where = false;
                $in_select_expr = false;
                $in_from = false;
            }
            if ($upper_data == 'ORDER' && !$in_group_concat) {
                $seen_order = true;
                $seen_group = false;
                $in_having = false;
                $in_group_by = false;
                $in_where = false;
                $in_select_expr = false;
                $in_from = false;
            }
            if ($upper_data == 'HAVING') {
                $in_having = true;
                $having_clause = '';
                $seen_group = false;
                $seen_order = false;
                $in_group_by = false;
                $in_order_by = false;
                $in_where = false;
                $in_select_expr = false;
                $in_from = false;
            }

            if ($upper_data == 'WHERE') {
                $in_where = true;
                $where_clause = '';
                $where_clause_identifiers = array();
                $seen_group = false;
                $seen_order = false;
                $in_group_by = false;
                $in_order_by = false;
                $in_having = false;
                $in_select_expr = false;
                $in_from = false;
            }

            if ($upper_data == 'BY') {
                if ($seen_group) {
                    $in_group_by = true;
                    $group_by_clause = '';
                }
                if ($seen_order) {
                    $seen_order_by = true;
                    // Here we assume that the ORDER BY keywords took
                    // exactly 8 characters.
                    // We use PMA_substr() to be charset-safe; otherwise
                    // if the table name contains accents, the unsorted
                    // query would be missing some characters.
                    $unsorted_query = PMA_substr(
                        $arr['raw'], 0, $arr[$i]['pos'] - 8
                    );
                    $in_order_by = true;
                    $order_by_clause = '';
                }
            }

            // if we find one of the words that could end the clause
            if (isset($words_ending_clauses[$upper_data])) {

                $in_group_by = false;
                $in_order_by = false;
                $in_having   = false;
                $in_where    = false;
                $in_select_expr = false;
                $in_from = false;
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
                $in_group_concat = true;
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
                    $in_group_concat = false;
                }
            }
        }

        // do not add a space after an identifier if followed by a dot
        if ($arr[$i]['type'] == 'alpha_identifier'
            && $i < $size - 1 && $arr[$i + 1]['data'] == '.'
        ) {
            $sep = '';
        }

        // do not add a space after a dot if followed by an identifier
        if ($arr[$i]['data'] == '.' && $i < $size - 1
            && $arr[$i + 1]['type'] == 'alpha_identifier'
        ) {
            $sep = '';
        }

        if ($in_select_expr && $upper_data != 'SELECT'
            && $upper_data != 'DISTINCT'
        ) {
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
                || ($arr[$i]['type'] == 'alpha_identifier')
            ) {
                $where_clause_identifiers[] = $arr[$i]['data'];
            }
        }

        // to grab the rest of the query after the ORDER BY clause
        if (isset($subresult['queryflags']['select_from'])
            && $subresult['queryflags']['select_from'] == 1
            && ! $in_order_by
            && $seen_order_by
            && $upper_data != 'BY'
        ) {
            $unsorted_query .= $arr[$i]['data'];
            if ($arr[$i]['type'] != 'punct_bracket_open_round'
                && $arr[$i]['type'] != 'punct_bracket_close_round'
                && $arr[$i]['type'] != 'punct'
            ) {
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

    $seen_foreign = false;
    $seen_references = false;
    $seen_constraint = false;
    $foreign_key_number = -1;
    $seen_create_table = false;
    $seen_create = false;
    $seen_alter = false;
    $in_create_table_fields = false;
    $brackets_level = 0;
    $in_timestamp_options = false;
    $seen_default = false;

    for ($i = 0; $i < $size; $i++) {
        if ($arr[$i]['type'] == 'alpha_reservedWord') {
            $upper_data = strtoupper($arr[$i]['data']);

            if ($upper_data == 'NOT' && $in_timestamp_options) {
                $create_table_fields[$current_identifier]['timestamp_not_null'] = true;

            }

            if ($upper_data == 'CREATE') {
                $seen_create = true;
            }

            if ($upper_data == 'ALTER') {
                $seen_alter = true;
            }

            if ($upper_data == 'TABLE' && $seen_create) {
                $seen_create_table = true;
                $create_table_fields = array();
            }

            if ($upper_data == 'CURRENT_TIMESTAMP') {
                if ($in_timestamp_options) {
                    if ($seen_default) {
                        $create_table_fields[$current_identifier]['default_current_timestamp'] = true;
                    }
                }
            }

            if ($upper_data == 'CONSTRAINT') {
                $foreign_key_number++;
                $seen_foreign = false;
                $seen_references = false;
                $seen_constraint = true;
            }
            if ($upper_data == 'FOREIGN') {
                $seen_foreign = true;
                $seen_references = false;
                $seen_constraint = false;
            }
            if ($upper_data == 'REFERENCES') {
                $seen_foreign = false;
                $seen_references = true;
                $seen_constraint = false;
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
                            || $third_upper_data == 'RESTRICT'
                        ) {
                            $value = $third_upper_data;
                        } elseif ($third_upper_data == 'SET'
                            || $third_upper_data == 'NO'
                        ) {
                            if ($arr[$i+3]['type'] == 'alpha_reservedWord') {
                                $value = $third_upper_data . '_' . strtoupper($arr[$i+3]['data']);
                            }
                        } elseif ($third_upper_data == 'CURRENT_TIMESTAMP') {
                            if ($clause == 'on_update'
                                && $in_timestamp_options
                            ) {
                                $create_table_fields[$current_identifier]['on_update_current_timestamp'] = true;
                                $seen_default = false;
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
                $in_create_table_fields = true;
            }
        }


        if ($arr[$i]['type'] == 'punct_bracket_close_round') {
            $brackets_level--;
            if ($seen_references) {
                $seen_references = false;
            }
            if ($seen_create_table && $brackets_level == 0) {
                $in_create_table_fields = false;
            }
        }

        if (($arr[$i]['type'] == 'alpha_columnAttrib')) {
            $upper_data = strtoupper($arr[$i]['data']);
            if ($seen_create_table && $in_create_table_fields) {
                if ($upper_data == 'DEFAULT') {
                    $seen_default = true;
                    $create_table_fields[$current_identifier]['default_value'] = $arr[$i + 1]['data'];
                }
            }
        }

        /**
         * @see @todo 2005-10-16 note: the "or" part here is a workaround for a bug
         */
        if (($arr[$i]['type'] == 'alpha_columnType')
            || ($arr[$i]['type'] == 'alpha_functionName' && $seen_create_table)
        ) {
            $upper_data = strtoupper($arr[$i]['data']);
            if ($seen_create_table && $in_create_table_fields
                && isset($current_identifier)
            ) {
                $create_table_fields[$current_identifier]['type'] = $upper_data;
                if ($upper_data == 'TIMESTAMP') {
                    $arr[$i]['type'] = 'alpha_columnType';
                    $in_timestamp_options = true;
                } else {
                    $in_timestamp_options = false;
                    if ($upper_data == 'CHAR') {
                        $arr[$i]['type'] = 'alpha_columnType';
                    }
                }
            }
        }


        if ($arr[$i]['type'] == 'quote_backtick'
            || $arr[$i]['type'] == 'alpha_identifier'
        ) {

            if ($arr[$i]['type'] == 'quote_backtick') {
                // remove backquotes
                $identifier = PMA_Util::unQuote($arr[$i]['data']);
            } else {
                $identifier = $arr[$i]['data'];
            }

            if ($seen_create_table && $in_create_table_fields) {
                $current_identifier = $identifier;
                // we set this one even for non TIMESTAMP type
                $create_table_fields[$current_identifier]['timestamp_not_null'] = false;
            }

            if ($seen_constraint) {
                $foreign[$foreign_key_number]['constraint'] = $identifier;
            }

            if ($seen_foreign && $brackets_level > 0) {
                $foreign[$foreign_key_number]['index_list'][] = $identifier;
            }

            if ($seen_references) {
                if ($seen_alter && $brackets_level > 0) {
                    $foreign[$foreign_key_number]['ref_index_list'][] = $identifier;
                    // here, the first bracket level corresponds to the
                    // bracket of CREATE TABLE
                    // so if we are on level 2, it must be the index list
                    // of the foreign key REFERENCES
                } elseif ($brackets_level > 1) {
                    $foreign[$foreign_key_number]['ref_index_list'][] = $identifier;
                } elseif ($arr[$i+1]['type'] == 'punct_qualifier') {
                    // identifier is `db`.`table`
                    // the first pass will pick the db name
                    // the next pass will pick the table name
                    $foreign[$foreign_key_number]['ref_db_name'] = $identifier;
                } else {
                    // identifier is `table`
                    $foreign[$foreign_key_number]['ref_table_name'] = $identifier;
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
 * @param array $arr The SQL queries html formatted
 *
 * @return array   The colorized SQL queries
 *
 * @todo check why adding a "\n" after the </span> would cause extra blanks
 * to be displayed: SELECT p . person_name
 *
 * @access public
 */
function PMA_SQP_formatHtml_colorize($arr)
{
    $i         = PMA_strpos($arr['type'], '_');
    $class     = '';
    if ($i > 0) {
        $class = 'syntax_' . PMA_substr($arr['type'], 0, $i) . ' ';
    }

    $class     .= 'syntax_' . $arr['type'];

    return '<span class="' . $class . '">'
        . htmlspecialchars($arr['data']) . '</span>';
} // end of the "PMA_SQP_formatHtml_colorize()" function


/**
 * Formats SQL queries to html
 *
 * @param array   $arr              The SQL queries
 * @param string  $mode             mode of printing
 * @param integer $start_token      starting token
 * @param integer $number_of_tokens number of tokens to format, -1 = all
 *
 * @return string  The formatted SQL queries
 *
 * @access public
 */
function PMA_SQP_formatHtml(
    $arr, $mode='color', $start_token=0,
    $number_of_tokens=-1
) {
    global $PMA_SQPdata_operators_docs, $PMA_SQPdata_functions_docs;

    //DEBUG echo 'in Format<pre>'; print_r($arr); echo '</pre>';
    // then check for an array
    if (! is_array($arr)) {
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
        $docu                               = true;
        break;
    case 'query_only':
        $str                                = '';
        $html_line_break                    = "\n";
        $docu                               = false;
        break;
    case 'text':
        $str                                = '';
        $html_line_break                    = '<br />';
        $docu                               = true;
        break;
    } // end switch
    // inner_sql is a span that exists for all cases, except query_only
    // of $cfg['SQP']['fmtType'] to make possible a replacement
    // for inline editing
    if ($mode!='query_only') {
        $str .= '<span class="inner_sql">';
    }
    $close_docu_link = false;
    $indent                                     = 0;
    $bracketlevel                               = 0;
    $functionlevel                              = 0;
    $infunction                                 = false;
    $space_punct_listsep                        = ' ';
    $space_punct_listsep_function_name          = ' ';
    // $space_alpha_reserved_word = '<br />'."\n";
    $space_alpha_reserved_word                  = ' ';

    $keywords_with_brackets_1before            = array(
        'INDEX' => 1,
        'KEY' => 1,
        'ON' => 1,
        'USING' => 1
    );

    $keywords_with_brackets_2before            = array(
        'IGNORE' => 1,
        'INDEX' => 1,
        'INTO' => 1,
        'KEY' => 1,
        'PRIMARY' => 1,
        'PROCEDURE' => 1,
        'REFERENCES' => 1,
        'UNIQUE' => 1,
        'USE' => 1
    );

    // These reserved words do NOT get a newline placed near them.
    $keywords_no_newline               = array(
        'AS' => 1,
        'ASC' => 1,
        'DESC' => 1,
        'DISTINCT' => 1,
        'DUPLICATE' => 1,
        'HOUR' => 1,
        'INTERVAL' => 1,
        'IS' => 1,
        'LIKE' => 1,
        'NOT' => 1,
        'NULL' => 1,
        'ON' => 1,
        'REGEXP' => 1
    );

    // These reserved words introduce a privilege list
    $keywords_priv_list                = array(
        'GRANT' => 1,
        'REVOKE' => 1
    );

    if ($number_of_tokens == -1) {
        $number_of_tokens = $arr['len'];
    }
    $typearr   = array();
    if ($number_of_tokens >= 0) {
        $typearr[0] = '';
        $typearr[1] = '';
        $typearr[2] = '';
        $typearr[3] = $arr[$start_token]['type'];
    }

    $in_priv_list = false;
    for ($i = $start_token; $i < $number_of_tokens; $i++) {
        // DEBUG echo "Loop format <strong>" . $arr[$i]['data']
        // . "</strong> " . $arr[$i]['type'] . "<br />";
        $before = '';
        $after  = '';
        // array_shift($typearr);
        /*
        0 prev2
        1 prev
        2 current
        3 next
        */
        if (($i + 1) < $number_of_tokens) {
            $typearr[4] = $arr[$i + 1]['type'];
        } else {
            $typearr[4] = '';
        }

        for ($j=0; $j<4; $j++) {
            $typearr[$j] = $typearr[$j + 1];
        }

        switch ($typearr[2]) {
        case 'alpha_bitfield_constant_introducer':
            $before     = ' ';
            $after      = '';
            break;
        case 'white_newline':
            $before     = '';
            break;
        case 'punct_bracket_open_round':
            $bracketlevel++;
            $infunction = false;
            $keyword_brackets_2before = isset(
                $keywords_with_brackets_2before[strtoupper($arr[$i - 2]['data'])]
            );
            $keyword_brackets_1before = isset(
                $keywords_with_brackets_1before[strtoupper($arr[$i - 1]['data'])]
            );
            // Make sure this array is sorted!
            if (($typearr[1] == 'alpha_functionName')
                || ($typearr[1] == 'alpha_columnType') || ($typearr[1] == 'punct')
                || ($typearr[3] == 'digit_integer') || ($typearr[3] == 'digit_hex')
                || ($typearr[3] == 'digit_float')
                || ($typearr[0] == 'alpha_reservedWord' && $keyword_brackets_2before)
                || ($typearr[1] == 'alpha_reservedWord' && $keyword_brackets_1before)
            ) {
                $functionlevel++;
                $infunction = true;
                $after      .= ' ';
            } else {
                $indent++;
                if ($mode != 'query_only') {
                    $after .= '<div class="syntax_indent' . $indent . '">';
                } else {
                    $after .= ' ';
                }
            }
            break;
        case 'alpha_identifier':
            if (($typearr[1] == 'punct_qualifier')
                || ($typearr[3] == 'punct_qualifier')
            ) {
                $after      = '';
                $before     = '';
            }
            // for example SELECT 1 somealias
            if ($typearr[1] == 'digit_integer') {
                $before     = ' ';
            }
            if (($typearr[3] == 'alpha_columnType')
                || ($typearr[3] == 'alpha_identifier')
            ) {
                $after      .= ' ';
            }
            break;
        case 'punct_user':
        case 'punct_qualifier':
            $before         = '';
            $after          = '';
            break;
        case 'punct_listsep':
            if ($infunction == true) {
                $after      .= $space_punct_listsep_function_name;
            } else {
                $after      .= $space_punct_listsep;
            }
            break;
        case 'punct_queryend':
            if (($typearr[3] != 'comment_mysql')
                && ($typearr[3] != 'comment_ansi')
                && $typearr[3] != 'comment_c'
            ) {
                $after     .= $html_line_break;
                $after     .= $html_line_break;
            }
            $space_punct_listsep               = ' ';
            $space_punct_listsep_function_name = ' ';
            $space_alpha_reserved_word         = ' ';
            $in_priv_list                      = false;
            break;
        case 'comment_mysql':
        case 'comment_ansi':
            $after         .= $html_line_break;
            break;
        case 'punct':
            $before         .= ' ';
            if ($docu && isset($PMA_SQPdata_operators_docs[$arr[$i]['data']])
                && ($arr[$i]['data'] != '*' || in_array($arr[$i]['type'], array('digit_integer','digit_float','digit_hex')))
            ) {
                $before .= PMA_Util::showMySQLDocu(
                    'functions',
                    $PMA_SQPdata_operators_docs[$arr[$i]['data']]['link'],
                    false,
                    $PMA_SQPdata_operators_docs[$arr[$i]['data']]['anchor'],
                    true
                );
                $after .= '</a>';
            }

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
            // only close bracket level when it was opened before
            if ($bracketlevel > 0) {
                $bracketlevel--;
                if ($infunction == true) {
                    $functionlevel--;
                    $after     .= ' ';
                    $before    .= ' ';
                } else {
                    $indent--;
                    $before    .= ($mode != 'query_only' ? '</div>' : ' ');
                }
                $infunction    = ($functionlevel > 0) ? true : false;
            }
            break;
        case 'alpha_columnType':
            if ($docu) {
                switch ($arr[$i]['data']) {
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'bigint':
                case 'decimal':
                case 'float':
                case 'double':
                case 'real':
                case 'bit':
                case 'boolean':
                case 'serial':
                    $before .= PMA_Util::showMySQLDocu(
                        'data-types',
                        'numeric-types',
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                    break;
                case 'date':
                case 'datetime':
                case 'timestamp':
                case 'time':
                case 'year':
                    $before .= PMA_Util::showMySQLDocu(
                        'data-types',
                        'date-and-time-types',
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                    break;
                case 'char':
                case 'varchar':
                case 'tinytext':
                case 'text':
                case 'mediumtext':
                case 'longtext':
                case 'binary':
                case 'varbinary':
                case 'tinyblob':
                case 'mediumblob':
                case 'blob':
                case 'longblob':
                case 'enum':
                case 'set':
                    $before .= PMA_Util::showMySQLDocu(
                        'data-types',
                        'string-types',
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                    break;
                }
            }
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
            if ($typearr[1] == 'alpha_identifier'
                || $typearr[1] == 'alpha_charset'
            ) {
                $before .= ' ';
            }
            if (($typearr[3] == 'alpha_columnAttrib')
                || ($typearr[3] == 'quote_single')
                || ($typearr[3] == 'digit_integer')
            ) {
                $after     .= ' ';
            }
            // workaround for
            // AUTO_INCREMENT = 31DEFAULT_CHARSET = utf-8

            if ($typearr[2] == 'alpha_columnAttrib'
                && $typearr[3] == 'alpha_reservedWord'
            ) {
                $before .= ' ';
            }
            // workaround for
            // select * from mysql.user where binary user="root"
            // binary is marked as alpha_columnAttrib
            // but should be marked as a reserved word
            if (strtoupper($arr[$i]['data']) == 'BINARY'
                && $typearr[3] == 'alpha_identifier'
            ) {
                $after     .= ' ';
            }
            break;
        case 'alpha_functionName':
            $funcname = strtoupper($arr[$i]['data']);
            if ($docu && isset($PMA_SQPdata_functions_docs[$funcname])) {
                $before .= PMA_Util::showMySQLDocu(
                    'functions',
                    $PMA_SQPdata_functions_docs[$funcname]['link'],
                    false,
                    $PMA_SQPdata_functions_docs[$funcname]['anchor'],
                    true
                );
                $after .= '</a>';
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
                && isset($keywords_no_newline[strtoupper($arr[$i - 1]['data'])])))
                && ($typearr[1] != 'punct_level_plus')
                && (!isset($keywords_no_newline[$arr[$i]['data']]))
            ) {
                // do not put a space before the first token, because
                // we use a lot of pattern matching checking for the
                // first reserved word at beginning of query
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

                    if (!$in_priv_list
                        || $typearr[1] == 'alpha_identifier'
                        || $typearr[1] == 'quote_single'
                        || $typearr[1] == 'white_newline'
                    ) {
                        $before    .= $space_alpha_reserved_word;
                    }
                } else {
                    // on first keyword, check if it introduces a
                    // privilege list
                    if (isset($keywords_priv_list[$arr[$i]['data']])) {
                        $in_priv_list = true;
                    }
                }
            } else {
                $before    .= ' ';
            }

            switch ($arr[$i]['data']) {
            case 'CREATE':
            case 'ALTER':
            case 'DROP':
            case 'RENAME';
            case 'TRUNCATE':
            case 'ANALYZE':
            case 'ANALYSE':
            case 'OPTIMIZE':
                if ($docu) {
                    switch ($arr[$i + 1]['data']) {
                    case 'EVENT':
                    case 'TABLE':
                    case 'TABLESPACE':
                    case 'FUNCTION':
                    case 'INDEX':
                    case 'PROCEDURE':
                    case 'TRIGGER':
                    case 'SERVER':
                    case 'DATABASE':
                    case 'VIEW':
                        $before .= PMA_Util::showMySQLDocu(
                            'SQL-Syntax',
                            $arr[$i]['data'] . '_' . $arr[$i + 1]['data'],
                            false,
                            '',
                            true
                        );
                        $close_docu_link = true;
                        break;
                    }
                    if ($arr[$i + 1]['data'] == 'LOGFILE'
                        && $arr[$i + 2]['data'] == 'GROUP'
                    ) {
                        $before .= PMA_Util::showMySQLDocu(
                            'SQL-Syntax',
                            $arr[$i]['data'] . '_LOGFILE_GROUP',
                            false,
                            '',
                            true
                        );
                        $close_docu_link = true;
                    }
                }
                if (!$in_priv_list) {
                    $space_punct_listsep       = $html_line_break;
                    $space_alpha_reserved_word = ' ';
                }
                break;
            case 'EVENT':
            case 'TABLESPACE':
            case 'TABLE':
            case 'FUNCTION':
            case 'INDEX':
            case 'PROCEDURE':
            case 'SERVER':
            case 'TRIGGER':
            case 'DATABASE':
            case 'VIEW':
            case 'GROUP':
                if ($close_docu_link) {
                    $after = '</a>' . $after;
                    $close_docu_link = false;
                }
                break;
            case 'SET':
                if ($docu && ($i == 0 || $arr[$i - 1]['data'] != 'CHARACTER')) {
                    $before .= PMA_Util::showMySQLDocu(
                        'SQL-Syntax',
                        $arr[$i]['data'],
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                }
                if (!$in_priv_list) {
                    $space_punct_listsep       = $html_line_break;
                    $space_alpha_reserved_word = ' ';
                }
                break;
            case 'EXPLAIN':
            case 'DESCRIBE':
            case 'DELETE':
            case 'SHOW':
            case 'UPDATE':
                if ($docu) {
                    $before .= PMA_Util::showMySQLDocu(
                        'SQL-Syntax',
                        $arr[$i]['data'],
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                }
                if (!$in_priv_list) {
                    $space_punct_listsep       = $html_line_break;
                    $space_alpha_reserved_word = ' ';
                }
                break;
            case 'INSERT':
            case 'REPLACE':
                if ($docu) {
                    $before .= PMA_Util::showMySQLDocu(
                        'SQL-Syntax',
                        $arr[$i]['data'],
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                }
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
                if ($docu) {
                    $before .= PMA_Util::showMySQLDocu(
                        'SQL-Syntax',
                        'SELECT',
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                }
                $space_punct_listsep       = ' ';
                $space_alpha_reserved_word = $html_line_break;
                break;
            case 'CALL':
            case 'DO':
            case 'HANDLER':
                if ($docu) {
                    $before .= PMA_Util::showMySQLDocu(
                        'SQL-Syntax',
                        $arr[$i]['data'],
                        false,
                        '',
                        true
                    );
                    $after = '</a>' . $after;
                }
                break;
            default:
                if ($close_docu_link
                    && in_array(
                        $arr[$i]['data'],
                        array('LIKE', 'NOT', 'IN', 'REGEXP', 'NULL')
                    )
                ) {
                    $after .= '</a>';
                    $close_docu_link = false;
                } else if ($docu
                    && isset($PMA_SQPdata_functions_docs[$arr[$i]['data']])
                ) {
                    /* Handle multi word statements first */
                    if (isset($typearr[4])
                        && $typearr[4] == 'alpha_reservedWord'
                        && $typearr[3] == 'alpha_reservedWord'
                        && isset($PMA_SQPdata_functions_docs[strtoupper(
                            $arr[$i]['data'] . '_'
                            . $arr[$i + 1]['data'] . '_'
                            . $arr[$i + 2]['data']
                        )])
                    ) {
                        $tempname = strtoupper(
                            $arr[$i]['data'] . '_'
                            . $arr[$i + 1]['data'] . '_'
                            . $arr[$i + 2]['data']
                        );
                        $before .= PMA_Util::showMySQLDocu(
                            'functions',
                            $PMA_SQPdata_functions_docs[$tempname]['link'],
                            false,
                            $PMA_SQPdata_functions_docs[$tempname]['anchor'],
                            true
                        );
                        $close_docu_link = true;
                    } else if (isset($typearr[3])
                        && $typearr[3] == 'alpha_reservedWord'
                        && isset($PMA_SQPdata_functions_docs[strtoupper(
                            $arr[$i]['data'] . '_' . $arr[$i + 1]['data']
                        )])
                    ) {
                        $tempname = strtoupper(
                            $arr[$i]['data'] . '_' . $arr[$i + 1]['data']
                        );
                        $before .= PMA_Util::showMySQLDocu(
                            'functions',
                            $PMA_SQPdata_functions_docs[$tempname]['link'],
                            false,
                            $PMA_SQPdata_functions_docs[$tempname]['anchor'],
                            true
                        );
                        $close_docu_link = true;
                    } else {
                        $before .= PMA_Util::showMySQLDocu(
                            'functions',
                            $PMA_SQPdata_functions_docs[$arr[$i]['data']]['link'],
                            false,
                            $PMA_SQPdata_functions_docs[$arr[$i]['data']]['anchor'],
                            true
                        );
                        $after .= '</a>';
                    }
                }
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
            if ($typearr[1] != 'punct_user'
                && $typearr[1] != 'alpha_bitfield_constant_introducer'
            ) {
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
            if ($typearr[3] != 'punct_qualifier'
                && $typearr[3] != 'alpha_variable'
                && $typearr[3] != 'punct_user'
            ) {
                $after     .= ' ';
            }
            if ($typearr[1] != 'punct_qualifier'
                && $typearr[1] != 'alpha_variable'
                && $typearr[1] != 'punct_user'
            ) {
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
        $str .= $before;
        if ($mode=='color') {
            $str .= PMA_SQP_formatHTML_colorize($arr[$i]);
        } elseif ($mode == 'text') {
            $str .= htmlspecialchars($arr[$i]['data']);
        } else {
            $str .= $arr[$i]['data'];
        }
        $str .= $after;
    } // end for
    // close unclosed indent levels
    while ($indent > 0) {
        $indent--;
        $str .= ($mode != 'query_only' ? '</div>' : ' ');
    }
    /* End possibly unclosed documentation link */
    if ($close_docu_link) {
        $str .= '</a>';
        $close_docu_link = false;
    }
    if ($mode!='query_only') {
        // close inner_sql span
            $str .= '</span>';
    }
    if ($mode=='color') {
        // close syntax span
        $str .= '</span>';
    }

    return $str;
} // end of the "PMA_SQP_formatHtml()" function

/**
 * Gets SQL queries with no format
 *
 * @param array $arr The SQL queries list
 *
 * @return string  The SQL queries with no format
 *
 * @access public
 */
function PMA_SQP_formatNone($arr)
{
    $formatted_sql = htmlspecialchars($arr['raw']);
    $formatted_sql = preg_replace(
        "@((\015\012)|(\015)|(\012)){3,}@",
        "\n\n",
        $formatted_sql
    );

    return $formatted_sql;
} // end of the "PMA_SQP_formatNone()" function

/**
 * Checks whether a given name is MySQL reserved word
 *
 * @param string $column The word to be checked
 *
 * @return boolean whether true or false
 */
function PMA_SQP_isKeyWord($column)
{
    global $PMA_SQPdata_forbidden_word;
    return in_array(strtoupper($column), $PMA_SQPdata_forbidden_word);
}

?>
