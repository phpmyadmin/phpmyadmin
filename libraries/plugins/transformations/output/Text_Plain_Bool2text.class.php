<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Bool2Text Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Bool2Text
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the Bool2Text transformations interface */
require_once 'libraries/plugins/transformations/abstract/'
    . 'Bool2TextTransformationsPlugin.class.php';

/**
 * Handles the Boolean to Text transformation for text plain.
 * Has one option: the output format (default 'T/F')
 * or 'Y/N'
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Bool2Text
 */
class Text_Plain_Bool2Text extends Bool2TextTransformationsPlugin
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
?>
