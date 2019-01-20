<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Image JPEG Upload Input Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageUpload
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\Plugins\Transformations\Abs\ImageUploadTransformationsPlugin;

/**
 * Handles the image upload input transformation for JPEG.
 * Has two option: width & height of the thumbnail
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageUpload
 */
// @codingStandardsIgnoreLine
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
