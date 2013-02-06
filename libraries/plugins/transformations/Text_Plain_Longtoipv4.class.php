<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Long To IPv4 Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage LongToIPv4
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the long to ipv4 transformations interface */
require_once 'abstract/LongToIPv4TransformationsPlugin.class.php';

/**
 * Handles the long to ipv4 transformation for text plain
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage LongToIPv4
 */
class Text_Plain_Longtoipv4 extends LongToIPv4TransformationsPlugin
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
 * Function to call Text_Plain_Longtoipv4::getInfo();
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
function Text_Plain_Longtoipv4_getInfo()
{
    return Text_Plain_Longtoipv4::getInfo();
}
?>
