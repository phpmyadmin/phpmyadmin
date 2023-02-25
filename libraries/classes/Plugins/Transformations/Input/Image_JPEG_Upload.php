<?php
/**
 * Image JPEG Upload Input Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\Plugins\Transformations\Abs\ImageUploadTransformationsPlugin;

/**
 * Handles the image upload input transformation for JPEG.
 * Has two option: width & height of the thumbnail
 */
class Image_JPEG_Upload extends ImageUploadTransformationsPlugin
{
    /**
     * Gets the plugin`s MIME type
     */
    public static function getMIMEType(): string
    {
        return 'Image';
    }

    /**
     * Gets the plugin`s MIME subtype
     */
    public static function getMIMESubtype(): string
    {
        return 'JPEG';
    }
}
