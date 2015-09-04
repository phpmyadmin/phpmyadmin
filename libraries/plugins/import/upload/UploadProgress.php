<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides upload functionalities for the import plugins
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\plugins\import\upload;

use PMA\libraries\plugins\UploadInterface;

/**
 * Implementation for upload progress
 *
 * @package PhpMyAdmin
 */
class UploadProgress implements UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey()
    {
        return 'UPLOAD_IDENTIFIER';
    }

    /**
     * Returns upload status.
     *
     * This is implementation for upload progress
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

        if (!array_key_exists($id, $_SESSION[$SESSION_KEY])) {
            $_SESSION[$SESSION_KEY][$id] = array(
                'id'       => $id,
                'finished' => false,
                'percent'  => 0,
                'total'    => 0,
                'complete' => 0,
                'plugin'   => UploadProgress::getIdKey(),
            );
        }
        $ret = $_SESSION[$SESSION_KEY][$id];

        if (!PMA_Import_progressCheck() || $ret['finished']) {
            return $ret;
        }

        $status = uploadprogress_get_info($id);

        if ($status) {
            if ($status['bytes_uploaded'] == $status['bytes_total']) {
                $ret['finished'] = true;
            } else {
                $ret['finished'] = false;
            }
            $ret['total'] = $status['bytes_total'];
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
                'plugin'   => UploadProgress::getIdKey(),
            );
        }

        $_SESSION[$SESSION_KEY][$id] = $ret;

        return $ret;
    }
}
