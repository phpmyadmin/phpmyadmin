<?php
/**
 * Blob SQL Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\SQLTransformationsPlugin;

/**
 * Handles the sql transformation for blob data
 */
class Text_Octetstream_Sql extends SQLTransformationsPlugin
{
    /**
     * Gets the plugin`s MIME type
     */
    public static function getMIMEType(): string
    {
        return 'Text';
    }

    /**
     * Gets the plugin`s MIME subtype
     */
    public static function getMIMESubtype(): string
    {
        return 'Octetstream';
    }
}
