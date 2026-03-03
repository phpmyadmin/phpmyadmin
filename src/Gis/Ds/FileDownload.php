<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis\Ds;

final readonly class FileDownload
{
    public function __construct(
        public string $blob,
        public string $mime,
        public string $extension,
    ) {
    }
}
