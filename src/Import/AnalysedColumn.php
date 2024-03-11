<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

final class AnalysedColumn
{
    public function __construct(
        public ColumnType $type,
        public int|DecimalSize $size,
        public bool $isFullyFormattedSql = false,
    ) {
    }
}
