<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Implements PMA_StringByte interface using the "mbstring" extension.
 *
 * @package    PhpMyAdmin-String
 * @subpackage MB
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/StringByte.int.php';

/**
 * Implements PMA_StringByte interface using the "mbstring" extension.
 *
 * @package    PhpMyAdmin-String
 * @subpackage MB
 */
class PMA_StringMB implements PMA_StringByte
{
    /**
     * Returns length of string depending on current charset.
     *
     * @param string $string string to count
     *
     * @return int string length
     */

    public function strlen($string)
    {
        return mb_strlen($string);
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
    public function substr($string, $start, $length = 2147483647)
    {
        $stringLength = $this->strlen($string);
        if ($stringLength <= $start) {
            return false;
        }
        if ($stringLength + $length < $start) {
            return false;
        }

        var_dump($string, $start, $length);

        return mb_substr($string, $start, $length);
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
    public function strpos($haystack, $needle, $offset = 0)
    {
        if (null === $haystack) {
            return false;
        }
        if (false === $needle) {
            return false;
        }
        if (!is_string($needle) && is_numeric($needle)) {
            $needle = (int)$needle;
            $needle = chr($needle);
        }
        return mb_strpos($haystack, $needle, $offset);
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
     *
     * @todo add unit tests
     */
    public function stripos($haystack, $needle, $offset = 0)
    {
        if (null === $haystack) {
            return false;
        }
        if (false === $needle) {
            return false;
        }
        if (!is_string($needle) && is_numeric($needle)) {
            $needle = (int)$needle;
            $needle = chr($needle);
        }
        return mb_stripos($haystack, $needle, $offset);
    }

    /**
     * Returns position of last $needle in $haystack or false if not found
     *
     * @param string $haystack the string being checked
     * @param string $needle   the string to find in haystack
     * @param int    $offset   the search offset
     *
     * @return integer position of last $needle in $haystack or false
     *
     * @todo add unit tests
     */
    public function strrpos($haystack, $needle, $offset = 0)
    {
        if (null === $haystack) {
            return false;
        }
        if (false === $needle) {
            return false;
        }
        if (!is_string($needle) && is_numeric($needle)) {
            $needle = (int)$needle;
            $needle = chr($needle);
        }
        return mb_strrpos($haystack, $needle, $offset);
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
     *
     * @todo add unit tests
     */
    public function strstr($haystack, $needle, $before_needle = false)
    {
        if (!is_string($needle) && is_numeric($needle)) {
            $needle = (int)$needle;
            $needle = chr($needle);
        }
        if (!is_string($haystack) || !is_string($needle) || null === $needle) {
            return false;
        }
        return mb_strstr($haystack, $needle, $before_needle);
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
     *
     * @todo add unit tests
     */
    public function stristr($haystack, $needle, $before_needle = false)
    {
        if (!is_string($needle) && is_numeric($needle)) {
            $needle = (int)$needle;
            $needle = chr($needle);
        }
        if (!is_string($haystack) || !is_string($needle) || null === $needle) {
            return false;
        }
        return mb_stristr($haystack, $needle, $before_needle);
    }

    /**
     * Make a string lowercase
     *
     * @param string $string the string being lowercased
     *
     * @return string the lower case string
     */
    public function strtolower($string)
    {
        return mb_strtolower($string);
    }

    /**
     * Make a string uppercase
     *
     * @param string $string the string being uppercased
     *
     * @return string the upper case string
     *
     * @todo add unit tests
     */
    public function strtoupper($string)
    {
        return mb_strtoupper($string);
    }

    /**
     * Get the ordinal value of a multibyte string
     * (Adapted from http://www.php.net/manual/en/function.ord.php#72463)
     *
     * @param string $string the string for which ord is required
     *
     * @return string the ord value
     */
    public function ord($string)
    {
        if (false === $string || null === $string || '' === $string) {
            return 0;
        }

        $str = mb_convert_encoding($string, "UCS-4BE", "UTF-8");
        $substr = mb_substr($str, 0, 1, "UCS-4BE");
        $val = unpack("N", $substr);
        return $val[1];
    }

    /**
     * Get the multibyte character of an ASCII
     * (from http://fr2.php.net/manual/en/function.chr.php#69082)
     *
     * @param int $ascii the ASCII code for which character is required
     *
     * @return string the multibyte character
     */
    public function chr($ascii)
    {
        return mb_convert_encoding(
            pack("N", $ascii),
            mb_internal_encoding(),
            'UCS-4BE'
        );
    }
}
?>
