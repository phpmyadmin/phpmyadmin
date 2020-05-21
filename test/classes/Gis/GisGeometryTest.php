<?php
/**
 * Test for PhpMyAdmin\Gis\GisGeometry
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for PhpMyAdmin\Gis\GisGeometry class
 */
class GisGeometryTest extends AbstractTestCase
{
    /** @access protected */
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
    public function testSetMinMax($point_set, $min_max, $output): void
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
    public function providerForTestSetMinMax()
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
     * @param string $output Expected output
     *
     * @dataProvider providerForTestGenerateParams
     */
    public function testGenerateParams($value, $output): void
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
    public function providerForTestGenerateParams()
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
     * @param string $point_set  String of comma separated points
     * @param array  $scale_data Data related to scaling
     * @param bool   $linear     If true, as a 1D array, else as a 2D array
     * @param array  $output     Expected output
     *
     * @dataProvider providerForTestExtractPoints
     */
    public function testExtractPoints($point_set, $scale_data, $linear, $output): void
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
    public function providerForTestExtractPoints()
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
     * @param string $srid       spatial reference ID
     * @param array  $scale_data data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForTestGetBoundsForOl
     */
    public function testGetBoundsForOl($srid, $scale_data, $output): void
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
    public function providerForTestGetBoundsForOl()
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
                'bound = new OpenLayers.Bounds(); '
                    . 'bound.extend(new OpenLayers.LonLat(0, 0).transform('
                    . 'new OpenLayers.Projection("EPSG:4326"), '
                    . 'map.getProjectionObject())); '
                    . 'bound.extend(new OpenLayers.LonLat(1, 1).transform('
                    . 'new OpenLayers.Projection("EPSG:4326"), '
                    . 'map.getProjectionObject()));',
            ],

        ];
    }

    /**
     * test case for getPolygonArrayForOpenLayers() method
     *
     * @param array  $polygons x and y coordinate pairs for each polygon
     * @param string $srid     spatial reference id
     * @param string $output   expected output
     *
     * @dataProvider providerForTestGetPolygonArrayForOpenLayers
     */
    public function testGetPolygonArrayForOpenLayers($polygons, $srid, $output): void
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
    public function providerForTestGetPolygonArrayForOpenLayers()
    {
        return [
            [
                ['Triangle'],
                4326,
                'new Array('
                    . 'new OpenLayers.Geometry.Polygon('
                    . 'new Array('
                    . 'new OpenLayers.Geometry.LinearRing('
                    . 'new Array('
                    . '(new OpenLayers.Geometry.Point(0,0)).transform('
                    . 'new OpenLayers.Projection("EPSG:4326"), '
                    . 'map.getProjectionObject()))))))',
            ],
        ];
    }
}
