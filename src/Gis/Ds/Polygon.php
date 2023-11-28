<?php

declare(strict_types=1);

namespace PhpMyAdmin\Gis\Ds;

use SplDoublyLinkedList;

use function count;
use function sqrt;

/** @extends SplDoublyLinkedList<Point> */
final class Polygon extends SplDoublyLinkedList
{
    /** @param non-empty-list<array{x: float, y: float}> $points */
    public static function fromXYArray(array $points): self
    {
        $polygon = new self();
        foreach ($points as $pointXY) {
            $polygon[] = new Point($pointXY['x'], $pointXY['y']);
        }

        return $polygon;
    }

    /**
     * Calculates the area of a closed simple polygon.
     */
    public function area(): float
    {
        $noOfPoints = $this->count();

        // If the last point is same as the first point ignore it
        if ($this->top() == $this->bottom()) {
            --$noOfPoints;
        }

        //         _n-1
        // A = _1_ \    (X(i) * Y(i+1)) - (Y(i) * X(i+1))
        //      2  /__
        //         i=0
        $area = 0;
        for ($i = 0; $i < $noOfPoints; $i++) {
            $j = ($i + 1) % $noOfPoints;
            $area += $this[$i]->x * $this[$j]->y;
            $area -= $this[$i]->y * $this[$j]->x;
        }

        return $area / 2.0;
    }

    /**
     * Determines whether a set of points represents an outer ring.
     * If points are in clockwise orientation then, they form an outer ring.
     */
    public function isOuterRing(): bool
    {
        // If area is negative then it's in clockwise orientation,
        // i.e. it's an outer ring
        return $this->area() < 0;
    }

    /**
     * Returns a point that is guaranteed to be on the surface of the ring.
     * (for simple closed rings)
     *
     * @return Point|false a point on the surface of the ring
     */
    public function getPointOnSurface(): Point|false
    {
        $points = $this->findTwoConsecutiveDistinctPoints();

        if ($points === false) {
            return false;
        }

        $pointPrev = $points[0];
        $pointNext = $points[1];

        // Find the mid point
        $midPoint = new Point(($pointPrev->x + $pointNext->x) / 2, ($pointPrev->y + $pointNext->y) / 2);

        // Always keep $epsilon < 1 to go with the reduction logic down here
        $epsilon = 0.1;
        $denominator = sqrt(($pointNext->y - $pointPrev->y) ** 2 + ($pointPrev->x - $pointNext->x) ** 2);

        while (true) {
            // Get the points on either sides of the line
            // with a distance of epsilon to the mid point
            $x = $midPoint->x + ($epsilon * ($pointNext->y - $pointPrev->y)) / $denominator;
            $y = $midPoint->y + ($x - $midPoint->x) * ($pointPrev->x - $pointNext->x) / ($pointNext->y - $pointPrev->y);
            $pointA = new Point($x, $y);

            $x = $midPoint->x + ($epsilon * ($pointNext->y - $pointPrev->y)) / (0 - $denominator);
            $y = $midPoint->y + ($x - $midPoint->x) * ($pointPrev->x - $pointNext->x) / ($pointNext->y - $pointPrev->y);
            $pointB = new Point($x, $y);

            // One of the points should be inside the polygon,
            // unless epsilon chosen is too large
            if ($pointA->isInsidePolygon($this)) {
                return $pointA;
            }

            if ($pointB->isInsidePolygon($this)) {
                return $pointB;
            }

            //If both are outside the polygon reduce the epsilon and
            //recalculate the points(reduce exponentially for faster convergence)
            $epsilon **= 2;
            if ($epsilon == 0) {
                return false;
            }
        }
    }

    /** @return array{Point, Point}|false */
    private function findTwoConsecutiveDistinctPoints(): array|false
    {
        for ($i = 0, $nb = count($this) - 1; $i < $nb; $i++) {
            $pointPrev = $this->offsetGet($i);
            $pointNext = $this->offsetGet($i + 1);
            if ($pointPrev->y !== $pointNext->y) {
                return [$pointNext, $pointPrev];
            }
        }

        return false;
    }
}
