<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis\Ds;

final readonly class ScaleData
{
    public function __construct(
        public float $scale,
        public float $offsetX,
        public float $offsetY,
        public int $height,
    ) {
    }
}
