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

/**
 * Function to call Image_PNG_Inline::getInfo();
 *
 * Temporary workaround for bug #3783 :
 * Calling a method from a variable class is not possible before PHP 5.3.
 *
 * This function is called by PMA_getTransformationDescription()
 * in libraries/transformations.lib.php using a variable to construct it's name.
 * This function then calls the static method.
 *
 * Don't use this function unless you are affected by the same issue.
 * Call the static method directly instead.
 *
 * @deprecated
 * @return string Info about transformation class
 */
function Image_PNG_Inline_getInfo()
{
    return Image_PNG_Inline::getInfo();
}
?>
