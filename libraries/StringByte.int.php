<?php
/**
 * Defines a set of specialized string functions.
 *
 * @package PhpMyAdmin-String
 */
interface PMA_StringByte
{
    /**
     * Returns length of string depending on current charset.
     *
     * @param string $string string to count
     *
     * @return int string length
     */

    public function strlen($string);

    /**
     * Returns substring from string, works depending on current charset.
     *
     * @param string $string string to count
     * @param int    $start  start of substring
     * @param int    $length length of substring
     *
     * @return string the sub string
     */
    public function substr($string, $start, $length = 2147483647);

    /**
     * Returns number of substrings from string, works depending on current charset.
     *
     * @param string $string string to count
     * @param int    $start  start of substring
     *
     * @return int number of substrings from the string
     */
    public function substrCount($string, $start);

    /**
     * Returns position of $needle in $haystack or false if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     * @param int    $offset   the search offset
     *
     * @return integer position of $needle in $haystack or false
     */
    public function strpos($haystack, $needle, $offset = 0);

    /**
     * Returns position of $needle in $haystack - case insensitive - or false if
     * not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     * @param int    $offset   the search offset
     *
     * @return integer position of $needle in $haystack or false
     */
    public function stripos($haystack, $needle, $offset = 0);

    /**
     * Returns position of last $needle in $haystack or false if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     * @param int    $offset   the search offset
     *
     * @return integer position of last $needle in $haystack or false
     */
    public function strrpos($haystack, $needle, $offset = 0);

    /**
     * Returns position of last $needle in $haystack - case insensitive - or false
     * if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     * @param int    $offset   the search offset
     *
     * @return integer position of last $needle in $haystack or false
     */
    public function strripos($haystack, $needle, $offset = 0);

    /**
     * Returns part of $haystack string starting from and including the first
     * occurrence of $needle to the end of $haystack or false if not found
     *
     * @param string $haystack      the string being checked
     * @param string $needle        the string to find in haystack
     * @param bool   $before_needle the part before the needle
     *
     * @return string part of $haystack or false
     */
    public function strstr($haystack, $needle, $before_needle = false);

    /**
     * Returns part of $haystack string starting from and including the first
     * occurrence of $needle to the end of $haystack - case insensitive - or false
     * if not found
     *
     * @param string $haystack      the string being checked
     * @param string $needle        the string to find in haystack
     * @param bool   $before_needle the part before the needle
     *
     * @return string part of $haystack or false
     *
     * @deprecated
     * @see DON'T USE UNTIL HHVM IMPLEMENTS THIRD PARAMETER!
     */
    public function stristr($haystack, $needle, $before_needle = false);

    /**
     * Returns the portion of haystack which starts at the last occurrence or false
     * if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     *
     * @return string portion of haystack which starts at the last occurrence or
     * false
     */
    public function strrchr($haystack, $needle);

    /**
     * Make a string lowercase
     *
     * @param string $string the string being lowercased
     *
     * @return string the lower case string
     */
    public function strtolower($string);

    /**
     * Make a string uppercase
     *
     * @param string $string the string being uppercased
     *
     * @return string the lower case string
     */
    public function strtoupper($string);

    /**
     * Get the ordinal value of a string
     *
     * @param string $string the string for which ord is required
     *
     * @return string the ord value
     */
    public function ord($string);

    /**
     * Get the character of an ASCII
     *
     * @param int $ascii the ASCII code for which character is required
     *
     * @return string the character
     */
    public function chr($ascii);
}
?>