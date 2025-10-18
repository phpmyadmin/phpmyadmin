<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis;

final readonly class GisVisualizationSettings
{
    /**
     * @psalm-param positive-int $width
     * @psalm-param positive-int $height
     */
    public function __construct(
        public int $width,
        public int $height,
        public string $spatialColumn,
        public string $labelColumn = '',
    ) {
    }
}
