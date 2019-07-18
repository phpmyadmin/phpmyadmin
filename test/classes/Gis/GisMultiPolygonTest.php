<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Gis\GisMultiPolygon
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisMultiPolygon;
use PhpMyAdmin\Tests\Gis\GisGeomTestCase;
use TCPDF;

/**
 * Tests for PhpMyAdmin\Gis\GisMultiPolygon class
 *
 * @package PhpMyAdmin-test
 */
class GisMultiPolygonTest extends GisGeomTestCase
{
    /**
     * @var    GisMultiPolygon
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        $this->object = GisMultiPolygon::singleton();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Provide some common data to data providers
     *
     * @return array common data for data providers
     */
    private function _getData()
    {
        return [
            'MULTIPOLYGON' => [
                'no_of_polygons' => 2,
                0 => [
                    'no_of_lines' => 2,
                    0 => [
                        'no_of_points' => 5,
                        0 => [
                            'x' => 35,
                            'y' => 10,
                        ],
                        1 => [
                            'x' => 10,
                            'y' => 20,
                        ],
                        2 => [
                            'x' => 15,
                            'y' => 40,
                        ],
                        3 => [
                            'x' => 45,
                            'y' => 45,
                        ],
                        4 => [
                            'x' => 35,
                            'y' => 10,
                        ],
                    ],
                    1 => [
                        'no_of_points' => 4,
                        0 => [
                            'x' => 20,
                            'y' => 30,
                        ],
                        1 => [
                            'x' => 35,
                            'y' => 32,
                        ],
                        2 => [
                            'x' => 30,
                            'y' => 20,
                        ],
                        3 => [
                            'x' => 20,
                            'y' => 30,
                        ],
                    ],
                ],
                1 => [
                    'no_of_lines' => 1,
                    0 => [
                        'no_of_points' => 4,
                        0 => [
                            'x' => 123,
                            'y' => 0,
                        ],
                        1 => [
                            'x' => 23,
                            'y' => 30,
                        ],
                        2 => [
                            'x' => 17,
                            'y' => 63,
                        ],
                        3 => [
                            'x' => 123,
                            'y' => 0,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public function providerForTestGenerateWkt()
    {
        $temp = [
            0 => $this->_getData(),
        ];

        $temp1 = $temp;
        $temp1[0]['MULTIPOLYGON']['no_of_polygons'] = 0;

        $temp2 = $temp;
        $temp2[0]['MULTIPOLYGON'][1]['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTIPOLYGON'][1][0]['no_of_points'] = 3;

        return [
            [
                $temp,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
            ],
            // at lease one polygon should be there
            [
                $temp1,
                0,
                null,
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)))',
            ],
            // a polygon should have at least one ring
            [
                $temp2,
                0,
                null,
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
     * data provider for testGenerateParams
     *
     * @return array data for testGenerateParams
     */
    public function providerForTestGenerateParams()
    {
        $temp = $this->_getData();

        $temp1 = $this->_getData();
        $temp1['gis_type'] = 'MULTIPOLYGON';

        return [
            [
                "'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),"
                . "(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',124",
                null,
                [
                    'srid' => '124',
                    0 => $temp,
                ],
            ],
            [
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10)'
                    . ',(20 30,35 32,30 20,20 30)),((123 0,23 30,17 63,123 0)))',
                2,
                [
                    2 => $temp1,
                ],
            ],
        ];
    }

    /**
     * test getShape method
     *
     * @param array  $row_data array of GIS data
     * @param string $shape    expected shape in WKT
     *
     * @dataProvider providerForTestGetShape
     * @return void
     */
    public function testGetShape($row_data, $shape): void
    {
        $this->assertEquals($this->object->getShape($row_data), $shape);
    }

    /**
     * data provider for testGetShape
     *
     * @return array data for testGetShape
     */
    public function providerForTestGetShape()
    {
        return [
            [
                [
                    'parts' => [
                        0 => [
                            'points' => [
                                0 => [
                                    'x' => 10,
                                    'y' => 10,
                                ],
                                1 => [
                                    'x' => 10,
                                    'y' => 40,
                                ],
                                2 => [
                                    'x' => 50,
                                    'y' => 40,
                                ],
                                3 => [
                                    'x' => 50,
                                    'y' => 10,
                                ],
                                4 => [
                                    'x' => 10,
                                    'y' => 10,
                                ],
                            ],
                        ],
                        1 => [
                            'points' => [
                                0 => [
                                    'x' => 60,
                                    'y' => 40,
                                ],
                                1 => [
                                    'x' => 75,
                                    'y' => 65,
                                ],
                                2 => [
                                    'x' => 90,
                                    'y' => 40,
                                ],
                                3 => [
                                    'x' => 60,
                                    'y' => 40,
                                ],
                            ],
                        ],
                        2 => [
                            'points' => [
                                0 => [
                                    'x' => 20,
                                    'y' => 20,
                                ],
                                1 => [
                                    'x' => 40,
                                    'y' => 20,
                                ],
                                2 => [
                                    'x' => 25,
                                    'y' => 30,
                                ],
                                3 => [
                                    'x' => 20,
                                    'y' => 20,
                                ],
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
     * data provider for testScaleRow
     *
     * @return array data for testScaleRow
     */
    public function providerForTestScaleRow()
    {
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),'
                    . '((105 0,56 20,78 73,105 0)))',
                [
                    'minX' => 16,
                    'maxX' => 147,
                    'minY' => 0,
                    'maxY' => 83,
                ],
            ],
            [
                'MULTIPOLYGON(((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20'
                    . ',20 30)),((105 0,56 20,78 73,105 0)))',
                [
                    'minX' => 10,
                    'maxX' => 105,
                    'minY' => 0,
                    'maxY' => 73,
                ],
            ],
        ];
    }


    /**
     * test case for prepareRowAsPng() method
     *
     * @param string   $spatial    GIS MULTIPOLYGON object
     * @param string   $label      label for the GIS MULTIPOLYGON object
     * @param string   $fill_color color for the GIS MULTIPOLYGON object
     * @param array    $scale_data array containing data related to scaling
     * @param resource $image      image object
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        $spatial,
        $label,
        $fill_color,
        $scale_data,
        $image
    ) {
        $return = $this->object->prepareRowAsPng(
            $spatial,
            $label,
            $fill_color,
            $scale_data,
            $image
        );
        $this->assertImage($return);
    }

    /**
     * data provider for testPrepareRowAsPng() test case
     *
     * @return array test data for testPrepareRowAsPng() test case
     */
    public function providerForPrepareRowAsPng()
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension missing!');
        }
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),'
                    . '((105 0,56 20,78 73,105 0)))',
                'image',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                imagecreatetruecolor(120, 150),
            ],

        ];
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      label for the GIS MULTIPOLYGON object
     * @param string $fill_color color for the GIS MULTIPOLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param TCPDF  $pdf        TCPDF instance
     *
     * @return void
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        $spatial,
        $label,
        $fill_color,
        $scale_data,
        $pdf
    ) {
        $return = $this->object->prepareRowAsPdf(
            $spatial,
            $label,
            $fill_color,
            $scale_data,
            $pdf
        );
        $this->assertInstanceOf('TCPDF', $return);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public function providerForPrepareRowAsPdf()
    {
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),'
                    . '((105 0,56 20,78 73,105 0)))',
                'pdf',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                new TCPDF(),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param string $label      label for the GIS MULTIPOLYGON object
     * @param string $fill_color color for the GIS MULTIPOLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        $spatial,
        $label,
        $fill_color,
        $scale_data,
        $output
    ) {
        $string = $this->object->prepareRowAsSvg(
            $spatial,
            $label,
            $fill_color,
            $scale_data
        );
        $this->assertEquals(1, preg_match($output, $string));
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg()
    {
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),'
                    . '((105 0,56 20,78 73,105 0)))',
                'svg',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '/^(<path d=" M 248, 208 L 270, 122 L 8, 138 Z " name="svg" class="'
                . 'multipolygon vector" stroke="black" stroke-width="0.5" fill="'
                . '#B02EE0" fill-rule="evenodd" fill-opacity="0.8" id="svg)(\d+)'
                . '("\/><path d=" M 186, 288 L 88, 248 L 132, 142 Z " name="svg" '
                . 'class="multipolygon vector" stroke="black" stroke-width="0.5" '
                . 'fill="#B02EE0" fill-rule="evenodd" fill-opacity="0.8" id="svg)'
                . '(\d+)("\/>)$/',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS MULTIPOLYGON object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS MULTIPOLYGON object
     * @param string $fill_color color for the GIS MULTIPOLYGON object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @return void
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        $spatial,
        $srid,
        $label,
        $fill_color,
        $scale_data,
        $output
    ) {
        $this->assertEquals(
            $output,
            $this->object->prepareRowAsOl(
                $spatial,
                $srid,
                $label,
                $fill_color,
                $scale_data
            )
        );
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForPrepareRowAsOl()
    {
        return [
            [
                'MULTIPOLYGON(((136 40,147 83,16 75,136 40)),'
                    . '((105 0,56 20,78 73,105 0)))',
                4326,
                'Ol',
                '#B02EE0',
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'bound = new OpenLayers.Bounds(); bound.extend(new OpenLayers.'
                . 'LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326"), '
                . 'map.getProjectionObject())); bound.extend(new OpenLayers.LonLat'
                . '(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.ge'
                . 'tProjectionObject()));vectorLayer.addFeatures(new OpenLayers.'
                . 'Feature.Vector(new OpenLayers.Geometry.MultiPolygon(new Array'
                . '(new OpenLayers.Geometry.Polygon(new Array(new OpenLayers.Geo'
                . 'metry.LinearRing(new Array((new OpenLayers.Geometry.Point'
                . '(136,40)).transform(new OpenLayers.Projection("EPSG:4326"), '
                . 'map.getProjectionObject()), (new OpenLayers.Geometry.Point(147,'
                . '83)).transform(new OpenLayers.Projection("EPSG:4326"), map.get'
                . 'ProjectionObject()), (new OpenLayers.Geometry.Point(16,75)).'
                . 'transform(new OpenLayers.Projection("EPSG:4326"), map.getPro'
                . 'jectionObject()), (new OpenLayers.Geometry.Point(136,40)).trans'
                . 'form(new OpenLayers.Projection("EPSG:4326"), map.getProjection'
                . 'Object()))))), new OpenLayers.Geometry.Polygon(new Array(new '
                . 'OpenLayers.Geometry.LinearRing(new Array((new OpenLayers.Geometry'
                . '.Point(105,0)).transform(new OpenLayers.Projection("EPSG:4326"), '
                . 'map.getProjectionObject()), (new OpenLayers.Geometry.Point(56,20)'
                . ').transform(new OpenLayers.Projection("EPSG:4326"), map.getProjec'
                . 'tionObject()), (new OpenLayers.Geometry.Point(78,73)).transform'
                . '(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject'
                . '()), (new OpenLayers.Geometry.Point(105,0)).transform(new Open'
                . 'Layers.Projection("EPSG:4326"), map.getProjectionObject()))))))'
                . '), null, {"strokeColor":"#000000","strokeWidth":0.5,"fillColor":'
                . '"#B02EE0","fillOpacity":0.8,"label":"Ol","fontSize":10}));',
            ],
        ];
    }
}
