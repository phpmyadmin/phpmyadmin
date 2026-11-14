<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use PhpMyAdmin\Util;

use function ini_get;
use function min;

class IniReader
{
    public function get(string $option): string|false
    {
        return ini_get($option);
    }

    /**
     * Maximum upload size as limited by PHP
     * Used with permission from Moodle (https://moodle.org/) by Martin Dougiamas
     */
    public function getUploadSizeInBytes(): int
    {
        $fileSize = $this->get('upload_max_filesize');
        if ($fileSize === '' || $fileSize === false) {
            $fileSize = '5M';
        }

        $size = Util::getRealSize($fileSize);
        $postSize = $this->get('post_max_size');
        if ($postSize !== '' && $postSize !== false) {
            $size = min($size, Util::getRealSize($postSize));
        }

        return $size;
    }
}
