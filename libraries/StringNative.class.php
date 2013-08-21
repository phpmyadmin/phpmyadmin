<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Implements PMA_StringByte interface using native PHP functions.
 *
 * @package    PhpMyAdmin-String
 * @subpackage Native
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/StringByte.int.php';

/**
 * Implements PMA_StringByte interface using native PHP functions.
 *
 * @package    PhpMyAdmin-String
 * @subpackage Native
 */
class PMA_StringNative implements PMA_StringByte
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
    public function substr($string, $start, $length = 2147483647)
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
    public function strpos($haystack, $needle, $offset = 0)
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
    public function strtolower($string)
    {
        return strtolower($string);
    }

    /**
     * Get the ordinal value of a string
     *
     * @param string $string the string for which ord is required
     *
     * @return string the ord value
     */
    public function ord($string)
    {
        return ord($string);
    }
};
?>
