<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
*
* @package PhpMyAdmin
*/

if (!defined('PHPMYADMIN')) {
    exit;
}
/**
  * constant for differenciating array in $_SESSION variable
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
       "uploadprogress",
       "apc",
       "noplugin"
       ); // available plugins. Each plugin has own checkfunction in display_import_ajax.lib.php and own file with functions in upload_#KEY#.php

// select available plugin
foreach ($plugins as $plugin) {
    $check = "PMA_import_" . $plugin . "Check";

    if ($check()) {
        $_SESSION[$SESSION_KEY]["handler"] = $plugin;
        include_once "import/upload/" . $plugin . ".php";
        break;
    }
}

/**
  * Checks if APC bar extension is available and configured correctly.
  *
  * @return true if APC extension is available and if rfc1867 is enabled, false if it is not
  */
function PMA_import_apcCheck()
{
    if (! extension_loaded('apc') || ! function_exists('apc_fetch') || ! function_exists('getallheaders')) {
        return false;
    }
    return (ini_get('apc.enabled') && ini_get('apc.rfc1867'));
}

/**
  * Checks if UploadProgress bar extension is available.
  *
  * @return true if UploadProgress extension is available, false if it is not
  */
function PMA_import_uploadprogressCheck()
{
    if (! function_exists("uploadprogress_get_info") || ! function_exists('getallheaders')) {
        return false;
    }
    return true;
}
/**
  * Default plugin for handling import. If no other plugin is available, noplugin is used.
  *
  * @return true
  */
function PMA_import_nopluginCheck()
{
    return true;
}

/**
  * The function outputs json encoded status of uploaded. It uses PMA_getUploadStatus, which is defined in plugin's file.
  *
  * @param $id - ID of transfer, usually $upload_id from display_import_ajax.lib.php
  */
function PMA_importAjaxStatus($id)
{
    header('Content-type: application/json');
    echo json_encode(PMA_getUploadStatus($id));
}
?>
