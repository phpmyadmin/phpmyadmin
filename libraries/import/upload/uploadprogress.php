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

$ID_KEY = "UPLOAD_IDENTIFIER";

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
    
    if (! PMA_import_uploadprogressCheck() || $ret['finished']) {
        return $ret;
    }
    
    $status = uploadprogress_get_info($id);

    if ($status) {
        if ($status['bytes_uploaded'] == $status['bytes_total']) {
            $ret['finished'] = true;
        } else {
            $ret['finished'] = false;
        }
        $ret['total']    = $status['bytes_total'];
        $ret['complete'] = $status['bytes_uploaded'];
 
        if ($ret['total'] > 0) {
            $ret['percent'] = $ret['complete'] / $ret['total'] * 100;
        }
    } else {
       $ret = array(
                    'id'       => $id,
                    'finished' => true,
                    'percent'  => 100,
                    'total'    => $ret['total'],
                    'complete' => $ret['total'],
                    'plugin'   => $ID_KEY
                );
    }
    
    $_SESSION[$SESSION_KEY][$id] = $ret;
    
    return $ret;
}
?>
