<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

final readonly class Routine
{
    public function __construct(
        public string $name,
        public string $type,
        public string $returns,
        public string $definer,
    ) {
    }
}
