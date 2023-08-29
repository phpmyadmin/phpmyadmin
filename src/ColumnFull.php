<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final class ColumnFull
{
    public function __construct(
        public readonly string $field,
        public readonly string $type,
        public readonly string|null $collation,
        public readonly bool $isNull,
        public readonly string $key,
        public readonly string|null $default,
        public readonly string $extra,
        public readonly string $privileges,
        public readonly string $comment,
    ) {
    }
}
