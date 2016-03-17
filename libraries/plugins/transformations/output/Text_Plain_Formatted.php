<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Formatted Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Formatted
 */
namespace PMA\libraries\plugins\transformations\output;

use PMA\libraries\plugins\transformations\abs\FormattedTransformationsPlugin;

/**
 * Handles the formatted transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Formatted
 */
class Text_Plain_Formatted extends FormattedTransformationsPlugin
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
