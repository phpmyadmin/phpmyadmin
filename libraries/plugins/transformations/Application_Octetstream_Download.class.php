<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Application OctetStream Download Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Download
 */
if (! defined('PHPMYADMIN')) {
    exit;
}
/* Get the download transformations interface */
require_once 'abstract/DownloadTransformationsPlugin.class.php';

/**
 * Handles the download transformation for application octetstream
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Download
 */
class Application_Octetstream_Download extends DownloadTransformationsPlugin
{
    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Application";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "OctetStream";
    }
}

/**
 * Function to call Application_Octetstream_Download::getInfo();
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
function Application_Octetstream_Download_getInfo()
{
    return Application_Octetstream_Download::getInfo();
}
?>
