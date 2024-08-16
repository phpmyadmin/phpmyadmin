<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final readonly class Release
{
    public function __construct(
        public string $version,
        public string $date,
        public string $phpVersions,
        public string $mysqlVersions,
    ) {
    }
}
