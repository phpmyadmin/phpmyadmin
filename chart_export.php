<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * "Echo" service to allow force downloading of exported charts (png or svg)
 *
 * @package phpMyAdmin
 */
 

define('PMA_MINIMUM_COMMON', true);

require_once './libraries/common.inc.php';

if (isset($_REQUEST['filename']) && isset($_REQUEST['image'])) {
    $allowed = Array( 'image/png'=>'png', 'image/svg+xml'=>'svg');
    
    if (! isset($allowed[$_REQUEST['type']])) exit('Invalid export type');
    
    if (! preg_match("/(".implode("|",$allowed).")$/i", $_REQUEST['filename']))
        $_REQUEST['filename'] .= '.' . $allowed[$_REQUEST['type']];

    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=".$_REQUEST['filename']);
    header("Content-Type: ".$_REQUEST['type']);
    header("Content-Transfer-Encoding: binary");
    
    if ($allowed[$_REQUEST['type']] != 'svg')
        echo base64_decode(substr($_REQUEST['image'], strpos($_REQUEST['image'],',') + 1));
    else
        echo $_REQUEST['image'];
    
} else exit('Invalid request');
?>