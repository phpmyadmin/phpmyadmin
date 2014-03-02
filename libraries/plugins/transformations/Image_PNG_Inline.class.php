<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Image PNG Inline Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Inline
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the inline transformations interface */
require_once 'abstract/InlineTransformationsPlugin.class.php';

/**
 * Handles the inline transformation for image png
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Inline
 */
class Image_PNG_Inline extends InlineTransformationsPlugin
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
        return "PNG";
    }
}
?>