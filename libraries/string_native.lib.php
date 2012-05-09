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
 * @package    PhpMyAdmin-String
 * @subpackage Native
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns length of string depending on current charset.
 *
 * @param string $string string to count
 *
 * @return int string length
 */
function PMA_strlen($string)
{
    return strlen($string);
}

/**
 * Returns substring from string, works depending on current charset.
 *
 * @param string $string string to count
 * @param int    $start  start of substring
 * @param int    $length length of substring
 *
 * @return string the sub string
 */
function PMA_substr($string, $start, $length = 2147483647)
{
    return substr($string, $start, $length);
}

/**
 * Returns postion of $needle in $haystack or false if not found
 *
 * @param string $haystack the string being checked
 * @param string $needle   the string to find in haystack
 * @param int    $offset   the search offset
 *
 * @return integer position of $needle in $haystack or false
 */
function PMA_strpos($haystack, $needle, $offset = 0)
{
    return strpos($haystack, $needle, $offset);
}

/**
 * Make a string lowercase
 *
 * @param string $string the string being lowercased
 *
 * @return string the lower case string
 */
function PMA_strtolower($string)
{
    return strtolower($string);
}

?>
