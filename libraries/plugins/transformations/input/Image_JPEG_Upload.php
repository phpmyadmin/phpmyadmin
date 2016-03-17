<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Image JPEG Upload Input Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageUpload
 */
namespace PMA\libraries\plugins\transformations\input;

use PMA\libraries\plugins\transformations\abs\ImageUploadTransformationsPlugin;

/**
 * Handles the image upload input transformation for JPEG.
 * Has two option: width & height of the thumbnail
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageUpload
 */
class Image_JPEG_Upload extends ImageUploadTransformationsPlugin
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
