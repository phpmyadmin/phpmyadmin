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
require_once 'abstract/Bool2TextTransformationsPlugin.class.php';

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

/**
 * Function to call Text_Plain_Bool2Text::getInfo();
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
function Text_Plain_Bool2Text_getInfo()
{
    return Text_Plain_Bool2Text::getInfo();
}
?>
