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

require_once 'libraries/StringAbstractType.class.php';

/**
 * Implements PMA_StringByte interface using native PHP functions.
 *
 * @package    PhpMyAdmin-String
 * @subpackage Native
 * @todo       May be join this class with PMA_StringNative class
 */
class PMA_StringNativeType extends PMA_StringAbstractType
{
    /**
     * Checks if a character is an alphanumeric one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an alphanumeric one or not
     */
    public function isAlnum($c)
    {
        return ($this->isAlpha($c) || $this->isDigit($c));
    } // end of the "isAlnum()" function

    /**
     * Checks if a character is an alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an alphabetic one or not
     */
    public function isAlpha($c)
    {
        return ($this->isUpper($c) || $this->isLower($c));
    } // end of the "isAlpha()" function

    /**
     * Checks if a character is a digit
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a digit or not
     */
    public function isDigit($c)
    {
        $ord_zero = 48; //ord('0');
        $ord_nine = 57; //ord('9');
        $ord_c    = ord($c);

        return $this->numberInRangeInclusive($ord_c, $ord_zero, $ord_nine);
    } // end of the "isDigit()" function

    /**
     * Checks if a character is an upper alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an upper alphabetic one or not
     */
    public function isUpper($c)
    {
        $ord_A = 65; //ord('A');
        $ord_Z = 90; //ord('Z');
        $ord_c    = ord($c);

        return $this->numberInRangeInclusive($ord_c, $ord_A, $ord_Z);
    } // end of the "isUpper()" function

    /**
     * Checks if a character is a lower alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a lower alphabetic one or not
     */
    public function isLower($c)
    {
        $ord_a = 97;  //ord('a');
        $ord_z = 122; //ord('z');
        $ord_c    = ord($c);

        return $this->numberInRangeInclusive($ord_c, $ord_a, $ord_z);
    } // end of the "isLower()" function

    /**
     * Checks if a character is a space one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a space one or not
     */
    public function isSpace($c)
    {
        $ord_space = 32;    //ord(' ')
        $ord_tab   = 9;     //ord('\t')
        $ord_CR    = 13;    //ord('\n')
        $ord_NOBR  = 160;   //ord('U+00A0);
        $ord_c     = ord($c);

        return ($ord_c == $ord_space
            || $ord_c == $ord_NOBR
            || $this->numberInRangeInclusive($ord_c, $ord_tab, $ord_CR));
    } // end of the "isSpace()" function

    /**
     * Checks if a character is an hexadecimal digit
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an hexadecimal digit or not
     */
    public function isHexDigit($c)
    {
        $ord_Aupper = 65;  //ord('A');
        $ord_Fupper = 70;  //ord('F');
        $ord_Alower = 97;  //ord('a');
        $ord_Flower = 102; //ord('f');
        $ord_zero   = 48;  //ord('0');
        $ord_nine   = 57;  //ord('9');
        $ord_c      = ord($c);

        return ($this->numberInRangeInclusive($ord_c, $ord_zero, $ord_nine)
            || $this->numberInRangeInclusive($ord_c, $ord_Aupper, $ord_Fupper)
            || $this->numberInRangeInclusive($ord_c, $ord_Alower, $ord_Flower));
    } // end of the "isHexDigit()" function
}
?>
