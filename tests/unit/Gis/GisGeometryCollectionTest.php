<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\GisGeometryCollection;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[CoversClass(GisGeometryCollection::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class GisGeometryCollectionTest extends AbstractTestCase
{
    /**
     * Data provider for testGetExtent() test case
     *
     * @return array<array{string, Extent}>
     */
    public static function providerForTestGetExtent(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                new Extent(minX: 10, minY: 10, maxX: 45, maxY: 45),
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
        $object = new GisGeometryCollection();
        self::assertEquals($extent, $object->getExtent($spatial));
    }

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
        $object = new GisGeometryCollection();
        self::assertSame($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * Data provider for testGenerateWkt() test case
     *
     * @return array<array{array<mixed>, int, string, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp1 = [
            [
                'gis_type' => 'LINESTRING',
                'LINESTRING' => ['data_length' => 2, 0 => ['x' => 5.02, 'y' => 8.45], 1 => ['x' => 6.14, 'y' => 0.15]],
            ],
        ];

        return [
            [
                [
                    'gis_type' => 'GEOMETRYCOLLECTION',
                    'srid' => '0',
                    'GEOMETRYCOLLECTION' => ['data_length' => '1'],
                    0 => ['gis_type' => 'POINT'],
                ],
                0,
                '',
                'GEOMETRYCOLLECTION(POINT( ))',
            ],
            [
                [
                    'gis_type' => 'GEOMETRYCOLLECTION',
                    'srid' => '0',
                    'GEOMETRYCOLLECTION' => ['data_length' => '1'],
                    0 => ['gis_type' => 'LINESTRING'],
                ],
                0,
                '',
                'GEOMETRYCOLLECTION(LINESTRING( , ))',
            ],
            [
                [
                    'gis_type' => 'GEOMETRYCOLLECTION',
                    'srid' => '0',
                    'GEOMETRYCOLLECTION' => ['data_length' => '1'],
                    0 => ['gis_type' => 'POLYGON'],
                ],
                0,
                '',
                'GEOMETRYCOLLECTION(POLYGON(( , , , )))',
            ],
            [
                [
                    'gis_type' => 'GEOMETRYCOLLECTION',
                    'srid' => '0',
                    'GEOMETRYCOLLECTION' => ['data_length' => '1'],
                    0 => ['gis_type' => 'MULTIPOINT'],
                ],
                0,
                '',
                'GEOMETRYCOLLECTION(MULTIPOINT( ))',
            ],
            [
                [
                    'gis_type' => 'GEOMETRYCOLLECTION',
                    'srid' => '0',
                    'GEOMETRYCOLLECTION' => ['data_length' => '1'],
                    0 => ['gis_type' => 'MULTILINESTRING'],
                ],
                0,
                '',
                'GEOMETRYCOLLECTION(MULTILINESTRING(( , )))',
            ],
            [
                [
                    'gis_type' => 'GEOMETRYCOLLECTION',
                    'srid' => '0',
                    'GEOMETRYCOLLECTION' => ['data_length' => '1'],
                    0 => ['gis_type' => 'MULTIPOLYGON'],
                ],
                0,
                '',
                'GEOMETRYCOLLECTION(MULTIPOLYGON((( , , , ))))',
            ],
            [$temp1, 0, '', 'GEOMETRYCOLLECTION(LINESTRING(5.02 8.45,6.14 0.15))'],
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
        $object = new GisGeometryCollection();
        self::assertSame($params, $object->generateParams($wkt));
    }

    /**
     * Data provider for testGenerateParams() test case
     *
     * @return array<array{string, array<mixed>}>
     */
    public static function providerForTestGenerateParams(): array
    {
        return [
            [
                'GEOMETRYCOLLECTION('
                . 'LINESTRING(5.02 8.45,6.14 0.15)'
                . ',MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))'
                . ',MULTIPOINT(5.02 8.45,6.14 0.15)'
                . ',MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))'
                . ',POINT(5.02 8.45)'
                . ',POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))'
                . ')',
                [
                    'srid' => 0,
                    'GEOMETRYCOLLECTION' => ['data_length' => 6],
                    0 => [
                        'gis_type' => 'LINESTRING',
                        'LINESTRING' => [
                            'data_length' => 2,
                            0 => ['x' => 5.02, 'y' => 8.45],
                            1 => ['x' => 6.14, 'y' => 0.15],
                        ],
                    ],
                    1 => [
                        'gis_type' => 'MULTILINESTRING',
                        'MULTILINESTRING' => [
                            'data_length' => 2,
                            0 => [
                                'data_length' => 3,
                                0 => ['x' => 36.0, 'y' => 14.0],
                                1 => ['x' => 47.0, 'y' => 23.0],
                                2 => ['x' => 62.0, 'y' => 75.0],
                            ],
                            1 => [
                                'data_length' => 3,
                                0 => ['x' => 36.0, 'y' => 10.0],
                                1 => ['x' => 17.0, 'y' => 23.0],
                                2 => ['x' => 178.0, 'y' => 53.0],
                            ],
                        ],
                    ],
                    2 => [
                        'gis_type' => 'MULTIPOINT',
                        'MULTIPOINT' => [
                            'data_length' => 2,
                            0 => ['x' => 5.02, 'y' => 8.45],
                            1 => ['x' => 6.14, 'y' => 0.15],
                        ],
                    ],
                    3 => [
                        'gis_type' => 'MULTIPOLYGON',
                        'MULTIPOLYGON' => [
                            'data_length' => 2,
                            0 => [
                                'data_length' => 2,
                                0 => [
                                    'data_length' => 5,
                                    0 => ['x' => 35.0, 'y' => 10.0],
                                    1 => ['x' => 10.0, 'y' => 20.0],
                                    2 => ['x' => 15.0, 'y' => 40.0],
                                    3 => ['x' => 45.0, 'y' => 45.0],
                                    4 => ['x' => 35.0, 'y' => 10.0],
                                ],
                                1 => [
                                    'data_length' => 4,
                                    0 => ['x' => 20.0, 'y' => 30.0],
                                    1 => ['x' => 35.0, 'y' => 32.0],
                                    2 => ['x' => 30.0, 'y' => 20.0],
                                    3 => ['x' => 20.0, 'y' => 30.0],
                                ],
                            ],
                            1 => [
                                'data_length' => 1,
                                0 => [
                                    'data_length' => 4,
                                    0 => ['x' => 123.0, 'y' => 0.0],
                                    1 => ['x' => 23.0, 'y' => 30.0],
                                    2 => ['x' => 17.0, 'y' => 63.0],
                                    3 => ['x' => 123.0, 'y' => 0.0],
                                ],
                            ],
                        ],
                    ],
                    4 => ['gis_type' => 'POINT', 'POINT' => ['x' => 5.02, 'y' => 8.45]],
                    5 => [
                        'gis_type' => 'POLYGON',
                        'POLYGON' => [
                            'data_length' => 2,
                            0 => [
                                'data_length' => 5,
                                0 => ['x' => 35.0, 'y' => 10.0],
                                1 => ['x' => 10.0, 'y' => 20.0],
                                2 => ['x' => 15.0, 'y' => 40.0],
                                3 => ['x' => 45.0, 'y' => 45.0],
                                4 => ['x' => 35.0, 'y' => 10.0],
                            ],
                            1 => [
                                'data_length' => 4,
                                0 => ['x' => 20.0, 'y' => 30.0],
                                1 => ['x' => 35.0, 'y' => 32.0],
                                2 => ['x' => 30.0, 'y' => 20.0],
                                3 => ['x' => 20.0, 'y' => 30.0],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
