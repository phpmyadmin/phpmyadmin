<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MIME detection code.
 *
 * @package PhpMyAdmin
 * @todo Maybe we could try to use fileinfo module if loaded
 */
namespace PhpMyAdmin;

/**
 * PhpMyAdmin\Mime class;
 *
 * @package PhpMyAdmin
 */
class Mime
{
    /**
     * Tries to detect MIME type of content.
     *
     * @param string &$test First few bytes of content to use for detection
     *
     * @return string
     */
    public static function detect(&$test)
    {
        $len = mb_strlen($test);
        if ($len >= 2 && $test[0] == chr(0xff) && $test[1] == chr(0xd8)) {
            return 'image/jpeg';
        }
        if ($len >= 3 && substr($test, 0, 3) == 'GIF') {
            return 'image/gif';
        }
        if ($len >= 4 && mb_substr($test, 0, 4) == "\x89PNG") {
            return 'image/png';
        }
        return 'application/octet-stream';
    }
}
