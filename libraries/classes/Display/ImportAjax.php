<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
* Handles plugins that show the upload progress
*
* @package PhpMyAdmin
*/
namespace PhpMyAdmin\Display;

use PhpMyAdmin\Core;

/**
* PhpMyAdmin\Display\ImportAjax class
*
* @package PhpMyAdmin
*/
class ImportAjax
{
    /**
     * Sets up some variables for upload progress
     *
     * @return array
     */
    public static function uploadProgressSetup()
    {
        /**
         * constant for differentiating array in $_SESSION variable
         */
        $SESSION_KEY = '__upload_status';

        /**
         * sets default plugin for handling the import process
         */
        $_SESSION[$SESSION_KEY]["handler"] = "";

        /**
         * unique ID for each upload
         */
        $upload_id = uniqid("");

        /**
         * list of available plugins
         */
        $plugins = array(
            // PHP 5.4 session-based upload progress is problematic, see bug 3964
            //"session",
            "progress",
            "apc",
            "noplugin"
        );

        // select available plugin
        foreach ($plugins as $plugin) {
            $check = $plugin . "Check";

            if (self::$check()) {
                $upload_class = 'PhpMyAdmin\Plugins\Import\Upload\Upload' . ucwords(
                    $plugin
                );
                $_SESSION[$SESSION_KEY]["handler"] = $upload_class;
                break;
            }
        }
        return array($SESSION_KEY, $upload_id, $plugins);
    }

    /**
      * Checks if APC bar extension is available and configured correctly.
      *
      * @return boolean true if APC extension is available and if rfc1867 is enabled,
      *                      false if it is not
      */
    public static function apcCheck()
    {
        if (! extension_loaded('apc')
            || ! function_exists('apc_fetch')
            || ! function_exists('getallheaders')
        ) {
            return false;
        }
        return (ini_get('apc.enabled') && ini_get('apc.rfc1867'));
    }

    /**
     * Checks if PhpMyAdmin\Plugins\Import\Upload\UploadProgress bar extension is
     * available.
     *
     * @return boolean true if PhpMyAdmin\Plugins\Import\Upload\UploadProgress
     * extension is available, false if it is not
     */
    public static function progressCheck()
    {
        return function_exists("uploadprogress_get_info")
            && function_exists('getallheaders');
    }

    /**
      * Checks if PHP 5.4 session upload-progress feature is available.
      *
      * @return boolean true if PHP 5.4 session upload-progress is available,
      *                 false if it is not
      */
    public static function sessionCheck()
    {
        return ini_get('session.upload_progress.enabled');
    }

    /**
      * Default plugin for handling import.
      * If no other plugin is available, noplugin is used.
      *
      * @return boolean true
      */
    public static function nopluginCheck()
    {
        return true;
    }

    /**
      * The function outputs json encoded status of uploaded.
      * It uses PMA_getUploadStatus, which is defined in plugin's file.
      *
      * @param string $id ID of transfer, usually $upload_id
      *
      * @return void
      */
    public static function status($id)
    {
        Core::headerJSON();
        echo json_encode(
            $_SESSION[$GLOBALS['SESSION_KEY']]['handler']::getUploadStatus($id)
        );
    }
}
