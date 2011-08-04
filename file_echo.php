<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * "Echo" service to allow force downloading of exported charts (png or svg) and server status monitor settings
 *
 * @package phpMyAdmin
 */
 

define('PMA_MINIMUM_COMMON', true);

require_once './libraries/common.inc.php';

if(isset($_REQUEST['filename']) && isset($_REQUEST['image'])) {
    $allowed = Array( 'image/png'=>'png', 'image/svg+xml'=>'svg');
    
    if (! isset($allowed[$_REQUEST['type']])) exit('Invalid export type');
    
    if (! preg_match("/(".implode("|",$allowed).")$/i", $_REQUEST['filename']))
        $_REQUEST['filename'] .= '.' . $allowed[$_REQUEST['type']];
        
    downloadHeader($_REQUEST['filename'],$_REQUEST['type']);

    if ($allowed[$_REQUEST['type']] != 'svg')
        echo base64_decode(substr($_REQUEST['image'], strpos($_REQUEST['image'],',') + 1));
    else
        echo $_REQUEST['image'];
        
    exit();
}
    
if(isset($_REQUEST['monitorconfig'])) {
    downloadHeader('monitor.cfg','application/force-download');
    echo urldecode($_REQUEST['monitorconfig']);
    exit();
}

if(isset($_REQUEST['import'])) {
    echo '<html><body>' . file_get_contents($_FILES['file']['tmp_name']) . '</body></html>';
    exit();
} 

exit('Invalid request');

function downloadHeader($file,$type) {
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=".$file);
    header("Content-Type: ".$type);
    header("Content-Transfer-Encoding: binary");
}
?>