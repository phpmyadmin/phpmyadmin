<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Application OctetStream Download Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Download
 */
if (! defined('PHPMYADMIN')) {
    exit;
}
/* Get the download transformations interface */
require_once "abstract/DownloadTransformationsPlugin.class.php";

/**
 * Handles the download transformation for application octetstream
 *
 * @package PhpMyAdmin
 */
class Application_Octetstream_Download extends DownloadTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a link to download the binary data of the column. You can'
            . ' use the first option to specify the filename, or use the second'
            . ' option as the name of a column which contains the filename. If'
            . ' you use the second option, you need to set the first option to'
            . ' the empty string.'
        );
    }

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
?>