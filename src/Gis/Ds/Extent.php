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
        public readonly float $minX,
        public readonly float $minY,
        public readonly float $maxX,
        public readonly float $maxY,
    ) {
    }

    public function merge(Extent $extent): Extent
    {
        return new Extent(
            minX: min($this->minX, $extent->minX),
            minY: min($this->minY, $extent->minY),
            maxX: max($this->maxX, $extent->maxX),
            maxY: max($this->maxY, $extent->maxY),
        );
    }

    public function isEmpty(): bool
    {
        return $this->minX > $this->maxX || $this->minY > $this->maxY;
    }
}
