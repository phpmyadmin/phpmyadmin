<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Image JPEG Link Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\ImageLinkTransformationsPlugin;

/**
 * Handles the link transformation for image jpeg
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
// @codingStandardsIgnoreLine
class Image_JPEG_Link extends ImageLinkTransformationsPlugin
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
