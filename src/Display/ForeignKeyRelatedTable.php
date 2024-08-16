<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

final readonly class ForeignKeyRelatedTable
{
    public function __construct(
        public string $table,
        public string $field,
        public string $displayField,
        public string $database,
    ) {
    }
}
