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
 * @todo a .lib filename should not have code in main(), split or rename file
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$GLOBALS['PMA_allow_mbstr'] = @function_exists('mb_strlen');
$GLOBALS['PMA_allow_ctype'] = @extension_loaded('ctype');

if ($GLOBALS['PMA_allow_mbstr']) {
    mb_internal_encoding('utf-8');
}

/**
 * Load proper code for handling input.
 */
if ($GLOBALS['PMA_allow_mbstr']) {
    $GLOBALS['PMA_strpos']      = 'mb_strpos';
    $GLOBALS['PMA_substr']      = 'mb_substr';
    require './libraries/string_mb.lib.php';
} else {
    $GLOBALS['PMA_strpos']      = 'strpos';
    $GLOBALS['PMA_substr']      = 'substr';
    require './libraries/string_native.lib.php';
}

/**
 * Load ctype handler.
 */
if ($GLOBALS['PMA_allow_ctype']) {
    $GLOBALS['PMA_STR_isAlnum'] = 'ctype_alnum';
    $GLOBALS['PMA_STR_isDigit'] = 'ctype_digit';
    $GLOBALS['PMA_STR_isSpace'] = 'ctype_space';
    require './libraries/string_type_ctype.lib.php';
} else {
    $GLOBALS['PMA_STR_isAlnum'] = 'PMA_STR_isAlnum';
    $GLOBALS['PMA_STR_isDigit'] = 'PMA_STR_isDigit';
    $GLOBALS['PMA_STR_isSpace'] = 'PMA_STR_isSpace';
    require './libraries/string_type_native.lib.php';
}

/**
 * Checks if a given character position in the string is escaped or not
 *
 * @param string   string to check for
 * @param integer  the character to check for
 * @param integer  starting position in the string
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
 * @param integer  number to check for
 * @param integer  lower bound
 * @param integer  upper bound
 * @return  boolean  whether the number is in the range or not
 */
function PMA_STR_numberInRangeInclusive($num, $lower, $upper)
{
    return ($num >= $lower && $num <= $upper);
} // end of the "PMA_STR_numberInRangeInclusive()" function

/**
 * Checks if a character is an SQL identifier
 *
 * @param string   character to check for
 * @param boolean  whether the dot character is valid or not
 * @return  boolean  whether the character is an SQL identifier or not
 */
function PMA_STR_isSqlIdentifier($c, $dot_is_valid = false)
{
    return ($GLOBALS['PMA_STR_isAlnum']($c)
        || ($ord_c = ord($c)) && $ord_c >= 192 && $ord_c != 215 && $ord_c != 249
        || $c == '_'
        || $c == '$'
        || ($dot_is_valid != false && $c == '.'));
} // end of the "PMA_STR_isSqlIdentifier()" function

?>
