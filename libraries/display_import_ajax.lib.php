<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
* Handles plugins that show the upload progress
*
* @package PhpMyAdmin
*/
if (! defined('PHPMYADMIN')) {
    exit;
}

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
  *
  * Each plugin has own checkfunction in display_import_ajax.lib.php
  * and own file with functions in upload_#KEY#.php
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
    $check = "PMA_Import_" . $plugin . "Check";

    if ($check()) {
        $upload_class = "Upload" . ucwords($plugin);
        $_SESSION[$SESSION_KEY]["handler"] = $upload_class;
        include_once "plugins/import/upload/" . $upload_class . ".class.php";
        break;
    }
}

/**
  * Checks if APC bar extension is available and configured correctly.
  *
  * @return boolean true if APC extension is available and if rfc1867 is enabled,
  *                      false if it is not
  */
function PMA_Import_apcCheck()
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
  * Checks if UploadProgress bar extension is available.
  *
  * @return boolean true if UploadProgress extension is available,
  *                 false if it is not
  */
function PMA_Import_progressCheck()
{
    if (! function_exists("uploadprogress_get_info")
        || ! function_exists('getallheaders')
    ) {
        return false;
    }
    return true;
}

/**
  * Checks if PHP 5.4 session upload-progress feature is available.
  *
  * @return boolean true if PHP 5.4 session upload-progress is available,
  *                 false if it is not
  */
function PMA_Import_sessionCheck()
{
    if (PMA_PHP_INT_VERSION < 50400
        || ! ini_get('session.upload_progress.enabled')
    ) {
        return false;
    }
    return true;
}

/**
  * Default plugin for handling import.
  * If no other plugin is available, noplugin is used.
  *
  * @return boolean true
  */
function PMA_Import_nopluginCheck()
{
    return true;
}

/**
  * The function outputs json encoded status of uploaded.
  * It uses PMA_getUploadStatus, which is defined in plugin's file.
  *
  * @param string $id ID of transfer, usually $upload_id
  *                   from display_import_ajax.lib.php
  *
  * @return void
  */
function PMA_importAjaxStatus($id)
{
    PMA_headerJSON();
    echo json_encode(
        $_SESSION[$GLOBALS['SESSION_KEY']]['handler']::getUploadStatus($id)
    );
}
