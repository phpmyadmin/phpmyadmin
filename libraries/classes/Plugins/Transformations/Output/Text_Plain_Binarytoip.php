<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles the binary to IPv4/IPv6 transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage BinaryToIP
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\TransformationsPlugin;
use stdClass;

/**
 * Handles the binary to IPv4/IPv6 transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage BinaryToIP
 */
// @codingStandardsIgnoreLine
class Text_Plain_Binarytoip extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Converts an Internet network address stored as a binary string'
            . ' into a string in Internet standard (IPv4/IPv6) format.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string        $buffer  text to be transformed. a binary string containing
     *                               an IP address, as returned from MySQL's INET6_ATON
     *                               function
     * @param array         $options transformation options
     * @param stdClass|null $meta    meta information
     *
     * @return string IP address
     */
    public function applyTransformation($buffer, array $options = [], ?stdClass $meta = null)
    {
        if (0 !== strpos($buffer, '0x')) {
            return $buffer;
        }

        $ipHex = substr($buffer, 2);
        $ipBin = hex2bin($ipHex);

        if (false === $ipBin) {
            return $buffer;
        }

        return @inet_ntop($ipBin);
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Binary To IPv4/IPv6";
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Text";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "Plain";
    }
}
