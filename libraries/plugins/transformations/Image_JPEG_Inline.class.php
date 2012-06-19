<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Image JPEG Inline Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Inline
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the inline transformations interface */
require_once "abstract/InlineTransformationsPlugin.class.php";

/**
 * Handles the inline transformation for image jpeg
 *
 * @package PhpMyAdmin
 */
class Image_JPEG_Inline extends InlineTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a clickable thumbnail. The options are the maximum width'
            . ' and height in pixels. The original aspect ratio is preserved.'
        );
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Image";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "JPEG";
    }
}
?>