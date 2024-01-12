<?php
/**
 * MIME detection code.
 *
 * @todo Maybe we could try to use fileinfo module if loaded
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function chr;
use function mb_strlen;
use function str_starts_with;

/**
 * Handles mime type detection
 */
class Mime
{
    /**
     * Tries to detect MIME type of content.
     *
     * @param string $test First few bytes of content to use for detection
     */
    public static function detect(string $test): string
    {
        $len = mb_strlen($test);
        if ($len >= 2 && $test[0] === chr(0xff) && $test[1] === chr(0xd8)) {
            return 'image/jpeg';
        }

        if ($len >= 3 && str_starts_with($test, 'GIF')) {
            return 'image/gif';
        }

        if ($len >= 4 && str_starts_with($test, "\x89PNG")) {
            return 'image/png';
        }

        return 'application/octet-stream';
    }
}
