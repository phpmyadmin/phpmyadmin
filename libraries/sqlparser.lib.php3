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
 * $parsedSQL = PMA_SQP_Parse($sql);
 *
 * If you want to extract data from it then, you just need to run
 * $SQLinfo = PMA_SQP_Analyze($parsedSQL);
 * (returned structure of this function is being rewritten presently);
 *
 * If you want a pretty-printed version of the query, do:
 * $string = PMA_SQP_FormatHTML($parsedSQL);
 * (note that that you need to have syntax.css.php3 included somehow in your
 * page for it to work, I recommend '<link rel="stylesheet" type="text/css"
 * href="syntax.css.php3" />' at the moment.)
 *
 */


if (!defined('PMA_SQP_LIB_INCLUDED')) {
    define('PMA_SQP_LIB_INCLUDED', 1);

    require('./libraries/string.lib.php3');
    require('./libraries/sqlparser.data.php3');

    if (!defined('DEBUGTIMING')) {
        function PMA_SQP_ArrayAdd(&$arr,$type,$data, &$arrsize)
        {
            $arr[] = array( 'type' => $type, 'data' => $data );
            $arrsize++;
        }
    } else {
        function PMA_SQP_ArrayAdd(&$arr,$type,$data, &$arrsize)
        {	
            global $timer;
            $t = $timer;
            $arr[] = array( 'type' => $type, 'data' => $data ,  'time' => $t );
            $timer = microtime();
            $arrsize++;
        }
    }

    function PMA_SQP_Parse($sql)
    {	
        $len = strlen($sql);
        if ($len == 0) {
            return array();
        }
        $sql_array = array();
        $sql_array['raw'] = $sql;
        $count1 = 0;
        $count2 = 0;
        $punct_queryend = ';';
        $punct_qualifier = '.';
        $punct_listsep = ',';
        $punct_level_plus = '(';
        $punct_level_minus = ')';
        $digit_floatdecimal = '.';
        $digit_hexset = 'x';
        $bracket_list = '()[]{}';
        $allpunct_list = '-,;:!?/.^~\*&%+<=>|';
        $allpunct_list_pair = array (
            0 => '!=',
            1 => '&&',
            2 => ':=',
            3 => '<<',
            4 => '<=',
            5 => '<=>',
            6 => '<>',
            7 => '>=',
            8 => '>>',
            9 => '||',
        );

        $allpunct_list_pair_size = 10; //count($allpunct_list_pair);
        $quote_list = "\'\"\`";
        $arraysize = 0;
        while($count2 < $len) {
            $c = $sql[$count2];
            $count1 = $count2;

            if ( ($c == "\n") ) {
                $count2++;
                PMA_SQP_ArrayAdd( $sql_array, 'white_newline', '', $arraysize);
                continue;
            }

            //check for white space
            if (PMA_STR_IsSpace($c)) {
                $count2++;
                continue;
            }

            // check for comment lines. 
            // MySQL style #
            // C style /* */
            // ANSI style -- 
            if ( ($c == '#') || (($count2+1 < $len) && ($c == '/') && ($sql[$count2+1] == '*')) || (($c == '-') && ($count2+2 < $len) && ($sql[$count2+1] == '-') && ($sql[$count2+2] == ' '))) {
                $count2++;
                $pos = 0;
                $type = 'bad'; 
                switch($c) {   
                    case '#': 
                        $type = 'mysql'; 
                    case '-': 
                        $type = 'ansi';
                        $pos = strpos($sql,"\n",$count2);
                        break;
                    case '/': 
                        $type = 'c';
                        $pos = strpos($sql,"*/",$count2);
                        $pos += 2;
                        break;
                    default: 
                        break;
                }
                $count2 = ($pos < $count2) ? $len : $pos;
                $str = substr($sql,$count1,$count2-$count1);
                PMA_SQP_ArrayAdd ( $sql_array, 'comment_'.$type, $str, $arraysize);
                continue; 
            }

            //check for something inside quotation marks
            if (PMA_STR_StrInStr($c,$quote_list)) {
                $startquotepos = $count2;
                $quotetype = $c;
                $count2++;
                $escaped = FALSE;
                $escaped_escaped = FALSE;
                $pos = $count2;
                $oldpos = 0;
                do {
                    $oldpos = $pos;
                    $pos = strpos($sql,$quotetype,$oldpos);
                    // ($pos === FALSE)
                    if (!is_integer($pos)) {
                        trigger_error('Syntax: Unclosed quote ('.$quotetype.') at '.$startquotepos);
                        return;
                    }

                    //if the quote is the first character,
                    //it can't be escaped, so don't do the rest of the code
                    if ($pos == 0) {
                        break;
                    }

                    if (PMA_STR_CharIsEscaped($sql,$pos)) {
                        $pos ++;
                        continue;
                    } else {
                        break;
                    }
                } while ( $len > $pos );

                $count2 = $pos;
                $count2++;
                $type = 'quote_';
                switch($quotetype) {
                    case "'": 
                        $type .= 'single'; 
                        break;
                    case "\"":
                        $type .= 'double';
                        break;
                    case "`": 
                        $type .= 'backtick'; 
                        break;
                    default: 
                        break;
                }
                $data = substr($sql, $count1, $count2-$count1);
                PMA_SQP_ArrayAdd ( $sql_array, $type, $data, $arraysize );
                continue;
            }
            //check for brackets
            if (PMA_STR_StrInStr($c,$bracket_list)) {
                //all bracket tokens are only one item long
                $count2++;
                $type_type = '';
                if (PMA_STR_StrInStr($c,'([{')) {
                    $type_type = 'open';
                } else {	
                    $type_type = 'close';
                }
                $type_style = '';
                if (PMA_STR_StrInStr($c,'()')) {	
                    $type_style = 'round';
                } elseif (PMA_STR_StrInStr($c,'[]')) {	
                    $type_style = 'square';
                } else {	
                    $type_style = 'curly';
                }

                $type = 'punct_bracket_'.$type_type.'_'.$type_style;
                PMA_SQP_ArrayAdd ( $sql_array, $type, $c, $arraysize);
                continue;
            }
            //check for punct
            if (PMA_STR_StrInStr($c,$allpunct_list))
            {	
                while( ($count2 < $len) && PMA_STR_StrInStr($sql[$count2],$allpunct_list) ) {
                    $count2++;
                }
                $l = $count2-$count1;
                if ($l == 1) {
                    $punct_data = $c;
                } else {
                    $punct_data = substr($sql,$count1,$l);
                }

                //special case, sometimes, althought two characters are adjectent directly,
                //they ACTUALLY need to be seperate
                if ( $l == 1 ) {	
                    $t_suffix = '';
                    switch($punct_data) {
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
                    PMA_SQP_ArrayAdd ( $sql_array, 'punct'.$t_suffix, $punct_data, $arraysize);
                } elseif ( PMA_STR_BinarySearchInArr($punct_data,$allpunct_list_pair,$allpunct_list_pair_size)) {
                    //Ok, we have one of the valid combined punct expressions
                    PMA_SQP_ArrayAdd ( $sql_array, 'punct', $punct_data, $arraysize );
                } else {	
                    //bad luck, lets split it up more
                    $first = $punct_data[0];
                    $first2 = $punct_data[0].$punct_data[1];
                    $last2 = $punct_data[$l-2].$punct_data[$l-1];
                    $last = $punct_data[$l-1];
                    if (($first == ',') || ($first == ';') || ($first == '.') || $first = '*') {
                        $count2 = $count1 + 1;
                        $punct_data = $first;
                    } elseif (($last2 == '/*') || ($last2 == '--')) {
                        $count2-=2;
                        $punct_data = substr($sql,$count1,$count2-$count1);
                    } elseif (($last == '-') || ($last == '+') || ($last == '!')) {
                        $count2--;
                        $punct_data = substr($sql,$count1,$count2-$count1);
                    } else {	
                        trigger_error('Syntax: Unknown punctation string ('.$punct_data.') at '.$count1);
                        return;
                    }
                    PMA_SQP_ArrayAdd ( $sql_array, 'punct', $punct_data, $arraysize);
                    continue;	
                }
                continue;
            }
            //check for alpha
            if (PMA_STR_IsSqlIdentifier($c,FALSE) || ($c == '@')) {
                $count2 ++;
                $is_SQLvariable = ($c == '@');
                $is_Digit = (!$is_SQLvariable) && PMA_STR_IsDigit($c);
                $is_HexDigit = ($is_Digit) && ($c == '0') && ($sql[$count2] == 'x');
                $is_FloatDigit = FALSE; 
                $is_FloatDigitExponent = FALSE;

                if ($is_HexDigit) {	
                    $count2++;
                }


                while(($count2 < $len) && PMA_STR_IsSqlIdentifier($sql[$count2],$is_SQLvariable || $is_Digit)) {
                    $c2 = $sql[$count2];
                    if ($is_SQLvariable && ($c2 == '.')) {
                        $count2++;
                        continue;
                    }
                    if ($is_Digit && (!$is_HexDigit) && ($c2 == '.')) {
                        $count2++;
                        if (!$is_FloatDigit) {
                            $is_FloatDigit = TRUE;
                            continue;
                        } else {	
                            trigger_error('Syntax: Invalid Identifer ('.substr($sql,$count1,$count2-$count1).') at '.$count1);
                            return;
                        }
                    }
                    if ($is_Digit && (!$is_HexDigit) && (($c2 == 'e') || ($c2 == 'E'))) {
                        if (!$is_FloatDigitExponent) {
                            $is_FloatDigitExponent = TRUE;
                            $is_FloatDigit = TRUE;
                            $count2++;
                            continue;
                        } else {	
                            $is_Digit = FALSE;
                            $is_FloatDigit = FALSE;
                        }
                    }
                    if ( ($is_HexDigit && PMA_STR_IsHexDigit($c2)) || ($is_Digit && PMA_STR_IsDigit($c2))) {
                        $count2++;
                        continue;
                    } else {	
                        $is_Digit = FALSE;
                        $is_HexDigit = FALSE;
                    }

                    $count2++;
                }


                $l = $count2-$count1;
                $str = substr($sql,$count1,$l);

                $type = '';		
                if ($is_Digit) {
                    $type = 'digit';
                    if ($is_FloatDigit) {
                        $type .= '_float';
                    } elseif ($is_HexDigit) {
                        $type .= '_hex';
                    } else {
                        $type .= '_integer';
                    }
                } else {
                    if ($is_SQLvariable != FALSE) {
                        $type = 'alpha_variable';
                    } else {	
                        $type = 'alpha';
                    }
                }
                PMA_SQP_ArrayAdd ( $sql_array, $type, $str, $arraysize );

                continue;
            }

            //DEBUG
            $count2++;
            echo 'You seem to have found a bug in the SQL parser.<br />Please submit a bug report with the data chunk below:<br />--BEGIN CUT--<br />';
            $debugstr = '$Id$<br />';
            $debugstr .= 'Why did we get here? '.$count1.' '.$count2.' '.$len.'<br />'."\n";
            $debugstr .= 'Leftover: '.substr($sql,$count1,$count2-$count1).'<br />'."\n";
            $debugstr .= 'A: '.$count1.' '.$count2.'<br />'."\n";
            $debugstr .= 'SQL: '.$sql;
            $encodedstr = nl2br(chunk_split(base64_encode(gzcompress($debugstr,9))));
            echo $encodedstr; 
            echo '---END CUT---<br />';
            //$decodedstr = str_replace('<br />','', base64_decode(gzuncompress($encodedstr)));
            $decodedstr = gzuncompress(base64_decode(str_replace('<br />','',$encodedstr)));
            echo $decodedstr; 
            flush();
            ob_flush();
            die();

        }

        global $PMA_SQPdata_ColumnAttrib, $PMA_SQPdata_ReservedWord, $PMA_SQPdata_ColumnType, $PMA_SQPdata_FunctionName,
        $PMA_SQPdata_ColumnAttribLen, $PMA_SQPdata_ReservedWordLen, $PMA_SQPdata_ColumnTypeLen, $PMA_SQPdata_FunctionNameLen;
        
        if ($arraysize > 0) {
            $t_next = $sql_array[0]['type'];
            $t_prev = NULL;
        }

        for($i = 0; $i < $arraysize; $i++) {
            $t_prev = $t_cur;
            $t_cur = $t_next;
            if (($i+1)<$arraysize) {
                $t_next = $sql_array[$i+1]['type'];
            } else {	
                $t_next = NULL;
            }
            if ($t_cur == 'alpha') {	
                $t_suffix = '_identifier';
                $d_cur_upper = strtoupper($sql_array[$i]['data']);
                if ( ($t_next == 'punct_qualifier') || ($t_prev == 'punct_qualifier')) {
                    $t_suffix = '_identifier';
                } elseif ( ($t_next == 'punct_bracket_open_round') && PMA_STR_BinarySearchInArr($d_cur_upper,$PMA_SQPdata_FunctionName,$PMA_SQPdata_FunctionNameLen)) {
                    $t_suffix = '_functionName';
                } elseif (PMA_STR_BinarySearchInArr($d_cur_upper,$PMA_SQPdata_ReservedWord,$PMA_SQPdata_ReservedWordLen)) {
                    $t_suffix = '_reservedWord';
                } elseif (PMA_STR_BinarySearchInArr($d_cur_upper,$PMA_SQPdata_ColumnType,$PMA_SQPdata_ColumnTypeLen)) {
                    $t_suffix = '_columnType';
                } elseif (PMA_STR_BinarySearchInArr($d_cur_upper,$PMA_SQPdata_ColumnAttrib,$PMA_SQPdata_ColumnAttribLen)) {
                    $t_suffix = '_columnAttrib';
                } else {
                    // Do nothing
                }
                $sql_array[$i]['type'] .= $t_suffix;
            }
        } 

        // Store the size of the array inside the array, as count() is a slow operation.
        $sql_array['len'] = $arraysize;

        // Send the data back
        return $sql_array;

    }


    function PMA_SQP_Analyze($arr) 
    {	
        $result = array();
        $size = $arr['len'];
        $subresult = array(
            'querytype' => '',
            'list_db' => array(), 
            'list_tbl' => array(),
            'list_tbl_alias' => array(),
            'list_col' => array(),
            'list_col_alias' => array(),
        );
        $subresult_empty = $subresult;
        $seek_queryend = FALSE;

        $supportedQueryTypes = array(
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
        $supportedQueryTypes_size = count($supportedQueryTypes);

        for($i=0;$i <= $size; $i++) {
            // High speed seek for locating the end of the current query
            if ($seek_queryend == TRUE) {
                if ($arr[$i]['type'] == 'punct_queryend') {
                    $seek_queryend = FALSE;
                } else {
                    continue;
                }
            }

            switch($arr[$i]['type']) {
                case 'punct_queryend':
                    $result[] = $subresult;
                    $subresult = $subresult_empty;
                    break;
                case 'alpha_reservedWord':
                    // We don't know what type of query yet, so run this
                    if ($subresult['querytype'] == '') {
                        $subresult['querytype'] = strtoupper($arr[$i]['data']);
                    }
                    // Check if we support this type of query
                    if (! PMA_STR_BinarySearchInArr($subresult['querytype'],$supportedQueryTypes,$supportedQueryTypes_size)) {
                        // Skip ahead to the next one if we don't
                        $seek_queryend = TRUE;
                    }
                    break;
                default:
                    break; 
            }

            switch($subresult['querytype']) {
                case 'SELECT':
                    break;
                default:
                    break;
            }

        }

        // They are are naughty and didn't have a trailing semi-colon, then still handle it properly
        if ($subresult['querytype'] != '') {
            $result[] = $subresult;
        }

        echo '<pre>';
        print_r($result);
        echo '</pre>';
    }

    function PMA_SQP_FormatHTML_colorize($arr)
    {	
        $i = strpos($arr['type'],'_');
        $class = '';
        if ($i > 0) {
            $class = 'syntax_'.substr($arr['type'],0,$i).' ';
        }

        $class .= 'syntax_'.$arr['type'];
        return '<span class="'.$class.'">'.htmlspecialchars($arr['data']).'</span>';
    }

    function PMA_SQP_FormatHTML($arr)
    {	
        $str = '';
        $indent = 0;
        $bracketlevel = 0;
        $functionlevel = 0;
        $infunction = FALSE;
        $space_punct_listsep = ' ';
        $space_punct_listsep_functionName = ' ';
        $space_alpha_reservedWord = '<br />'."\n";
        $keywordsWithBrackets = array(
            'INDEX',
            'INTO',
            'KEY',
            'PRIMARY',
            'REFERENCES',
            'UNIQUE'
        );
        $keywordsWithBrackets_size = count($keywordsWithBrackets);
        $arraysize = $arr['len'];
        $typearr = array();
        if ($arraysize >= 0) {
            /*	array_push($typearr,NULL);
            array_push($typearr,NULL);
            array_push($typearr,NULL);
            array_push($typearr,$arr[0]['type']);
            array_push($typearr,$arr[1]['type']); */

            $typearr[0] = NULL;
            $typearr[1] = NULL;
            $typearr[2] = NULL;
            $typearr[3] = $arr[0]['type'];
        }

        for($i = 0; $i < $arraysize; $i++) {
            $before = '';
            $after = '';
            $indent = 0;
            //	array_shift($typearr);
            /*
            0 prev2
            1 prev
            2 current
            3 next
            */
            if (($i+1)<$arraysize) {
                //array_push($typearr,$arr[$i+1]['type']);
                $typearr[4] = $arr[$i+1]['type'];
            } else {	
                //array_push($typearr,NULL);
                $typearr[4] = NULL;
            }

            for($j=0;$j<4;$j++) {
                $typearr[$j] = $typearr[$j+1];
            }

            switch($typearr[2]) {
                case 'white_newline':
                    $after = '<br />';
                    $before = '';
                    break;
                case 'punct_bracket_open_round':
                    $bracketlevel++;
                    $infunction = FALSE;
                    //make sure this array is sorted!
                    if ( ($typearr[1] == 'alpha_functionName') || ($typearr[1] == 'alpha_columnType') || ($typearr[1] == 'punct') || ($typearr[3] == 'digit_integer') || ($typearr[3] == 'digit_hex') || ($typearr[3] == 'digit_float') || ( ( $typearr[0] == 'alpha_reservedWord' ) && ( PMA_STR_BinarySearchInArr(strtoupper($arr[$i-2]['data']),$keywordsWithBrackets,$keywordsWithBrackets_size))) ) {
                        $functionlevel++;
                        $infunction = TRUE;
                        $after .= ' ';
                    } else {	
                        $indent++;
                        $after .= '<div class="syntax_indent'.$indent.'">'."\n";
                    }
                    break;
                case 'punct_qualifier': 
                    break;
                case 'punct_listsep':
                    if ($infunction == TRUE) {
                        $after .= $space_punct_listsep_functionName;
                    } else {	
                        $after .= $space_punct_listsep;
                    }
                    break;
                case 'punct_queryend':
                    if (($typearr[3] != 'white_newline') && ($typearr[3] != 'comment_mysql')&& ($typearr[3] != 'comment_ansi') ) {
                        $after .= '<br />'."\n";
                    }
                    break;
                case 'comment':
                    break;
                case 'punct_bracket_close_round':
                    $bracketlevel--;
                    if ($infunction == TRUE) {
                        $functionlevel--;
                        $after .= ' ';
                    } else {	
                        $indent--;
                        $before .= '</div>';
                    }
                    $infunction = ($functionlevel > 0) ? TRUE : FALSE;
                    break;

                case 'alpha_reservedWord':
                    if ( ($typearr[1] != 'alpha_reservedWord') && ($typearr[1] != 'punct_level_plus')  && ($typearr[1] != 'white_newline')) {
                        $before .= $space_alpha_reservedWord;
                    }

                    switch(strtoupper($arr[$i]['data'])) {
                        case 'CREATE':
                            $space_punct_listsep = '<br />'."\n";
                            $space_alpha_reservedWord = ' ';
                            break;
                        case 'UPDATE':
                            $space_punct_listsep = '<br />'."\n";
                            $space_alpha_reservedWord = ' ';
                            break;
                        case 'INSERT':
                            $space_punct_listsep = '<br />'."\n";
                            $space_alpha_reservedWord = '<br />'."\n";
                            break;
                        case 'VALUES':
                            $space_punct_listsep = ' ';
                            $space_alpha_reservedWord = '<br />'."\n";
                            break;
                        case 'SELECT':
                            $space_punct_listsep = ' ';
                            $space_alpha_reservedWord = '<br />'."\n";
                            break;
                        default:
                            break;
                    }

                    $after .= " ";
                    break;

                default:
                    break;
            }

            if ($typearr[3] != 'punct_qualifier') {
                $after .= ' ';
            }
            $str .= $before.PMA_SQP_FormatHTML_colorize($arr[$i]).$after;
        }
        return $str;
    }

} // $__PMA_SQP_LIB__
