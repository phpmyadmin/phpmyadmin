<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Date Format Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage DateFormat
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the date format transformations interface */
require_once "libraries/plugins/abstract/DateFormatTransformationsPlugin.class.php";

/**
 * Handles the date format transformation for text plain
 *
 * @package PhpMyAdmin
 */
class Text_Plain_Dateformat extends DateFormatTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public function getInfo()
    {
        return __(
            'Displays a TIME, TIMESTAMP, DATETIME or numeric unix timestamp'
            . ' column as formatted date. The first option is the offset (in'
            . ' hours) which will be added to the timestamp (Default: 0). Use'
            . ' second option to specify a different date/time format string.'
            . ' Third option determines whether you want to see local date or'
            . ' UTC one (use "local" or "utc" strings) for that. According to'
            . ' that, date format has different value - for "local" see the'
            . ' documentation for PHP\'s strftime() function and for "utc" it'
            . ' is done using gmdate() function.'
        );
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public function getMIMEType()
    {
        return "Text";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public function getMIMESubtype()
    {
        return "Plain";
    }
}
?>