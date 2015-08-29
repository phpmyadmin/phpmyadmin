<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for classes based on StringByte interface.
 *
 * @package PhpMyAdmin-String
 */
namespace PMA\libraries;

/**
 * Implements StringByte interface using native PHP functions.
 *
 * @package PhpMyAdmin-String
 */
abstract class StringAbstractType implements StringType
{
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
        return ($num >= $lower && $num <= $upper);
    }
}
