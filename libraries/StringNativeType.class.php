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

class PMA_StringNativeType
{
    /**
     * Checks if a character is an alphanumeric one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an alphanumeric one or not
     */
    public static function isAlnum($c)
    {
        return (self::isUpper($c) || self::isLower($c) || self::isDigit($c));
    } // end of the "isAlnum()" function

    /**
     * Checks if a character is an alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an alphabetic one or not
     */
    public static function isAlpha($c)
    {
        return ($GLOBALS['PMA_StringType']::isUpper($c) || $GLOBALS['PMA_StringType']::isLower($c));
    } // end of the "isAlpha()" function

    /**
     * Checks if a character is a digit
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a digit or not
     */
    public static function isDigit($c)
    {
        $ord_zero = 48; //ord('0');
        $ord_nine = 57; //ord('9');
        $ord_c    = ord($c);

        return PMA_STR_numberInRangeInclusive($ord_c, $ord_zero, $ord_nine);
    } // end of the "isDigit()" function

    /**
     * Checks if a character is an upper alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an upper alphabetic one or not
     */
    public static function isUpper($c)
    {
        $ord_zero = 65; //ord('A');
        $ord_nine = 90; //ord('Z');
        $ord_c    = ord($c);

        return PMA_STR_numberInRangeInclusive($ord_c, $ord_zero, $ord_nine);
    } // end of the "isUpper()" function

    /**
     * Checks if a character is a lower alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a lower alphabetic one or not
     */
    public static function isLower($c)
    {
        $ord_zero = 97;  //ord('a');
        $ord_nine = 122; //ord('z');
        $ord_c    = ord($c);

        return PMA_STR_numberInRangeInclusive($ord_c, $ord_zero, $ord_nine);
    } // end of the "isLower()" function

    /**
     * Checks if a character is a space one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a space one or not
     */
    public static function isSpace($c)
    {
        $ord_space = 32;    //ord(' ')
        $ord_tab   = 9;     //ord('\t')
        $ord_CR    = 13;    //ord('\n')
        $ord_NOBR  = 160;   //ord('U+00A0);
        $ord_c     = ord($c);

        return ($ord_c == $ord_space
            || $ord_c == $ord_NOBR
            || PMA_STR_numberInRangeInclusive($ord_c, $ord_tab, $ord_CR));
    } // end of the "isSpace()" function

    /**
     * Checks if a character is an hexadecimal digit
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an hexadecimal digit or not
     */
    public static function isHexDigit($c)
    {
        $ord_Aupper = 65;  //ord('A');
        $ord_Fupper = 70;  //ord('F');
        $ord_Alower = 97;  //ord('a');
        $ord_Flower = 102; //ord('f');
        $ord_zero   = 48;  //ord('0');
        $ord_nine   = 57;  //ord('9');
        $ord_c      = ord($c);

        return (PMA_STR_numberInRangeInclusive($ord_c, $ord_zero, $ord_nine)
            || PMA_STR_numberInRangeInclusive($ord_c, $ord_Aupper, $ord_Fupper)
            || PMA_STR_numberInRangeInclusive($ord_c, $ord_Alower, $ord_Flower));
    } // end of the "isHexDigit()" function
}
?>
