<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

use function max;
use function min;

class ScaleData
{
    /** @var float */
    public $maxX;

    /** @var float */
    public $minX;

    /** @var float */
    public $maxY;

    /** @var float */
    public $minY;

    public function __construct(float $maxX, float $minX, float $maxY, float $minY)
    {
        $this->maxX = $maxX;
        $this->minX = $minX;
        $this->maxY = $maxY;
        $this->minY = $minY;
    }

    public function expand(float $x, float $y): ScaleData
    {
        return new ScaleData(
            max($this->maxX, $x),
            min($this->minX, $x),
            max($this->maxY, $y),
            min($this->minY, $y)
        );
    }

    public function merge(ScaleData $scaleData): ScaleData
    {
        return new ScaleData(
            max($this->maxX, $scaleData->maxX),
            min($this->minX, $scaleData->minX),
            max($this->maxY, $scaleData->maxY),
            min($this->minY, $scaleData->minY)
        );
    }
}
