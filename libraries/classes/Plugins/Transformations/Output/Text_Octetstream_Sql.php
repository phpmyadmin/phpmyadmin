<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Blob SQL Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\SQLTransformationsPlugin;

/**
 * Handles the sql transformation for blob data
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
// @codingStandardsIgnoreLine
class Text_Octetstream_Sql extends SQLTransformationsPlugin
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
        return "Octetstream";
    }
}
