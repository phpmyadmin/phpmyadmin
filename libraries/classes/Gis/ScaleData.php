<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use function max;
use function min;

class ScaleData
{
    public function __construct(
        public readonly float $maxX,
        public readonly float $minX,
        public readonly float $maxY,
        public readonly float $minY,
    ) {
    }

    public function expand(float $x, float $y): ScaleData
    {
        return new ScaleData(
            max($this->maxX, $x),
            min($this->minX, $x),
            max($this->maxY, $y),
            min($this->minY, $y),
        );
    }

    public function merge(ScaleData $scaleData): ScaleData
    {
        return new ScaleData(
            max($this->maxX, $scaleData->maxX),
            min($this->minX, $scaleData->minX),
            max($this->maxY, $scaleData->maxY),
            min($this->minY, $scaleData->minY),
        );
    }
}
