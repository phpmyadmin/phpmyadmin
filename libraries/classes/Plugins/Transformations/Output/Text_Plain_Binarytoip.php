<?php
/**
 * Handles the binary to IPv4/IPv6 transformation for text plain
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Utils\FormatConverter;

use function __;

/**
 * Handles the binary to IPv4/IPv6 transformation for text plain
 */
class Text_Plain_Binarytoip extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the plugin
     */
    public static function getInfo(): string
    {
        return __(
            'Converts an Internet network address stored as a binary string'
            . ' into a string in Internet standard (IPv4/IPv6) format.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed. a binary string containing
     *                                    an IP address, as returned from MySQL's INET6_ATON
     *                                    function
     * @param array              $options transformation options
     * @param FieldMetadata|null $meta    meta information
     *
     * @return string IP address
     */
    public function applyTransformation($buffer, array $options = [], ?FieldMetadata $meta = null)
    {
        $isBinary = ($meta !== null && $meta->isBinary);

        return FormatConverter::binaryToIp($buffer, $isBinary);
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the plugin
     */
    public static function getName(): string
    {
        return 'Binary To IPv4/IPv6';
    }

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
