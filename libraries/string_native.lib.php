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
 * @package PhpMyAdmin-String-Native
 */

/**
 * Returns length of string depending on current charset.
 *
 * @param string   string to count
 * @return  int      string length
 */
function PMA_strlen($string)
{
    return strlen($string);
}

/**
 * Returns substring from string, works depending on current charset.
 *
 * @param string $string  string to count
 * @param int    $start   start of substring
 * @param int    $length  length of substring
 * @return  string
 */
function PMA_substr($string, $start, $length = 2147483647)
{
    return substr($string, $start, $length);
}

/**
 * Returns postion of $needle in $haystack or false if not found
 *
 * @param string  $haystack
 * @param string  $needle
 * @param int     $offset
 * @return  integer position of $needle in $haystack or false
 */
function PMA_strpos($haystack, $needle, $offset = 0)
{
    return strpos($haystack, $needle, $offset);
}

/**
 * Make a string lowercase
 *
 * @param string  $string
 * @return  string
 */
function PMA_strtolower($string)
{
    return strtolower($string);
}

?>
