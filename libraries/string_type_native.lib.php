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
 * @version $Id$
 * @package phpMyAdmin-StringType-Native
 */

/**
 * Checks if a character is an alphanumeric one
 *
 * @uses    PMA_STR_isUpper()
 * @uses    PMA_STR_isLower()
 * @uses    PMA_STR_isDigit()
 * @param   string   character to check for
 * @return  boolean  whether the character is an alphanumeric one or not
 */
function PMA_STR_isAlnum($c)
{
    return (PMA_STR_isUpper($c) || PMA_STR_isLower($c) || PMA_STR_isDigit($c));
} // end of the "PMA_STR_isAlnum()" function

/**
 * Checks if a character is an alphabetic one
 *
 * @uses    PMA_STR_isUpper()
 * @uses    PMA_STR_isLower()
 * @param   string   character to check for
 * @return  boolean  whether the character is an alphabetic one or not
 */
function PMA_STR_isAlpha($c)
{
    return (PMA_STR_isUpper($c) || PMA_STR_isLower($c));
} // end of the "PMA_STR_isAlpha()" function

/**
 * Checks if a character is a digit
 *
 * @uses    PMA_STR_numberInRangeInclusive()
 * @uses    ord()
 * @param   string   character to check for
 * @return  boolean  whether the character is a digit or not
 */
function PMA_STR_isDigit($c)
{
    $ord_zero = 48; //ord('0');
    $ord_nine = 57; //ord('9');
    $ord_c    = ord($c);

    return PMA_STR_numberInRangeInclusive($ord_c, $ord_zero, $ord_nine);
} // end of the "PMA_STR_isDigit()" function

/**
 * Checks if a character is an upper alphabetic one
 *
 * @uses    PMA_STR_numberInRangeInclusive()
 * @uses    ord()
 * @param   string   character to check for
 * @return  boolean  whether the character is an upper alphabetic one or not
 */
function PMA_STR_isUpper($c)
{
    $ord_zero = 65; //ord('A');
    $ord_nine = 90; //ord('Z');
    $ord_c    = ord($c);

    return PMA_STR_numberInRangeInclusive($ord_c, $ord_zero, $ord_nine);
} // end of the "PMA_STR_isUpper()" function

/**
 * Checks if a character is a lower alphabetic one
 *
 * @uses    PMA_STR_numberInRangeInclusive()
 * @uses    ord()
 * @param   string   character to check for
 * @return  boolean  whether the character is a lower alphabetic one or not
 */
function PMA_STR_isLower($c)
{
    $ord_zero = 97;  //ord('a');
    $ord_nine = 122; //ord('z');
    $ord_c    = ord($c);

    return PMA_STR_numberInRangeInclusive($ord_c, $ord_zero, $ord_nine);
} // end of the "PMA_STR_isLower()" function

/**
 * Checks if a character is a space one
 *
 * @uses    PMA_STR_numberInRangeInclusive()
 * @uses    ord()
 * @param   string   character to check for
 * @return  boolean  whether the character is a space one or not
 */
function PMA_STR_isSpace($c)
{
    $ord_space = 32;    //ord(' ')
    $ord_tab   = 9;     //ord('\t')
    $ord_CR    = 13;    //ord('\n')
    $ord_NOBR  = 160;   //ord('U+00A0);
    $ord_c     = ord($c);

    return ($ord_c == $ord_space
         || $ord_c == $ord_NOBR
         || PMA_STR_numberInRangeInclusive($ord_c, $ord_tab, $ord_CR));
} // end of the "PMA_STR_isSpace()" function

/**
 * Checks if a character is an hexadecimal digit
 *
 * @uses    PMA_STR_numberInRangeInclusive()
 * @uses    ord()
 * @param   string   character to check for
 * @return  boolean  whether the character is an hexadecimal digit or not
 */
function PMA_STR_isHexDigit($c)
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
} // end of the "PMA_STR_isHexDigit()" function

?>
