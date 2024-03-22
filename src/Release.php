<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final class Release
{
    public function __construct(
        public readonly string $version,
        public readonly string $date,
        public readonly string $phpVersions,
        public readonly string $mysqlVersions,
    ) {
    }
}
