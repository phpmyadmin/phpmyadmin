<?php
/**
 * Image JPEG Link Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\ImageLinkTransformationsPlugin;

/**
 * Handles the link transformation for image jpeg
 */
class Image_JPEG_Link extends ImageLinkTransformationsPlugin
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
