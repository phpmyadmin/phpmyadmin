<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

use function mb_strlen;
use function mb_strpos;

final class DecimalSize
{
    private function __construct(public readonly int $precision, public readonly int $scale)
    {
    }

    public static function fromCell(string $cell): self
    {
        $precision = mb_strlen($cell) - 1;

        return new self(
            $precision,
            $precision - (int) mb_strpos($cell, '.'),
        );
    }
}
