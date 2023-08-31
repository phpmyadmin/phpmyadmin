<?php
/**
 * Text Plain Long To IPv4 Transformations plugin for phpMyAdmin
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations;

use PhpMyAdmin\Plugins\Transformations\Abs\LongToIPv4TransformationsPlugin;

/**
 * Handles the long to ipv4 transformation for text plain
 */
class Text_Plain_Longtoipv4 extends LongToIPv4TransformationsPlugin
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
        return 'Plain';
    }
}
