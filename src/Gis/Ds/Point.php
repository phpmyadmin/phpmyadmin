<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis\Ds;

use function max;
use function min;

final class Point
{
    public function __construct(public readonly float $x, public readonly float $y)
    {
    }

    /**
     * Determines whether a given point is inside a given polygon.
     */
    public function isInsidePolygon(Polygon $polygon): bool
    {
        $noOfPoints = $polygon->count();

        // If first point is repeated at the end remove it
        if ($polygon->top() == $polygon->bottom()) {
            --$noOfPoints;
        }

        $counter = 0;

        // Use ray casting algorithm
        $p1 = $polygon->bottom();
        for ($i = 1; $i <= $noOfPoints; $i++) {
            $p2 = $polygon[$i % $noOfPoints];
            if ($this->y <= min($p1->y, $p2->y)) {
                $p1 = $p2;
                continue;
            }

            if ($this->y > max($p1->y, $p2->y)) {
                $p1 = $p2;
                continue;
            }

            if ($this->x > max($p1->x, $p2->x)) {
                $p1 = $p2;
                continue;
            }

            if ($p1->y != $p2->y) {
                $xinters = ($this->y - $p1->y)
                    * ($p2->x - $p1->x)
                    / ($p2->y - $p1->y) + $p1->x;
                if ($p1->x == $p2->x || $this->x <= $xinters) {
                    $counter++;
                }
            }

            $p1 = $p2;
        }

        return $counter % 2 !== 0;
    }
}
