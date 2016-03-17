<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain External Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage External
 */
namespace PMA\libraries\plugins\transformations\output;

use PMA\libraries\plugins\transformations\abs\ExternalTransformationsPlugin;

/**
 * Handles the external transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage External
 */
class Text_Plain_External extends ExternalTransformationsPlugin
{
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
