<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Implements PMA_StringType interface using the "ctype" extension.
 * Methods of the "ctype" extension are faster compared to PHP versions of them.
 *
 * @package    PhpMyAdmin-String
 * @subpackage CType
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/StringAbstractType.class.php';

/**
 * Implements PMA_StringType interface using the "ctype" extension.
 * Methods of the "ctype" extension are faster compared to PHP versions of them.
 *
 * @package    PhpMyAdmin-String
 * @subpackage CType
 */
class PMA_StringCType extends PMA_StringAbstractType
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
        return ctype_alnum($c);
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
        return ctype_alpha($c);
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
        return ctype_digit($c);
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
        return ctype_upper($c);
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
        return ctype_lower($c);
    } // end of the "PisLower()" function

    /**
     * Checks if a character is a space one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a space one or not
     */
    public function isSpace($c)
    {
        return ctype_space($c);
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
        return ctype_xdigit($c);
    } // end of the "isHexDigit()" function
}
?>
