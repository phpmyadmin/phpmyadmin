<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Image JPEG Link Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the link transformations interface */
require_once 'libraries/plugins/transformations/abstract/'
    . 'ImageLinkTransformationsPlugin.class.php';

/**
 * Handles the link transformation for image jpeg
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
class Image_JPEG_Link extends ImageLinkTransformationsPlugin
{
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
