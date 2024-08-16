<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

final readonly class TrackedData
{
    /**
     * @psalm-param list<array{date: string, username: string, statement: string}> $ddlog
     * @psalm-param list<array{date: string, username: string, statement: string}> $dmlog
     */
    public function __construct(
        public string $dateFrom,
        public string $dateTo,
        public array $ddlog,
        public array $dmlog,
        public string $tracking,
        public string $schemaSnapshot,
    ) {
    }
}
