<?php
/* $Id$ */

if(!defined('PMA_STR_LIB_INCLUDED')) {
    define('PMA_STR_LIB_INCLUDED', 1);
    /* Specialized String Functions for phpMyAdmin
    **
    ** Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
    ** http://www.orbis-terrarum.net/?l=people.robbat2
    **
    ** Defines a set of function callbacks that have a pure C version 
    ** available if the ctype extension is available, but otherwise 
    ** have PHP versions to use (that are slower)
    **
    ** The SQL Parser code relies heavily on these functions
    **/

    /**
     * This checks if a string actually exists inside another string
     * We try to do it in a PHP3-portable way 
     * We don't care about the position it is in.
     *
     * @param   string  string to search for
     * @param   string  string to search in
     *
     * @return  boolean if the needle is in the haystack
     */
    function PMA_STR_StrInStr($needle,$haystack)
    {	
        // strpos($haystack,$needle) !== FALSE
        return (is_integer(strpos($haystack,$needle)));
    }

    // checks if a given character position in
    // the string is escaped or not
    function PMA_STR_CharIsEscaped($string,$pos,$start=0)
    {	
        $len = strlen($string);
        // Base case
        // Check for string length or invalid input
        // or special case of input
        // (pos == $start)
        if($pos == $start || $len <= $pos) {
            return FALSE;
        }

        $p = $pos-1;
        $escaped = FALSE;
        while(($p >= $start) && ($string[$p] == "\\")) {	
            $escaped = !$escaped;
            $p--;
        }

        if($pos < $start) {
            //throw error about strings
        }
        return $escaped;

    }

    function PMA_STR_NumberInRangeInclusive($num,$lower,$upper)
    {	
        return ($num >= $lower) && ($num <= $upper);
    }

    function PMA_STR_isdigit($c)
    {	
        $ord_zero = 48; //ord('0');
        $ord_nine = 57; //ord('9');
        $ord_c = ord($c);
        return PMA_STR_NumberInRangeInclusive($ord_c,$ord_zero,$ord_nine);
    }

    function PMA_STR_ishexdigit($c)
    {	
        $ord_Aupper = 65; //ord('A');
        $ord_Fupper = 70; //ord('F');
        $ord_Alower = 97; //ord('a');
        $ord_Flower = 102; //ord('f');
        $ord_zero = 48; //ord('0');
        $ord_nine = 57; //ord('9');
        $ord_c = ord($c);
        return 
        PMA_STR_NumberInRangeInclusive($ord_c,$ord_zero,$ord_nine)
        || PMA_STR_NumberInRangeInclusive($ord_c,$ord_Aupper,$ord_Fupper)
        || PMA_STR_NumberInRangeInclusive($ord_c,$ord_Alower,$ord_Flower);
    }

    function PMA_STR_isupper($c)
    {	
        $ord_zero = 65; //ord('A');
        $ord_nine = 90; //ord('Z');
        $ord_c = ord($c);
        return PMA_STR_NumberInRangeInclusive($ord_c,$ord_zero,$ord_nine);
    }

    function PMA_STR_islower($c)
    {	
        $ord_zero = 97; //ord('a');
        $ord_nine = 122; //ord('z');
        $ord_c = ord($c);
        return PMA_STR_NumberInRangeInclusive($ord_c,$ord_zero,$ord_nine);
    }

    function PMA_STR_isalpha($c)
    {	
        return PMA_STR_isupper($c) || PMA_STR_islower($c);
    }

    function PMA_STR_isalnum($c)
    {	
        return PMA_STR_isupper($c) || PMA_STR_islower($c) || PMA_STR_isdigit($c);
    }

    function PMA_STR_isspace($c)
    {	
        $ord_tab = 9;
        $ord_CR = 13;
        $ord_c = ord($c);
        return  ($ord_c == 32) ||
        PMA_STR_NumberInRangeInclusive($ord_c,$ord_tab,$ord_CR);
    }

    function PMA_STR_isSQLidentifier($c,$dotIsValid=FALSE)
    {	
        return PMA_STR_isalnum($c) || ($c == '_') || ($c == '$') || (($dotIsValid != FALSE) && ($c == '.'));
    }

    function PMA_STR_BinarySearchInArr($str,$arr,$arrsize)
    {	
        //$arr NUST be sorted, due to binary search
        $top = $arrsize-1;
        $bottom = 0;
        $found = FALSE;
        while( ($top >= $bottom) && ($found == FALSE)) {
            $mid = intval(($top+$bottom)/2);
            $res = strcmp($str,$arr[$mid]);
            if($res == 0) {
                $found = TRUE;
            } elseif($res < 0) {
                $top = $mid-1;
            } else { 
                $bottom = $mid+1;
            }
        }
        return $found;
    }

} // $__PMA_STR_LIB__

?>
