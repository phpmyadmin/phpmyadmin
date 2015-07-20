<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Image Link Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageLink
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the image link transformations interface */
require_once 'libraries/plugins/transformations/abstract/'
    . 'TextImageLinkTransformationsPlugin.class.php';

/**
 * Handles the image link transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageLink
 */
class Text_Plain_Imagelink extends TextImageLinkTransformationsPlugin
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
