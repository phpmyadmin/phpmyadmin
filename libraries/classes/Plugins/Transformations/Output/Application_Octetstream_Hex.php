<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Application OctetStream Hex Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Hex
 */
namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\HexTransformationsPlugin;

/**
 * Handles the hex transformation for application octetstream
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Hex
 */
// @codingStandardsIgnoreLine
class Application_Octetstream_Hex extends HexTransformationsPlugin
{
    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Application";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "OctetStream";
    }
}
