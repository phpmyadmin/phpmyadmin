<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis\Ds;

use function max;
use function min;

use const INF;

class Extent
{
    public static function empty(): Extent
    {
        return new Extent(minX: +INF, minY: +INF, maxX: -INF, maxY: -INF);
    }

    public function __construct(
        public float $minX,
        public float $minY,
        public float $maxX,
        public float $maxY,
    ) {
    }

    public function extend(float $x, float $y): void
    {
        $this->minX = min($this->minX, $x);
        $this->minY = min($this->minY, $y);
        $this->maxX = max($this->maxX, $x);
        $this->maxY = max($this->maxY, $y);
    }

    public function merge(Extent $extent): void
    {
        $this->minX = min($this->minX, $extent->minX);
        $this->minY = min($this->minY, $extent->minY);
        $this->maxX = max($this->maxX, $extent->maxX);
        $this->maxY = max($this->maxY, $extent->maxY);
    }

    public function isEmpty(): bool
    {
        return $this->minX > $this->maxX || $this->minY > $this->maxY;
    }
}
