<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use function ini_get;
use function ini_parse_quantity;
use function min;

class IniReader
{
    protected function get(string $option): string
    {
        return (string) ini_get($option);
    }

    private static function toBytes(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        // Using error suppression operator because ini_parse_quantity() can throw a warning if the value is invalid
        return @ini_parse_quantity($value);
    }

    public function getMemoryLimitInBytes(): int
    {
        return self::toBytes($this->get('memory_limit'));
    }

    /**
     * Maximum upload size as limited by PHP
     * Used with permission from Moodle (https://moodle.org/) by Martin Dougiamas
     */
    public function getUploadSizeInBytes(): int
    {
        $uploadMaxFilesize = $this->get('upload_max_filesize');
        $size = $uploadMaxFilesize === '' ? 5 * 1024 * 1024 : self::toBytes($uploadMaxFilesize);
        $postSize = self::toBytes($this->get('post_max_size'));

        return $postSize > 0 ? min($size, $postSize) : $size;
    }
}
