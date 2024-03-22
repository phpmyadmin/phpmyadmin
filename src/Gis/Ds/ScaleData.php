<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis\Ds;

class ScaleData
{
    public function __construct(
        public readonly float $scale,
        public readonly float $offsetX,
        public readonly float $offsetY,
        public readonly int $height,
    ) {
    }
}
