<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

use Stringable;

use function mb_strlen;
use function mb_strpos;

final readonly class DecimalSize implements Stringable
{
    private function __construct(public int $precision, public int $scale)
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

    public static function fromPrecisionAndScale(int $precision, int $scale): self
    {
        return new self($precision, $scale);
    }

    public function __toString(): string
    {
        return $this->precision . ',' . $this->scale;
    }
}
