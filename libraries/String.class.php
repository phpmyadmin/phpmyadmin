<?php
/**
 * Specialized string class for phpMyAdmin.
 * The SQL Parser code relies heavily on these functions.
 *
 * @package PhpMyAdmin-String
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/StringType.int.php';
require_once 'libraries/StringByte.int.php';
/**
 * Specialized string class for phpMyAdmin.
 * The SQL Parser code relies heavily on these functions.
 *
 * @package PhpMyAdmin-String
 */
class PMA_String implements PMA_StringType
{
    /**
     * @var PMA_StringType
     */
    private $_type;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (@extension_loaded('ctype')) {
            include_once 'libraries/StringCType.class.php';
            $this->_type = new PMA_StringCType();
        } else {
            include_once 'libraries/StringNativeType.class.php';
            $this->_type = new PMA_StringNativeType();
        }
    }

    /**
     * Checks if a given character position in the string is escaped or not
     *
     * @param string  $string string to check for
     * @param integer $pos    the character to check for
     * @param integer $start  starting position in the string
     *
     * @return boolean  whether the character is escaped or not
     */
    public function charIsEscaped($string, $pos, $start = 0)
    {
        $pos = max(intval($pos), 0);
        $start = max(intval($start), 0);
        $len = /*overload*/mb_strlen($string);
        // Base case:
        // Check for string length or invalid input or special case of input
        // (pos == $start)
        if ($pos <= $start || $len <= max($pos, $start)) {
            return false;
        }

        $pos--;
        $escaped     = false;
        while ($pos >= $start && /*overload*/mb_substr($string, $pos, 1) == '\\') {
            $escaped = !$escaped;
            $pos--;
        } // end while

        return $escaped;
    }

    /**
     * Checks if a character is an SQL identifier
     *
     * @param string  $c            character to check for
     * @param boolean $dot_is_valid whether the dot character is valid or not
     *
     * @return boolean  whether the character is an SQL identifier or not
     */
    public function isSqlIdentifier($c, $dot_is_valid = false)
    {
        return ($this->isAlnum($c)
            || ($ord_c = /*overload*/mb_ord($c)) && $ord_c >= 192 && $ord_c != 215 &&
            $ord_c != 249
            || $c == '_'
            || $c == '$'
            || ($dot_is_valid != false && $c == '.'));
    }

    /**
     * Checks if a character is an alphanumeric one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an alphanumeric one or not
     */
    public function isAlnum($c)
    {
        return $this->_type->isAlnum($c);
    }

    /**
     * Checks if a character is an alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an alphabetic one or not
     */
    public function isAlpha($c)
    {
        return $this->_type->isAlpha($c);
    }

    /**
     * Checks if a character is a digit
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a digit or not
     */
    public function isDigit($c)
    {
        return $this->_type->isDigit($c);
    }

    /**
     * Checks if a character is an upper alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an upper alphabetic one or not
     */
    public function isUpper($c)
    {
        return $this->_type->isUpper($c);
    }

    /**
     * Checks if a character is a lower alphabetic one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a lower alphabetic one or not
     */
    public function isLower($c)
    {
        return $this->_type->isLower($c);
    }

    /**
     * Checks if a character is a space one
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is a space one or not
     */
    public function isSpace($c)
    {
        return $this->_type->isSpace($c);
    }

    /**
     * Checks if a character is an hexadecimal digit
     *
     * @param string $c character to check for
     *
     * @return boolean whether the character is an hexadecimal digit or not
     */
    public function isHexDigit($c)
    {
        return $this->_type->isHexDigit($c);
    }

    /**
     * Checks if a number is in a range
     *
     * @param integer $num   number to check for
     * @param integer $lower lower bound
     * @param integer $upper upper bound
     *
     * @return boolean  whether the number is in the range or not
     */
    public function numberInRangeInclusive($num, $lower, $upper)
    {
        return $this->_type->numberInRangeInclusive($num, $lower, $upper);
    }
}
?>
