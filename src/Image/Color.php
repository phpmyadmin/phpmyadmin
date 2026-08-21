<?php

declare(strict_types=1);

namespace PhpMyAdmin\Image;

final readonly class Color
{
    /**
     * @param int<0, 255> $red
     * @param int<0, 255> $green
     * @param int<0, 255> $blue
     */
    public function __construct(public int $red, public int $green, public int $blue)
    {
    }

    public static function black(): self
    {
        return new self(0, 0, 0);
    }

    /** @return array{int<0, 255>, int<0, 255>, int<0, 255>} */
    public function toArray(): array
    {
        return [$this->red, $this->green, $this->blue];
    }
}
