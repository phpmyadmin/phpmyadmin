<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
*
* @version $Id$
* @package phpMyAdmin
*/

if (! defined('PHPMYADMIN')) {
    exit;
}

$ID_KEY      = 'noplugin';

function PMA_getUploadStatus($id) {
    global $SESSION_KEY;
    global $ID_KEY;
  
    if (trim($id) == "") {
        return;
    }
    if (! array_key_exists($id, $_SESSION[$SESSION_KEY])) {
        $_SESSION[$SESSION_KEY][$id] = array(
                    'id'       => $id,
                    'finished' => false,
                    'percent'  => 0,
                    'total'    => 0,
                    'complete' => 0,
	        	    'plugin'   => $ID_KEY
        );
    }
    $ret = $_SESSION[$SESSION_KEY][$id];

    return $ret;
}
?>
