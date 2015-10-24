<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Prepend/Append Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage PreApPend
 */
namespace PMA\libraries\plugins\transformations;

use PMA\libraries\plugins\transformations\abs\PreApPendTransformationsPlugin;

/**
 * Handles the prepend and/or append transformation for text plain.
 * Has two options: the text to be prepended and appended (if any, default '')
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage PreApPend
 */
class Text_Plain_PreApPend extends PreApPendTransformationsPlugin
{
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
