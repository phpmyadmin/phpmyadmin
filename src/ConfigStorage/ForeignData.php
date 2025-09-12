<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

class ForeignData
{
    /** @param list<array<array-key, string|null>>|null $dispRow */
    public function __construct(
        public bool $foreignLink,
        public int $theTotal,
        public string $foreignDisplay,
        public array|null $dispRow,
        public string $foreignField,
    ) {
    }
}
