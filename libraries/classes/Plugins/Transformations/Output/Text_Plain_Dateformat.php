<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Date Format Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage DateFormat
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\DateFormatTransformationsPlugin;

/**
 * Handles the date format transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage DateFormat
 */
// @codingStandardsIgnoreLine
class Text_Plain_Dateformat extends DateFormatTransformationsPlugin
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
