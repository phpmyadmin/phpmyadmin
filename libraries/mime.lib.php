<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MIME detection code.
 *
 * @package PhpMyAdmin
 * @todo Maybe we could try to use fileinfo module if loaded
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Tries to detect MIME type of content.
 *
 * @param string &$test First few bytes of content to use for detection
 *
 * @return string
 */
function PMA_detectMIME(&$test)
{
    /** @var PMA_String $pmaString */
    $pmaString = $GLOBALS['PMA_String'];
    $len = $pmaString->strlen($test);
    if ($len >= 2 && $test[0] == $pmaString->chr(0xff)
        && $test[1] == $pmaString->chr(0xd8)
    ) {
        return 'image/jpeg';
    }
    if ($len >= 3 && $pmaString->substr($test, 0, 3) == 'GIF') {
        return 'image/gif';
    }
    if ($len >= 4 && $pmaString->substr($test, 0, 4) == "\x89PNG") {
        return 'image/png';
    }
    return 'application/octet-stream';
}
?>
