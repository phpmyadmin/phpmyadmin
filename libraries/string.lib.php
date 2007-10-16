<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Specialized String Functions for phpMyAdmin
 *
 * Copyright 2002 Robin Johnson <robbat2@users.sourceforge.net>
 * http://www.orbis-terrarum.net/?l=people.robbat2
 *
 * Defines a set of function callbacks that have a pure C version available if
 * the "ctype" extension is available, but otherwise have PHP versions to use
 * (that are slower).
 *
 * The SQL Parser code relies heavily on these functions.
 *
 * @version $Id$
 * @uses    PMA_dl()
 * @uses    extension_loaded()
 * @uses    substr()
 * @uses    function_exists()
 * @uses    mb_internal_encoding()
 * @uses    defined()
 * @todo a .lib filename should not have code in main(), split or rename file
 */

/* Try to load mbstring */
    if (!@extension_loaded('mbstring')) {
        PMA_dl('mbstring');
    }

/**
 * windows-* and tis-620 are not supported and are not multibyte,
 * others can be ignored as they're not multibyte
 *
 * @global boolean $GLOBALS['using_mb_charset']
 */
$GLOBALS['using_mb_charset'] =
    substr($GLOBALS['charset'], 0, 8) != 'windows-' &&
    substr($GLOBALS['charset'], 0, 9) != 'iso-8859-' &&
    substr($GLOBALS['charset'], 0, 3) != 'cp-' &&
    $GLOBALS['charset'] != 'koi8-r' &&
    $GLOBALS['charset'] != 'tis-620';

$GLOBALS['PMA_allow_mbstr'] = @function_exists('mb_strlen') && $GLOBALS['using_mb_charset'];

if ($GLOBALS['PMA_allow_mbstr']) {
    // the hebrew lang file uses iso-8859-8-i, encoded RTL,
    // but mb_internal_encoding only supports iso-8859-8
    if ($GLOBALS['charset'] == 'iso-8859-8-i'){
        mb_internal_encoding('iso-8859-8');
    } else {
        mb_internal_encoding($GLOBALS['charset']);
    }
}

// This is for handling input better
if (defined('PMA_MULTIBYTE_ENCODING') || $GLOBALS['PMA_allow_mbstr']) {
    $GLOBALS['PMA_strpos']  = 'mb_strpos';
    require './libraries/string_mb.lib.php';
} else {
    $GLOBALS['PMA_strpos']  = 'strpos';
    require './libraries/string_native.lib.php';
}

if (!@extension_loaded('ctype')) {
    PMA_dl('ctype');
}

if (@extension_loaded('ctype')) {
    require './libraries/string_type_ctype.lib.php';
} else {
    require './libraries/string_type_native.lib.php';
}

/**
 * This checks if a string actually exists inside another string
 * We don't care about the position it is in.
 *
 * @uses    PMA_STR_pos()
 * @param   string   string to search for
 * @param   string   string to search in
 * @return  boolean  whether the needle is in the haystack or not
 * @todo    rename PMA_STR_inStr()
 */
function PMA_STR_strInStr($needle, $haystack)
{
    // PMA_STR_pos($haystack, $needle) !== false
    // return (is_integer(PMA_STR_pos($haystack, $needle)));
    return (bool) PMA_STR_pos(' ' . $haystack, $needle);
} // end of the "PMA_STR_strInStr()" function

/**
 * Checks if a given character position in the string is escaped or not
 *
 * @uses    PMA_strlen()
 * @uses    PMA_substr()
 * @uses    max()
 * @uses    intval()
 * @param   string   string to check for
 * @param   integer  the character to check for
 * @param   integer  starting position in the string
 * @return  boolean  whether the character is escaped or not
 */
function PMA_STR_charIsEscaped($string, $pos, $start = 0)
{
    $pos = max(intval($pos), 0);
    $start = max(intval($start), 0);
    $len = PMA_strlen($string);
    // Base case:
    // Check for string length or invalid input or special case of input
    // (pos == $start)
    if ($pos <= $start || $len <= max($pos, $start)) {
        return false;
    }

    $pos--;
    $escaped     = false;
    while ($pos >= $start && PMA_substr($string, $pos, 1) == '\\') {
        $escaped = !$escaped;
        $pos--;
    } // end while

    return $escaped;
} // end of the "PMA_STR_charIsEscaped()" function


/**
 * Checks if a number is in a range
 *
 * @param   integer  number to check for
 * @param   integer  lower bound
 * @param   integer  upper bound
 * @return  boolean  whether the number is in the range or not
 */
function PMA_STR_numberInRangeInclusive($num, $lower, $upper)
{
    return ($num >= $lower && $num <= $upper);
} // end of the "PMA_STR_numberInRangeInclusive()" function


/**
 * Checks if a character is an accented character
 *
 * Presently this only works for some character sets. More work may be needed
 * to fix it.
 *
 * @uses    PMA_STR_numberInRangeInclusive()
 * @uses    ord()
 * @param   string   character to check for
 * @return  boolean  whether the character is an accented one or not
 */
function PMA_STR_isAccented($c)
{
    $ord_min1 = 192; //ord('A');
    $ord_max1 = 214; //ord('Z');
    $ord_min2 = 216; //ord('A');
    $ord_max2 = 246; //ord('Z');
    $ord_min3 = 248; //ord('A');
    $ord_max3 = 255; //ord('Z');

    $ord_c    = ord($c);

    return PMA_STR_numberInRangeInclusive($ord_c, $ord_min1, $ord_max1)
        || PMA_STR_numberInRangeInclusive($ord_c, $ord_min2, $ord_max2)
        || PMA_STR_numberInRangeInclusive($ord_c, $ord_min2, $ord_max2);
} // end of the "PMA_STR_isAccented()" function


/**
 * Checks if a character is an SQL identifier
 *
 * @uses    PMA_STR_isAlnum()
 * @uses    PMA_STR_isAccented()
 * @param   string   character to check for
 * @param   boolean  whether the dot character is valid or not
 * @return  boolean  whether the character is an SQL identifier or not
 */
function PMA_STR_isSqlIdentifier($c, $dot_is_valid = false)
{
    return (PMA_STR_isAlnum($c)
         || PMA_STR_isAccented($c)
         || $c == '_'
         || $c == '$'
         || ($dot_is_valid != false && $c == '.'));
} // end of the "PMA_STR_isSqlIdentifier()" function


/**
 * Binary search of a value in a sorted array
 *
 * $arr MUST be sorted, due to binary search
 *
 * @param   string   string to search for
 * @param   array    sorted array to search into
 * @param   integer  size of sorted array to search into
 *
 * @return  boolean  whether the string has been found or not
 */
function PMA_STR_binarySearchInArr($str, $arr, $arrsize)
{
    $top    = $arrsize - 1;
    $bottom = 0;
    $found  = false;

    while ($top >= $bottom && $found == false) {
        $mid        = intval(($top + $bottom) / 2);
        $res        = strcmp($str, $arr[$mid]);
        if ($res == 0) {
            $found  = true;
        } elseif ($res < 0) {
            $top    = $mid - 1;
        } else {
            $bottom = $mid + 1;
        }
    } // end while

    return $found;
} // end of the "PMA_STR_binarySearchInArr()" function

?>
