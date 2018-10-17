<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Application OctetStream Download Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Download
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Output;

use PhpMyAdmin\Plugins\Transformations\Abs\DownloadTransformationsPlugin;

/**
 * Handles the download transformation for application octetstream
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Download
 */
// @codingStandardsIgnoreLine
class Application_Octetstream_Download extends DownloadTransformationsPlugin
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
