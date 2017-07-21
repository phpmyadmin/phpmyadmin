<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Link Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
namespace PhpMyAdmin\Plugins\Transformations;

use PhpMyAdmin\Plugins\Transformations\Abs\TextLinkTransformationsPlugin;

/**
 * Handles the link transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
// @codingStandardsIgnoreLine
class Text_Plain_Link extends TextLinkTransformationsPlugin
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
