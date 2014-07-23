<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain External Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage External
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the external transformations interface */
require_once 'libraries/plugins/transformations/abstract/'
    . 'ExternalTransformationsPlugin.class.php';

/**
 * Handles the external transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage External
 */
class Text_Plain_External extends ExternalTransformationsPlugin
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