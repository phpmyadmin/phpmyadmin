<?php

declare(strict_types=1);

namespace PhpMyAdmin;

class SystemColumn
{
    public function __construct(
        public readonly string $tableName,
        public readonly string $referringColumn,
        public readonly string|null $realColumn,
    ) {
    }
}
