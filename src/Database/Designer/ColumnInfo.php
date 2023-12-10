<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database\Designer;

final class ColumnInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
    ) {
    }
}
