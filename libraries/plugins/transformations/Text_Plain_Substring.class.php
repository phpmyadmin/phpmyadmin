<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Substring Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Substring
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the substring transformations interface */
require_once "abstract/SubstringTransformationsPlugin.class.php";

/**
 * Handles the substring transformation for text plain
 *
 * @package PhpMyAdmin
 */
class Text_Plain_Substring extends SubstringTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a part of a string. The first option is the number of'
            . ' characters to skip from the beginning of the string (Default 0).'
            . ' The second option is the number of characters to return (Default:'
            . ' until end of string). The third option is the string to append'
            . ' and/or prepend when truncation occurs (Default: "...").'
        );
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Text";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "Plain";
    }
}
?>