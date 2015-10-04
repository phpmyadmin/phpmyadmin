<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains Advisor functions
 *
 * @package PhpMyAdmin
 */

/**
 * Formats interval like 10 per hour
 *
 * @param integer $num       number to format
 * @param integer $precision required precision
 *
 * @return string formatted string
 */
function ADVISOR_bytime($num, $precision)
{
    if ($num >= 1) { // per second
        $per = __('per second');
    } elseif ($num * 60 >= 1) { // per minute
        $num = $num * 60;
        $per = __('per minute');
    } elseif ($num * 60 * 60 >= 1 ) { // per hour
        $num = $num * 60 * 60;
        $per = __('per hour');
    } else {
        $num = $num * 60 * 60 * 24;
        $per = __('per day');
    }

    $num = round($num, $precision);

    if ($num == 0) {
        $num = '<' . PMA\libraries\Util::pow(10, -$precision);
    }

    return "$num $per";
}

/**
 * Wrapper for PMA\libraries\Util::timespanFormat
 *
 * This function is used when evaluating advisory_rules.txt
 *
 * @param int $seconds the timespan
 *
 * @return string  the formatted value
 */
function ADVISOR_timespanFormat($seconds)
{
    return PMA\libraries\Util::timespanFormat($seconds);
}

/**
 * Wrapper around PMA\libraries\Util::formatByteDown
 *
 * This function is used when evaluating advisory_rules.txt
 *
 * @param double $value the value to format
 * @param int    $limes the sensitiveness
 * @param int    $comma the number of decimals to retain
 *
 * @return string the formatted value with unit
 */
function ADVISOR_formatByteDown($value, $limes = 6, $comma = 0)
{
    return implode(' ', PMA\libraries\Util::formatByteDown($value, $limes, $comma));
}
