<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Append Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Append
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the append transformations interface */
require_once 'abstract/AppendTransformationsPlugin.class.php';

/**
 * Handles the append transformation for text plain.
 * Has one option: the text to be appended (default '')
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Append
 */
class Text_Plain_Append extends AppendTransformationsPlugin
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