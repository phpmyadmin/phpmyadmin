<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

final class Routine
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $returns,
    ) {
    }
}
