<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Specialized String Functions for phpMyAdmin
 *
 * Defines a set of function callbacks that have a pure C version available if
 * the "ctype" extension is available, but otherwise have PHP versions to use
 * (that are slower).
 *
 * The SQL Parser code relies heavily on these functions.
 *
 * @todo a .lib filename should not have code in main(), split or rename file
 * @package PhpMyAdmin-String
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Load proper code for handling input.
 */
if (@function_exists('mb_strlen')) {
    mb_internal_encoding('utf-8');
    include './libraries/string_mb.lib.php';
} else {
    include './libraries/string_native.lib.php';
}

/**
 * Load ctype handler.
 */
if (@extension_loaded('ctype')) {
    include './libraries/string_type_ctype.lib.php';
} else {
    include './libraries/string_type_native.lib.php';
}

/**
 * Checks if a given character position in the string is escaped or not
 *
 * @param string  $string string to check for
 * @param integer $pos    the character to check for
 * @param integer $start  starting position in the string
 *
 * @return boolean  whether the character is escaped or not
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
 * @param integer $num   number to check for
 * @param integer $lower lower bound
 * @param integer $upper upper bound
 *
 * @return boolean  whether the number is in the range or not
 */
function PMA_STR_numberInRangeInclusive($num, $lower, $upper)
{
    return ($num >= $lower && $num <= $upper);
} // end of the "PMA_STR_numberInRangeInclusive()" function

/**
 * Checks if a character is an SQL identifier
 *
 * @param string  $c            character to check for
 * @param boolean $dot_is_valid whether the dot character is valid or not
 *
 * @return boolean  whether the character is an SQL identifier or not
 */
function PMA_STR_isSqlIdentifier($c, $dot_is_valid = false)
{
    return (PMA_STR_isAlnum($c)
        || ($ord_c = ord($c)) && $ord_c >= 192 && $ord_c != 215 && $ord_c != 249
        || $c == '_'
        || $c == '$'
        || ($dot_is_valid != false && $c == '.'));
} // end of the "PMA_STR_isSqlIdentifier()" function

?>
