<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

final readonly class SortExpression
{
    /** @param 'ASC'|'DESC' $direction */
    public function __construct(
        public string|null $tableName,
        public string|null $columnName,
        public string $direction,
        public string $expression = '',
    ) {
    }
}
