<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Image JPEG Inline Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Inline
 */
namespace PMA\libraries\plugins\transformations\output;

use PMA\libraries\plugins\transformations\abs\InlineTransformationsPlugin;

/**
 * Handles the inline transformation for image jpeg
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Inline
 */
class Image_JPEG_Inline extends InlineTransformationsPlugin
{
    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Image";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "JPEG";
    }
}
