<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

use PhpMyAdmin\SqlParser\Utils\ForeignKey;

final class Foreigners
{
    /**
     * @param array<array<string|null>> $data
     * @param list<ForeignKey>          $keysData
     */
    public function __construct(
        public readonly array $data = [],
        public readonly array $keysData = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->data === [] && $this->keysData === [];
    }
}
