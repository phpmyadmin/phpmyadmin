<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database\Designer;

final readonly class ColumnInfo
{
    public function __construct(
        public string $name,
        public string $type,
    ) {
    }
}
