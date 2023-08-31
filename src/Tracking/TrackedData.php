<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

final class TrackedData
{
    /**
     * @psalm-param list<array{date: string, username: string, statement: string}> $ddlog
     * @psalm-param list<array{date: string, username: string, statement: string}> $dmlog
     */
    public function __construct(
        public readonly string $dateFrom,
        public readonly string $dateTo,
        public readonly array $ddlog,
        public readonly array $dmlog,
        public readonly string $tracking,
        public readonly string $schemaSnapshot,
    ) {
    }
}
