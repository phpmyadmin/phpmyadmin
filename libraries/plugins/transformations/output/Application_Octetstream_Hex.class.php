<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Application OctetStream Hex Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Hex
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the hex transformations interface */
require_once 'libraries/plugins/transformations/abstract/'
    . 'HexTransformationsPlugin.class.php';

/**
 * Handles the hex transformation for application octetstream
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Hex
 */
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
?>