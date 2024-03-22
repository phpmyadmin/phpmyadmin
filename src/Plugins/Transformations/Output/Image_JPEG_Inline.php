<?php
/**
 * Image JPEG Inline Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\InlineTransformationsPlugin;

/**
 * Handles the inline transformation for image jpeg
 */
class Image_JPEG_Inline extends InlineTransformationsPlugin
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
