<?php

declare(strict_types=1);

namespace PhpMyAdmin;

final readonly class Column
{
    public function __construct(
        public string $field,
        public string $type,
        public bool $isNull,
        public string $key,
        public string|null $default,
        public string $extra,
    ) {
    }
}
