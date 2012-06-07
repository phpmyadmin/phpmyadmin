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
require_once "libraries/plugins/abstract/HexTransformationsPlugin.class.php";

/**
 * Handles the hex transformation for application octetstream
 *
 * @package PhpMyAdmin
 */
class TransformationApplicationOctetStreamHex
    extends HexTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public function getInfo()
    {
        return __(
            'Displays hexadecimal representation of data. Optional first'
            . ' parameter specifies how often space will be added (defaults'
            . ' to 2 nibbles).'
        );
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public function getMIMEType()
    {
        return "Application";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public function getMIMESubtype()
    {
        return "OctetStream";
    }
}
?>