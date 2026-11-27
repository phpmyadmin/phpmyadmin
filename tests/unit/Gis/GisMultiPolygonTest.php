<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\GisMultiPolygon;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[CoversClass(GisMultiPolygon::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class GisMultiPolygonTest extends AbstractTestCase
{
    private const DATA = [
        'MULTIPOLYGON' => [
            'data_length' => 2,
            0 => [
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
            1 => [
                'data_length' => 1,
                0 => [
                    'data_length' => 4,
                    0 => ['x' => 123, 'y' => 0],
                    1 => ['x' => 23, 'y' => 30],
                    2 => ['x' => 17, 'y' => 63],
                    3 => ['x' => 123, 'y' => 0],
                ],
            ],
        ],
    ];

    /**
     * Test for generateWkt
     *
     * @param array<mixed> $gisData
     * @param int          $index   index in $gis_data
     * @param string       $empty   empty parameter
     * @param string       $output  expected output
     */
    #[DataProvider('providerForTestGenerateWkt')]
    public function testGenerateWkt(array $gisData, int $index, string $empty, string $output): void
    {
        $object = new GisMultiPolygon();
        self::assertSame($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [self::DATA];

        $temp1 = $temp;
        $temp1[0]['MULTIPOLYGON']['data_length'] = 0;

        $temp2 = $temp;
        $temp2[0]['MULTIPOLYGON'][1]['data_length'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTIPOLYGON'][1][0]['data_length'] = 3;

        return [
            [
                $temp,
                0,
                '',
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
            ],
            // at lease one polygon should be there
            [$temp1, 0, '', 'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))'],
            // a polygon should have at least one ring
            [
                $temp2,
                0,
                '',
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
            ],
            // a ring should have at least four points
            [
                $temp3,
                0,
                '0',
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
            ],
        ];
    }

    /**
     * test generateParams method
     *
     * @param string       $wkt    point in WKT form
     * @param array<mixed> $params expected output array
     */
    #[DataProvider('providerForTestGenerateParams')]
    public function testGenerateParams(string $wkt, array $params): void
    {
        $object = new GisMultiPolygon();
        self::assertEquals($params, $object->generateParams($wkt));
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array<array{string, array<mixed>}>
     */
    public static function providerForTestGenerateParams(): array
    {
        return [
            [
                "'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),"
                . "(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',124",
                ['srid' => 124, 0 => self::DATA],
            ],
        ];
    }

    /**
     * test getShape method
     *
     * @param array<string, array<int, array<string, array<int, int[]>>>> $rowData array of GIS data
     * @param string                                                      $shape   expected shape in WKT
     */
    #[DataProvider('providerForTestGetShape')]
    public function testGetShape(array $rowData, string $shape): void
    {
        $object = new GisMultiPolygon();
        self::assertSame($shape, $object->getShape($rowData));
    }

    /**
     * data provider for testGetShape
     *
     * @return array<array{array<string, array<int, array<string, array<int, int[]>>>>, string}>
     */
    public static function providerForTestGetShape(): array
    {
        return [
            [
                [
                    'parts' => [
                        [
                            'points' => [
                                ['x' => 10, 'y' => 10],
                                ['x' => 10, 'y' => 40],
                                ['x' => 50, 'y' => 40],
                                ['x' => 50, 'y' => 10],
                                ['x' => 10, 'y' => 10],
                            ],
                        ],
                        [
                            'points' => [
                                ['x' => 60, 'y' => 40],
                                ['x' => 75, 'y' => 65],
                                ['x' => 90, 'y' => 40],
                                ['x' => 60, 'y' => 40],
                            ],
                        ],
                        [
                            'points' => [
                                ['x' => 20, 'y' => 20],
                                ['x' => 40, 'y' => 20],
                                ['x' => 25, 'y' => 30],
                                ['x' => 20, 'y' => 20],
                            ],
                        ],
                    ],
                ],
                'MULTIPOLYGON(((10 10,10 40,50 40,50 10,10 10),(20 20,40 20,25 30'
                    . ',20 20)),((60 40,75 65,90 40,60 40)))',
            ],
        ];
    }

    /**
     * test getExtent method
     *
     * @param string $spatial spatial data of a row
     * @param Extent $extent  expected results
     */
    #[DataProvider('providerForTestGetExtent')]
    public function testGetExtent(string $spatial, Extent $extent): void
    {
        $object = new GisMultiPolygon();
        self::assertEquals($extent, $object->getExtent($spatial));
    }

    /**
     * data provider for testGetExtent
     *
     * @return array<array{string, Extent}>
     */
    public static function providerForTestGetExtent(): array
    {
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),((105 0,56 20,78 73,105 0)))',
                new Extent(minX: 16, minY: 0, maxX: 147, maxY: 83),
            ],
            [
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20'
                    . ',20 30)),((105 0,56 20,78 73,105 0)))',
                new Extent(minX: 10, minY: 0, maxX: 105, maxY: 73),
            ],
        ];
    }
}
