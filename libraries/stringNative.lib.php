<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/** String Functions for phpMyAdmin
 * If mb_* functions don't exist, we create the ones we need and they'll use the
 * standard string functions.
 * All mb_* functions created by PMA should behave as mb_* functions.
 *
 * @package PhpMyAdmin
 */
if (!defined('PHPMYADMIN')) {
    exit;
}

//Define mb_* functions if they don't exist.
if (!@function_exists('mb_strlen')) {
    /**
     * Returns length of string depending on current charset.
     *
     * @param string $string string to count
     *
     * @return int string length
     */
    function mb_strlen($string)
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
    function mb_substr($string, $start, $length = 2147483647)
    {
        if (null === $string || strlen($string) <= $start) {
            return '';
        }
        if (null === $length) {
            $length = 2147483647;
        }
        return substr($string, $start, $length);
    }

    /**
     * Returns number of substring from string.
     *
     * @param string $string string to check
     * @param int    $needle string to count
     *
     * @return int number of substring from the string
     */
    function mb_substrCount($string, $needle)
    {
        return substr_count($string, $needle);
    }

    /**
     * Returns position of $needle in $haystack or false if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     * @param int    $offset   the search offset
     *
     * @return integer position of $needle in $haystack or false
     */
    function mb_strpos($haystack, $needle, $offset = 0)
    {
        return strpos($haystack, $needle, $offset);
    }

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
    function mb_stripos($haystack, $needle, $offset = 0)
    {
        if (('' === $haystack || false === $haystack) && $offset >= strlen($haystack)
        ) {
            return false;
        }
        return stripos($haystack, $needle, $offset);
    }

    /**
     * Returns position of last $needle in $haystack or false if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     * @param int    $offset   the search offset
     *
     * @return integer position of last $needle in $haystack or false
     */
    function mb_strrpos($haystack, $needle, $offset = 0)
    {
        return strrpos($haystack, $needle, $offset);
    }

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
    function mb_strripos($haystack, $needle, $offset = 0)
    {
        if (('' === $haystack || false === $haystack) && $offset >= strlen($haystack)
        ) {
            return false;
        }
        return strripos($haystack, $needle, $offset);
    }

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
    function mb_strstr($haystack, $needle, $before_needle = false)
    {
        return strstr($haystack, $needle, $before_needle);
    }

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
     */
    function mb_stristr($haystack, $needle, $before_needle = false)
    {
        return stristr($haystack, $needle, $before_needle);
    }

    /**
     * Returns the portion of haystack which starts at the last occurrence or false
     * if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     *
     * @return string portion of haystack which starts at the last occurrence or
     *                         false
     */
    function mb_strrchr($haystack, $needle)
    {
        return strrchr($haystack, $needle);
    }

    /**
     * Make a string lowercase
     *
     * @param string $string the string being lowercased
     *
     * @return string the lower case string
     */
    function mb_strtolower($string)
    {
        return strtolower($string);
    }

    /**
     * Make a string uppercase
     *
     * @param string $string the string being uppercased
     *
     * @return string the upper case string
     */
    function mb_strtoupper($string)
    {
        return strtoupper($string);
    }
}

//New functions.
if (!@function_exists('mb_ord')) {
    /**
     * Perform a regular expression match
     *
     * @param string $pattern Pattern to search for
     * @param string $subject Input string
     * @param int    $offset  Start from search
     *
     * @return int 1 if matched, 0 if doesn't, false on failure
     */
    function mb_preg_strpos($pattern, $subject, $offset = 0)
    {
        $matches = array();
        $bFind = preg_match(
            $pattern, $subject, $matches, PREG_OFFSET_CAPTURE, $offset
        );
        if (1 !== $bFind) {
            return false;
        }

        return $matches[1][1];
    }

    /**
     * Get the ordinal value of a string
     *
     * @param string $string the string for which ord is required
     *
     * @return int the ord value
     */
    function mb_ord($string)
    {
        return ord($string);
    }

    /**
     * Get the character of an ASCII
     *
     * @param int $ascii the ASCII code for which character is required
     *
     * @return string the character
     */
    function mb_chr($ascii)
    {
        return chr($ascii);
    }
}