<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis\Ds;

use PhpMyAdmin\Gis\Ds\Point;
use PhpMyAdmin\Gis\Ds\Polygon;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Polygon::class)]
#[CoversClass(Point::class)]
class PolygonTest extends AbstractTestCase
{
    /**
     * Provide some common data to data providers
     *
     * @return mixed[][]
     */
    private static function getData(): array
    {
        return [
            'POLYGON' => [
                'data_length' => 2,
                0 => [
                    'data_length' => 5,
                    0 => ['x' => 35, 'y' => 10],
                    1 => ['x' => 10, 'y' => 20],
                    2 => ['x' => 15, 'y' => 40],
                    3 => ['x' => 45, 'y' => 45],
                    4 => ['x' => 35, 'y' => 10],
                ],
                1 => [
                    'data_length' => 4,
                    0 => ['x' => 20, 'y' => 30],
                    1 => ['x' => 35, 'y' => 32],
                    2 => ['x' => 30, 'y' => 20],
                    3 => ['x' => 20, 'y' => 30],
                ],
            ],
        ];
    }

    /**
     * test for Area
     */
    #[DataProvider('providerForTestArea')]
    public function testArea(Polygon $ring, float $area): void
    {
        self::assertSame($area, $ring->area());
    }

    /**
     * data provider for testArea
     *
     * @return list<array{Polygon, float}>
     */
    public static function providerForTestArea(): array
    {
        return [
            [
                Polygon::fromXYArray([
                    ['x' => 35, 'y' => 10],
                    ['x' => 10, 'y' => 10],
                    ['x' => 15, 'y' => 40],
                ]),
                -375.00,
            ],
            // first point of the ring repeated as the last point
            [
                Polygon::fromXYArray([
                    ['x' => 35, 'y' => 10],
                    ['x' => 10, 'y' => 10],
                    ['x' => 15, 'y' => 40],
                    ['x' => 35, 'y' => 10],
                ]),
                -375.00,
            ],
            // anticlockwise gives positive area
            [
                Polygon::fromXYArray([
                    ['x' => 15, 'y' => 40],
                    ['x' => 10, 'y' => 10],
                    ['x' => 35, 'y' => 10],
                ]),
                375.00,
            ],
        ];
    }

    /**
     * test for isPointInsidePolygon
     */
    #[DataProvider('providerForTestIsPointInsidePolygon')]
    public function testIsPointInsidePolygon(Point $point, Polygon $polygon, bool $isInside): void
    {
        self::assertSame($isInside, $point->isInsidePolygon($polygon));
    }

    /**
     * data provider for testIsPointInsidePolygon
     *
     * @return array<array{Point, Polygon, bool}>
     */
    public static function providerForTestIsPointInsidePolygon(): array
    {
        $ring = Polygon::fromXYArray([
            ['x' => 35, 'y' => 10],
            ['x' => 10, 'y' => 10],
            ['x' => 15, 'y' => 40],
            ['x' => 35, 'y' => 10],
        ]);

        return [
            // point inside the ring
            [new Point(20, 15), $ring, true],
            // point on an edge of the ring
            [new Point(20, 10), $ring, false],
            // point on a vertex of the ring
            [new Point(10, 10), $ring, false],
            // point outside the ring
            [new Point(5, 10), $ring, false],
        ];
    }

    /**
     * test for getPointOnSurface
     *
     * @param Polygon $ring array of points forming the ring
     */
    #[DataProvider('providerForTestGetPointOnSurface')]
    public function testGetPointOnSurface(Polygon $ring): void
    {
        $point = $ring->getPointOnSurface();
        self::assertInstanceOf(Point::class, $point);
        self::assertTrue($point->isInsidePolygon($ring));
    }

    /**
     * data provider for testGetPointOnSurface
     *
     * @return list{list{mixed}, list{mixed}}
     */
    public static function providerForTestGetPointOnSurface(): array
    {
        $temp = self::getData();
        unset($temp['POLYGON'][0]['data_length']);
        unset($temp['POLYGON'][1]['data_length']);

        return [[Polygon::fromXYArray($temp['POLYGON'][0])], [Polygon::fromXYArray($temp['POLYGON'][1])]];
    }

    /**
     * test case for isOuterRing() method
     *
     * @param Polygon $ring coordinates of the points in a ring
     */
    #[DataProvider('providerForIsOuterRing')]
    public function testIsOuterRing(Polygon $ring): void
    {
        self::assertTrue($ring->isOuterRing());
    }

    /**
     * data provider for testIsOuterRing() test case
     *
     * @return array<array{Polygon}>
     */
    public static function providerForIsOuterRing(): array
    {
        return [
            [
                Polygon::fromXYArray([
                    ['x' => 0, 'y' => 0],
                    ['x' => 0, 'y' => 1],
                    ['x' => 1, 'y' => 1],
                    ['x' => 1, 'y' => 0],
                ]),
            ],
        ];
    }
}
