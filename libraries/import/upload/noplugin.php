<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
*
* @package PhpMyAdmin
*/

if (! defined('PHPMYADMIN')) {
    exit;
}

$ID_KEY      = 'noplugin';

/**
 * Returns upload status.
 *
 * This is implementation when no webserver support exists, so it returns just zeroes.
 *
 * @param string $id
 * @return array|null
 */
function PMA_getUploadStatus($id)
{
    global $SESSION_KEY;
    global $ID_KEY;

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
                    'plugin'   => $ID_KEY
        );
    }
    $ret = $_SESSION[$SESSION_KEY][$id];

    return $ret;
}
?>
