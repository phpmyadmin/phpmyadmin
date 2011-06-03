<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * "Echo" service to allow force downloading of exported charts 
 *
 * @package phpMyAdmin
 */

if(isset($_POST['filename']) && isset($_POST['image'])) {
	header("Cache-Control: public");
	header("Content-Description: File Transfer");
	header("Content-Disposition: attachment; filename=".$_POST['filename']);
	header("Content-Type: image/png");
	header("Content-Transfer-Encoding: binary");
	
	echo base64_decode(substr($_POST['image'],strpos($_POST['image'],',')+1));
}
?>