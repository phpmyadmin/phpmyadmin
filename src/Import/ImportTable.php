<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

final class ImportTable
{
    /**
     * @param list<string>      $columns
     * @param list<list<mixed>> $rows
     */
    public function __construct(
        public string $tableName,
        public array $columns = [],
        public array $rows = [],
    ) {
    }
}
