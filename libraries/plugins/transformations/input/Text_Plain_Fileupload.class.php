<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain File Upload Input Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage TextFileUpload
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the text file upload transformations class */
require_once 'libraries/plugins/transformations/abstract/'
    . 'TextFileUploadTransformationsPlugin.class.php';

/**
 * Handles the input text file upload transformation for text plain.
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage TextFileUpload
 */
class Text_Plain_FileUpload extends TextFileUploadTransformationsPlugin
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
