<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GisGeometryTest extends AbstractTestCase
{
    /** @var GisGeometry|MockObject */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = $this->getMockForAbstractClass(GisGeometry::class);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * tests setMinMax method
     *
     * @param string $point_set Point set
     * @param array  $min_max   Existing min, max values
     * @param array  $output    Expected output array
     *
     * @dataProvider providerForTestSetMinMax
     */
    public function testSetMinMax(string $point_set, array $min_max, array $output): void
    {
        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                GisGeometry::class,
                'setMinMax',
                [
                    $point_set,
                    $min_max,
                ]
            )
        );
    }

    /**
     * data provider for testSetMinMax
     *
     * @return array data for testSetMinMax
     */
    public function providerForTestSetMinMax(): array
    {
        return [
            [
                '12 35,48 75,69 23,25 45,14 53,35 78',
                [],
                [
                    'minX' => 12,
                    'maxX' => 69,
                    'minY' => 23,
                    'maxY' => 78,
                ],
            ],
            [
                '12 35,48 75,69 23,25 45,14 53,35 78',
                [
                    'minX' => 2,
                    'maxX' => 29,
                    'minY' => 23,
                    'maxY' => 128,
                ],
                [
                    'minX' => 2,
                    'maxX' => 69,
                    'minY' => 23,
                    'maxY' => 128,
                ],
            ],
        ];
    }

    /**
     * tests generateParams method
     *
     * @param string $value  Geometry data
     * @param array  $output Expected output
     *
     * @dataProvider providerForTestGenerateParams
     */
    public function testGenerateParams(string $value, array $output): void
    {
        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                GisGeometry::class,
                'generateParams',
                [$value]
            )
        );
    }

    /**
     * data provider for testGenerateParams
     *
     * @return array data for testGenerateParams
     */
    public function providerForTestGenerateParams(): array
    {
        return [
            [
                "'MULTIPOINT(125 50,156 25,178 43,175 80)',125",
                [
                    'srid' => '125',
                    'wkt'  => 'MULTIPOINT(125 50,156 25,178 43,175 80)',
                ],
            ],
            [
                'MULTIPOINT(125 50,156 25,178 43,175 80)',
                [
                    'srid' => '0',
                    'wkt'  => 'MULTIPOINT(125 50,156 25,178 43,175 80)',
                ],
            ],
            [
                'foo',
                [
                    'srid' => '0',
                    'wkt'  => '',
                ],
            ],
        ];
    }

    /**
     * tests extractPoints method
     *
     * @param string     $point_set  String of comma separated points
     * @param array|null $scale_data Data related to scaling
     * @param bool       $linear     If true, as a 1D array, else as a 2D array
     * @param array      $output     Expected output
     *
     * @dataProvider providerForTestExtractPoints
     */
    public function testExtractPoints(string $point_set, ?array $scale_data, bool $linear, array $output): void
    {
        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                GisGeometry::class,
                'extractPoints',
                [
                    $point_set,
                    $scale_data,
                    $linear,
                ]
            )
        );
    }

    /**
     * data provider for testExtractPoints
     *
     * @return array data for testExtractPoints
     */
    public function providerForTestExtractPoints(): array
    {
        return [
            // with no scale data
            [
                '12 35,48 75,69 23',
                null,
                false,
                [
                    0 => [
                        12,
                        35,
                    ],
                    1 => [
                        48,
                        75,
                    ],
                    2 => [
                        69,
                        23,
                    ],
                ],
            ],
            // with scale data
            [
                '12 35,48 75,69 23',
                [
                    'x'      => 5,
                    'y'      => 5,
                    'scale'  => 2,
                    'height' => 200,
                ],
                false,
                [
                    0 => [
                        14,
                        140,
                    ],
                    1 => [
                        86,
                        60,
                    ],
                    2 => [
                        128,
                        164,
                    ],
                ],
            ],
            // linear output
            [
                '12 35,48 75,69 23',
                null,
                true,
                [
                    12,
                    35,
                    48,
                    75,
                    69,
                    23,
                ],
            ],
            // if a single part of a coordinate is empty
            [
                '12 35,48 75,69 ',
                null,
                false,
                [
                    0 => [
                        12,
                        35,
                    ],
                    1 => [
                        48,
                        75,
                    ],
                    2 => [
                        0,
                        0,
                    ],
                ],
            ],
        ];
    }

    /**
     * test case for getBoundsForOl() method
     *
     * @param int    $srid       spatial reference ID
     * @param array  $scale_data data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForTestGetBoundsForOl
     */
    public function testGetBoundsForOl(int $srid, array $scale_data, string $output): void
    {
        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                GisGeometry::class,
                'getBoundsForOl',
                [
                    $srid,
                    $scale_data,
                ]
            )
        );
    }

    /**
     * data provider for testGetBoundsForOl() test case
     *
     * @return array test data for the testGetBoundsForOl() test case
     */
    public function providerForTestGetBoundsForOl(): array
    {
        return [
            [
                4326,
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var minLoc = [0, 0];var maxLoc = [1, 1];'
                . 'var ext = ol.extent.boundingExtent([min'
                . 'Loc, maxLoc]);ext = ol.proj.transformEx'
                . 'tent(ext, ol.proj.get("EPSG:4326"), ol.'
                . 'proj.get(\'EPSG:3857\'));map.getView().'
                . 'fit(ext, map.getSize());',
            ],

        ];
    }

    /**
     * test case for getPolygonArrayForOpenLayers() method
     *
     * @param array  $polygons x and y coordinate pairs for each polygon
     * @param int    $srid     spatial reference id
     * @param string $output   expected output
     *
     * @dataProvider providerForTestGetPolygonArrayForOpenLayers
     */
    public function testGetPolygonArrayForOpenLayers(array $polygons, int $srid, string $output): void
    {
        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                GisGeometry::class,
                'getPolygonArrayForOpenLayers',
                [
                    $polygons,
                    $srid,
                ]
            )
        );
    }

    /**
     * data provider for testGetPolygonArrayForOpenLayers() test case
     *
     * @return array test data for testGetPolygonArrayForOpenLayers() test case
     */
    public function providerForTestGetPolygonArrayForOpenLayers(): array
    {
        return [
            [
                ['Triangle'],
                4326,
                'var polygonArray = [];var arr = [];var lineArr = [];'
                . 'var line = new ol.geom.LinearRing(new Array((new ol'
                . '.geom.Point([0,0]).transform(ol.proj.get("EPSG:4326'
                . '"), ol.proj.get(\'EPSG:3857\'))).getCoordinates()))'
                . ';var coord = line.getCoordinates();for (var i = 0; i < coord.length; i++)'
                . ' lineArr.push(coord[i]);arr.push(lineArr);var pol'
                . 'ygon = new ol.geom.Polygon(arr);polygonArray.push(p'
                . 'olygon);',
            ],
        ];
    }
}
