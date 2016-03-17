<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/** String Functions for phpMyAdmin
 *
 * If mb_* functions don't exist, we create the ones we need and they'll use the
 * standard string functions.
 *
 * All mb_* functions created by PMA should behave as mb_* functions.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (!@function_exists('mb_ord')) {
    /**
     * Perform a regular expression match
     *
     * Take care: might not work with lookbehind expressions.
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
            $pattern, mb_substr($subject, $offset), $matches, PREG_OFFSET_CAPTURE
        );
        if (1 !== $bFind) {
            return false;
        }

        return $matches[1][1] + $offset;
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
        if (false === $string || null === $string || '' === $string) {
            return 0;
        }

        $str = mb_convert_encoding($string, "UCS-4BE", "UTF-8");
        $substr = mb_substr($str, 0, 1, "UCS-4BE");
        $val = unpack("N", $substr);
        return $val[1];
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
        return mb_convert_encoding(
            pack("N", $ascii),
            mb_internal_encoding(),
            'UCS-4BE'
        );
    }

}
