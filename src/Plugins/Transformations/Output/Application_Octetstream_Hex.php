<?php
/**
 * Application OctetStream Hex Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\HexTransformationsPlugin;

/**
 * Handles the hex transformation for application octetstream
 */
class Application_Octetstream_Hex extends HexTransformationsPlugin
{
    /**
     * Gets the plugin`s MIME type
     */
    public static function getMIMEType(): string
    {
        return 'Application';
    }

    /**
     * Gets the plugin`s MIME subtype
     */
    public static function getMIMESubtype(): string
    {
        return 'OctetStream';
    }
}
