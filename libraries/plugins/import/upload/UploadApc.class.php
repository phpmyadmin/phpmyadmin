<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides upload functionalities for the import plugins
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/UploadInterface.int.php';

/**
 * Implementation for the APC extension
 *
 * @package PhpMyAdmin
 */
class UploadApc implements UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey()
    {
        return 'APC_UPLOAD_PROGRESS';
    }

    /**
     * Returns upload status.
     *
     * This is implementation for APC extension.
     *
     * @param string $id upload id
     *
     * @return array|null
     */
    public static function getUploadStatus($id)
    {
        global $SESSION_KEY;

        if (trim($id) == "") {
            return null;
        }
        if (! array_key_exists($id, $_SESSION[$SESSION_KEY])) {
            $_SESSION[$SESSION_KEY][$id] = array(
                'id'       => $id,
                'finished' => false,
                'percent'  => 0,
                'total'    => 0,
                'complete' => 0,
                'plugin'   => UploadApc::getIdKey()
             );
        }
        $ret = $_SESSION[$SESSION_KEY][$id];

        if (! PMA_import_apcCheck() || $ret['finished']) {
            return $ret;
        }
        $status = apc_fetch('upload_' . $id);

        if ($status) {
            $ret['finished'] = (bool)$status['done'];
            $ret['total']    = $status['total'];
            $ret['complete'] = $status['current'];

            if ($ret['total'] > 0) {
                $ret['percent'] = $ret['complete'] / $ret['total'] * 100;
            }

            if ($ret['percent'] == 100) {
                $ret['finished'] = (bool)true;
            }

            $_SESSION[$SESSION_KEY][$id] = $ret;
        }

        return $ret;
    }
}
?>