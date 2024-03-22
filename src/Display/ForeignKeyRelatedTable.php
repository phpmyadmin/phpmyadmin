<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

final class ForeignKeyRelatedTable
{
    public function __construct(
        public readonly string $table,
        public readonly string $field,
        public readonly string $displayField,
        public readonly string $database,
    ) {
    }
}
