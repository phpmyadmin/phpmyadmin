<?php
/* $Id$ */


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
 * (returned structure of this function is being rewritten presently);
 *
 * If you want a pretty-printed version of the query, do:
 * $string = PMA_SQP_formatHtml($parsed_sql);
 * (note that that you need to have syntax.css.php3 included somehow in your
 * page for it to work, I recommend '<link rel="stylesheet" type="text/css"
 * href="syntax.css.php3" />' at the moment.)
 */


if (!defined('PMA_SQP_LIB_INCLUDED')) {
    define('PMA_SQP_LIB_INCLUDED', 1);

    /**
     * Include the string libarry as we use it heavily
     */
    include('./libraries/string.lib.php3');

    /**
     * Include data for the SQL Parser
     */
    include('./libraries/sqlparser.data.php3');

    if (!defined('DEBUG_TIMING')) {
        function PMA_SQP_arrayAdd(&$arr, $type, $data, &$arrsize)
        {
            $arr[] = array('type' => $type, 'data' => $data);
            $arrsize++;
        } // end of the "PMA_SQP_arrayAdd" function
    } else {
        function PMA_SQP_arrayAdd(&$arr, $type, $data, &$arrsize)
        {
            global $timer;

            $t     = $timer;
            $arr[] = array('type' => $type, 'data' => $data , 'time' => $t);
            $timer = microtime();
            $arrsize++;
        } // end of the "PMA_SQP_arrayAdd" function
    } // end if... else...


    function PMA_SQP_parse($sql)
    {
        global $cfg;
        global $PMA_SQPdata_column_attrib, $PMA_SQPdata_reserved_word, $PMA_SQPdata_column_type, $PMA_SQPdata_function_name,
        $PMA_SQPdata_column_attrib_cnt, $PMA_SQPdata_reserved_word_cnt, $PMA_SQPdata_column_type_cnt, $PMA_SQPdata_function_name_cnt;

        // if the SQL parser is disabled just return the original query string
        if ($cfg['SQP']['enable'] == FALSE) {
            // Debug : echo 'FALSE';
            return $sql;
        }

        $len = strlen($sql);
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
        $quote_list              = "\'\"\`";
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
                || (($count2 + 2 < $len) && ($c == '-') && ($sql[$count2 + 1] == '-') && ($sql[$count2 + 2] == ' '))) {
                $count2++;
                $pos  = 0;
                $type = 'bad';
                switch ($c) {
                    case '#':
                        $type = 'mysql';
                    case '-':
                        $type = 'ansi';
                        $pos  = strpos($sql, "\n", $count2);
                        break;
                    case '/':
                        $type = 'c';
                        $pos  = strpos($sql, '*/', $count2);
                        $pos  += 2;
                        break;
                    default:
                        break;
                } // end switch
                $count2 = ($pos < $count2) ? $len : $pos;
                $str    = substr($sql, $count1, $count2 - $count1);
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
                    $pos    = strpos(' ' . $sql, $quotetype, $oldpos + 1) - 1;
                    // ($pos === FALSE)
                    if ($pos < 0) {
                        trigger_error('Syntax: Unclosed quote (' . $quotetype . ') at ' . $startquotepos);
                        return;
                    }

                    // If the quote is the first character, it can't be
                    // escaped, so don't do the rest of the code
                    if ($pos == 0) {
                        break;
                    }

                    // Check for MySQL escaping using a \
                    // And check for ANSI escaping using the $quotetype character
                    if (PMA_STR_charIsEscaped($sql, $pos) ) {
                        $pos ++;
                        continue;
                    } else if (($sql[$pos] == $quotetype) && ($sql[$pos+1] == $quotetype)) {
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
                $data = substr($sql, $count1, $count2 - $count1);
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
                    $punct_data = substr($sql, $count1, $l);
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
                    if (($first == ',') || ($first == ';') || ($first == '.') || ($first = '*')) {
                        $count2     = $count1 + 1;
                        $punct_data = $first;
                    } else if (($last2 == '/*') || ($last2 == '--')) {
                        $count2     -= 2;
                        $punct_data = substr($sql, $count1, $count2 - $count1);
                    } else if (($last == '-') || ($last == '+') || ($last == '!')) {
                        $count2--;
                        $punct_data = substr($sql, $count1, $count2 - $count1);
                    } else {
                        trigger_error('Syntax: Unknown punctation string (' . $punct_data . ') at ' . $count1);
                        return;
                    }
                    PMA_SQP_arrayAdd($sql_array, 'punct', $punct_data, $arraysize);
                    continue;
                } // end if... else if... else
                continue;
            }

            // Checks for alpha
            if (PMA_STR_isSqlIdentifier($c, FALSE) || ($c == '@')) {
                $count2 ++;
                $is_sql_variable         = ($c == '@');
                $is_digit                = (!$is_sql_variable) && PMA_STR_isDigit($c);
                $is_hex_digit            = ($is_digit) && ($c == '0') && ($sql[$count2] == 'x');
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
                            trigger_error('Syntax: Invalid Identifer (' . substr($sql, $count1, $count2 - $count1) . ') at ' . $count1);
                            return;
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
                $str  = substr($sql, $count1, $l);

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
            echo "\n" . '<p>' . "\n";
            echo 'You seem to have found a bug in the SQL parser.<br />Please submit a bug report with the data chunk below:<br />' . "\n" . '--BEGIN CUT--<br />' . "\n";
            $debugstr   = '$Id$<br />' . "\n";
            $debugstr   .= 'Why did we get here? ' . $count1 . ' ' . $count2 . ' ' . $len . '<br />' . "\n";
            $debugstr   .= 'Leftover: ' . substr($sql, $count1, $count2 - $count1) . '<br />' . "\n";
            $debugstr   .= 'A: ' . $count1 . ' ' . $count2 . '<br />' . "\n";
            $debugstr   .= 'SQL: ' . $sql;
            if (PMA_PHP_INT_VERSION >= 40001 && @function_exists('gzcompress')) {
                $debugstr = gzcompress($debugstr, 9);
            }
            $encodedstr = nl2br(chunk_split(base64_encode($debugstr)));
            echo $encodedstr . "\n";
            echo '---END CUT---<br /><br />' . "\n\n";
            echo '</p>' . "\n";
            flush();
            if (PMA_PHP_INT_VERSION >= 42000 && @function_exists('ob_flush')) { 
                ob_flush();
            }
            die();
        } // end while ($count2 < $len)


        if ($arraysize > 0) {
            $t_next     = $sql_array[0]['type'];
            $t_prev     = NULL;
            $t_cur      = NULL;
        }

        for ($i = 0; $i < $arraysize; $i++) {
            $t_prev     = $t_cur;
            $t_cur      = $t_next;
            if (($i + 1) < $arraysize) {
                $t_next = $sql_array[$i + 1]['type'];
            } else {
                $t_next = NULL;
            }
            if ($t_cur == 'alpha') {
                $t_suffix     = '_identifier';
                $d_cur_upper  = strtoupper($sql_array[$i]['data']);
                if (($t_next == 'punct_qualifier') || ($t_prev == 'punct_qualifier')) {
                    $t_suffix = '_identifier';
                } else if (($t_next == 'punct_bracket_open_round')
                            && PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_function_name, $PMA_SQPdata_function_name_cnt)) {
                    $t_suffix = '_functionName';
                } else if (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_reserved_word, $PMA_SQPdata_reserved_word_cnt)) {
                    $t_suffix = '_reservedWord';
                } else if (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_column_type, $PMA_SQPdata_column_type_cnt)) {
                    $t_suffix = '_columnType';
                } else if (PMA_STR_binarySearchInArr($d_cur_upper, $PMA_SQPdata_column_attrib, $PMA_SQPdata_column_attrib_cnt)) {
                    $t_suffix = '_columnAttrib';
                } else {
                    // Do nothing
                }
                $sql_array[$i]['type'] .= $t_suffix;
            }
        }

        // Stores the size of the array inside the array, as count() is a slow
        // operation.
        $sql_array['len'] = $arraysize;

        // Sends the data back
        return $sql_array;
    } // end of the "PMA_SQP_parse()" function


    function PMA_SQP_analyze($arr)
    {
        $result          = array();
        $size            = $arr['len'];
        $subresult       = array(
            'querytype'      => '',
            'list_db'        => array(),
            'list_tbl'       => array(),
            'list_tbl_alias' => array(),
            'list_col'       => array(),
            'list_col_alias' => array()
        );
        $subresult_empty = $subresult;
        $seek_queryend   = FALSE;

        $supported_query_types = array(
            'SELECT',
            'UPDATE',
            'DELETE',
            'INSERT',
            'REPLACE',
            'TRUNCATE'
            /*
            // Support for these additional query types will come later on.
            // They are not needed yet
            'EXPLAIN',
            'DESCRIBE',
            'SHOW',
            'CREATE',
            'SET',
            'ALTER'
            */
        );
        $supported_query_types_cnt = count($supported_query_types);

        for ($i = 0; $i < $size; $i++) {
            // High speed seek for locating the end of the current query
            if ($seek_queryend == TRUE) {
                if ($arr[$i]['type'] == 'punct_queryend') {
                    $seek_queryend = FALSE;
                } else {
                    continue;
                }
            }

            switch ($arr[$i]['type']) {
                case 'punct_queryend':
                    $result[]  = $subresult;
                    $subresult = $subresult_empty;
                    break;
                case 'alpha_reservedWord':
                    // We don't know what type of query yet, so run this
                    if ($subresult['querytype'] == '') {
                        $subresult['querytype'] = strtoupper($arr[$i]['data']);
                    }
                    // Check if we support this type of query
                    if (!PMA_STR_binarySearchInArr($subresult['querytype'], $supported_query_types, $supported_query_types_cnt)) {
                        // Skip ahead to the next one if we don't
                        $seek_queryend = TRUE;
                    }
                    break;
                default:
                    break;
            } // end switch

            switch ($subresult['querytype']) {
                case 'SELECT':
                    break;
                default:
                    break;
            } // end switch
        }

        // They are naughty and didn't have a trailing semi-colon,
        // then still handle it properly
        if ($subresult['querytype'] != '') {
            $result[] = $subresult;
        }

        return $result;
    } // end of the "PMA_SQP_analyze()" function


    function PMA_SQP_formatHtml_colorize($arr)
    {
        $i         = strpos($arr['type'], '_');
        $class     = '';
        if ($i > 0) {
            $class = 'syntax_' . substr($arr['type'], 0, $i) . ' ';
        }

        $class     .= 'syntax_' . $arr['type'];

        return '<span class="' . $class . '">' . htmlspecialchars($arr['data']) . '</span>';
    } // end of the "PMA_SQP_formatHtml_colorize()" function


    function PMA_SQP_formatHtml($arr)
    {
        $str                                        = '<span class="syntax">';
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
            'ON',
            'USING'
        );
        $keywords_with_brackets_1before_cnt        = 3;
            
        $keywords_with_brackets_2before            = array(
            'IGNORE',
            'INDEX',
            'INTO',
            'KEY',
            'PRIMARY',
            'PROCEDURE',
            'REFERENCES',
            'UNIQUE',
            'USE',
        );
        // $keywords_with_brackets_2before_cnt = count($keywords_with_brackets_2before);
        $keywords_with_brackets_2before_cnt        = 9;

        // These reserved words do NOT get a newline placed near them.
        $keywords_no_newline               = array(
            'AND',
            'AS',
            'ASC',
            'DESC',
            'IS',
            'NOT',
            'NULL',
            'ON',
            'OR'
        );
        $keywords_no_newline_cnt           = 9;

        $arraysize = $arr['len'];
        $typearr   = array();
        if ($arraysize >= 0) {
            $typearr[0] = NULL;
            $typearr[1] = NULL;
            $typearr[2] = NULL;
            $typearr[3] = $arr[0]['type'];
        }

        for ($i = 0; $i < $arraysize; $i++) {
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
                $typearr[4] = NULL;
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
                        $after      .= '<div class="syntax_indent' . $indent . '">';
                    }
                    break;
                case 'alpha_identifier':
                    if (($typearr[1] == 'punct_qualifier') || ($typearr[3] == 'punct_qualifier')) {
                        $after      = '';
                        $before     = '';
                    }
                    if ($typearr[3] == 'alpha_columnType') {
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
                    if (($typearr[3] != 'comment_mysql') && ($typearr[3] != 'comment_ansi')) {
                        $after     .= '<br />';
                        $after     .= '<br />';
                    }
                    $space_punct_listsep               = ' ';
                    $space_punct_listsep_function_name = ' ';
                    $space_alpha_reserved_word         = ' ';
                    break;
                case 'comment_mysql':
                case 'comment_ansi':
                    $after         .= '<br />';
                    break;
                case 'punct':
                    $after         .= ' ';
                    $before        .= ' ';
                    break;
                case 'punct_bracket_close_round':
                    $bracketlevel--;
                    if ($infunction == TRUE) {
                        $functionlevel--;
                        $after     .= ' ';
                        $before .= ' ';
                    } else {
                        $indent--;
                        $before    .= '</div>';
                    }
                    $infunction    = ($functionlevel > 0) ? TRUE : FALSE;
                    break;
                case 'alpha_columnType':
                    if ($typearr[3] == 'alpha_columnAttrib') {
                        $after     .= ' ';
                    }
                    if ($typearr[1] == 'alpha_columnType') {
                        $before .= ' ';
                    }
                    break;
                case 'alpha_reservedWord':
                    $upper         = $arr[$i]['data'];
                    if ((($typearr[1] != 'alpha_reservedWord') 
                        || (($typearr[1] == 'alpha_reservedWord') 
                            && PMA_STR_binarySearchInArr(strtoupper($arr[$i-1]['data']), $keywords_no_newline, $keywords_no_newline_cnt)))
                        && ($typearr[1] != 'punct_level_plus')  
                        && (!PMA_STR_binarySearchInArr($upper, $keywords_no_newline, $keywords_no_newline_cnt))) {
                        $before    .= $space_alpha_reserved_word;
                    } else {
                        $before    .= ' ';
                    }

                    switch ($upper) {
                        case 'CREATE':
                            $space_punct_listsep       = '<br />';
                            $space_alpha_reserved_word = ' ';
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
                            $space_punct_listsep       = '<br />';
                            $space_alpha_reserved_word = ' ';
                            break;
                        case 'INSERT':
                        case 'REPLACE':
                            $space_punct_listsep       = '<br />';
                            $space_alpha_reserved_word = '<br />';
                            break;
                        case 'VALUES':
                            $space_punct_listsep       = ' ';
                            $space_alpha_reserved_word = '<br />';
                            break;
                        case 'SELECT':
                            $space_punct_listsep       = ' ';
                            $space_alpha_reserved_word = '<br />';
                            break;
                        default:
                            break;
                    } // end switch ($upper)

                    $after         .= ' ';
                    break;
                case 'digit_integer':
                case 'digit_float':
                case 'digit_hex':
                    if ($infunction && $typearr[3] == 'punct_bracket_close_round') {
                        $after     .= ' ';
                    }
                    break;
                case 'quote_double':
                case 'quote_single':
                    $before        .= ' ';
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
            $str .= $before . PMA_SQP_formatHTML_colorize($arr[$i]) . $after;
        } // end for
        $str .= '</span>';

        return $str;
    } // end of the "PMA_SQP_formatHtml()" function


    function PMA_SQP_buildCssRule($classname, $property, $value)
    {
        $str     = '.' . $classname . ' {';
        if ($value != '') {
            $str .= $property . ': ' . $value . ';';
        }
        $str     .= '}' . "\n";

        return $str;
    } // end of the "PMA_SQP_buildCssRule()" function


    function PMA_SQP_buildCssData()
    {
        global $cfg;

        $css_string     = '';
        while (list($key, $col) = each($cfg['SQP']['fmtColor'])) {
            $css_string .= PMA_SQP_buildCssRule('syntax_' . $key, 'color', $col);
        }
        for ($i = 0; $i < 8; $i++) {
            $css_string .= PMA_SQP_buildCssRule('syntax_indent' . $i, 'margin-left', ($i * $cfg['SQP']['fmtInd']) . $cfg['SQP']['fmtIndUnit']);
        }

        return $css_string;
    } // end of the "PMA_SQP_buildCssData()" function


    function PMA_SQP_formatNone($arr)
    {
        $formatted_sql = htmlspecialchars($arr['raw']);
        $formatted_sql = ereg_replace("((\015\012)|(\015)|(\012)){3,}", "\n\n", $formatted_sql);

        return $formatted_sql;
    } // end of the "PMA_SQP_formatNone()" function


    function PMA_SQP_formatText($arr)
    {
        /**
         * TODO WRITE THIS!
         */
         return PMA_SQP_formatNone($arr);
    } // end of the "PMA_SQP_formatText()" function

} // $__PMA_SQP_LIB__
