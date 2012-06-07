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
require_once "libraries/plugins/abstract/LinkTransformationsPlugin.class.php";

/**
 * Handles the link transformation for image jpeg
 *
 * @package PhpMyAdmin
 */
class TransformationImageJPEGLink
    extends LinkTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public function getInfo()
    {
        return __(
            'Displays a link to download this image.'
        );
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public function getMIMEType()
    {
        return "Image";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public function getMIMESubtype()
    {
        return "JPEG";
    }
}
?>