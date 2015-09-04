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
 * Implementation for session
 *
 * @package PhpMyAdmin
 */
class UploadSession implements UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey()
    {
        return ini_get('session.upload_progress.name');
    }

    /**
     * Returns upload status.
     *
     * This is implementation for session.upload_progress in PHP 5.4+.
     *
     * @param string $id upload id
     *
     * @return array|null
     */
    public static function getUploadStatus($id)
    {
        global $SESSION_KEY;

        if (trim($id) == '') {
            return null;
        }

        if (!array_key_exists($id, $_SESSION[$SESSION_KEY])) {
            $_SESSION[$SESSION_KEY][$id] = array(
                'id'       => $id,
                'finished' => false,
                'percent'  => 0,
                'total'    => 0,
                'complete' => 0,
                'plugin'   => UploadSession::getIdKey(),
            );
        }
        $ret = $_SESSION[$SESSION_KEY][$id];

        if (!PMA_Import_sessionCheck() || $ret['finished']) {
            return $ret;
        }

        $status = false;
        $sessionkey = ini_get('session.upload_progress.prefix') . $id;

        if (isset($_SESSION[$sessionkey])) {
            $status = $_SESSION[$sessionkey];
        }

        if ($status) {
            $ret['finished'] = $status['done'];
            $ret['total'] = $status['content_length'];
            $ret['complete'] = $status['bytes_processed'];

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
                'plugin'   => UploadSession::getIdKey(),
            );
        }

        $_SESSION[$SESSION_KEY][$id] = $ret;

        return $ret;
    }
}
