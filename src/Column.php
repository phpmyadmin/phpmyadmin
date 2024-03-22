<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final class Column
{
    public function __construct(
        public readonly string $field,
        public readonly string $type,
        public readonly bool $isNull,
        public readonly string $key,
        public readonly string|null $default,
        public readonly string $extra,
    ) {
    }
}
