<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Gis\GisLineString
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisLineString;
use PhpMyAdmin\Tests\Gis\GisGeomTestCase;
use TCPDF;

/**
 * Tests for PhpMyAdmin\Gis\GisLineString class
 *
 * @package PhpMyAdmin-test
 */
class GisLineStringTest extends GisGeomTestCase
{
    /**
     * @var    GisLineString
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
        $this->object = GisLineString::singleton();
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
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public function providerForTestGenerateWkt()
    {
        $temp1 = [
            0 => [
                'LINESTRING' => [
                    'no_of_points' => 2,
                    0 => [
                        'x' => 5.02,
                        'y' => 8.45,
                    ],
                    1 => [
                        'x' => 6.14,
                        'y' => 0.15,
                    ],
                ],
            ],
        ];

        $temp2 = $temp1;
        $temp2[0]['LINESTRING']['no_of_points'] = 3;
        $temp2[0]['LINESTRING'][2] = ['x' => 1.56];

        $temp3 = $temp2;
        $temp3[0]['LINESTRING']['no_of_points'] = -1;

        $temp4 = $temp3;
        $temp4[0]['LINESTRING']['no_of_points'] = 3;
        unset($temp4[0]['LINESTRING'][2]['x']);

        return [
            [
                $temp1,
                0,
                null,
                'LINESTRING(5.02 8.45,6.14 0.15)',
            ],
            // if a coordinate is missing, default is empty string
            [
                $temp2,
                0,
                null,
                'LINESTRING(5.02 8.45,6.14 0.15,1.56 )',
            ],
            // if no_of_points is not valid, it is considered as 2
            [
                $temp3,
                0,
                null,
                'LINESTRING(5.02 8.45,6.14 0.15)',
            ],
            // missing coordinates are replaced with provided values (3rd parameter)
            [
                $temp4,
                0,
                '0',
                'LINESTRING(5.02 8.45,6.14 0.15,0 0)',
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
        $temp = [
            'LINESTRING' => [
                'no_of_points' => 2,
                0 => [
                    'x' => '5.02',
                    'y' => '8.45',
                ],
                1 => [
                    'x' => '6.14',
                    'y' => '0.15',
                ],
            ],
        ];
        $temp1 = $temp;
        $temp1['gis_type'] = 'LINESTRING';

        return [
            [
                "'LINESTRING(5.02 8.45,6.14 0.15)',124",
                null,
                [
                    'srid' => '124',
                    0 => $temp,
                ],
            ],
            [
                'LINESTRING(5.02 8.45,6.14 0.15)',
                2,
                [
                    2 => $temp1,
                ],
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                [
                    'minX' => 12,
                    'maxX' => 69,
                    'minY' => 23,
                    'maxY' => 78,
                ],
            ],
        ];
    }

    /**
     * test case for prepareRowAsPng() method
     *
     * @param string   $spatial    GIS LINESTRING object
     * @param string   $label      label for the GIS LINESTRING object
     * @param string   $line_color color for the GIS LINESTRING object
     * @param array    $scale_data array containing data related to scaling
     * @param resource $image      image object
     *
     * @dataProvider providerForPrepareRowAsPng
     * @return void
     */
    public function testPrepareRowAsPng(
        $spatial,
        $label,
        $line_color,
        $scale_data,
        $image
    ) {
        $this->object->prepareRowAsPng(
            $spatial,
            $label,
            $line_color,
            $scale_data,
            $image
        );
        /* TODO: this never fails */
        $this->assertTrue(true);
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
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
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      label for the GIS LINESTRING object
     * @param string $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param TCPDF  $pdf        TCPDF instance
     *
     * @dataProvider providerForPrepareRowAsPdf
     * @return void
     */
    public function testPrepareRowAsPdf(
        $spatial,
        $label,
        $line_color,
        $scale_data,
        $pdf
    ) {
        $return = $this->object->prepareRowAsPdf(
            $spatial,
            $label,
            $line_color,
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
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
     * @param string $spatial    GIS LINESTRING object
     * @param string $label      label for the GIS LINESTRING object
     * @param string $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     * @return void
     */
    public function testPrepareRowAsSvg(
        $spatial,
        $label,
        $line_color,
        $scale_data,
        $output
    ) {
        $string = $this->object->prepareRowAsSvg(
            $spatial,
            $label,
            $line_color,
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                'svg',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '/^(<polyline points="0,218 72,138 114,242 26,198 4,182 46,132 " '
                . 'name="svg" id="svg)(\d+)(" class="linestring vector" fill="none" '
                . 'stroke="#B02EE0" stroke-width="2"\/>)$/',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial    GIS LINESTRING object
     * @param int    $srid       spatial reference ID
     * @param string $label      label for the GIS LINESTRING object
     * @param string $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     * @return void
     */
    public function testPrepareRowAsOl(
        $spatial,
        $srid,
        $label,
        $line_color,
        $scale_data,
        $output
    ) {
        $this->assertEquals(
            $this->object->prepareRowAsOl(
                $spatial,
                $srid,
                $label,
                $line_color,
                $scale_data
            ),
            $output
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
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
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
                . '(1, 1).transform(new OpenLayers.Projection("EPSG:4326"), map.get'
                . 'ProjectionObject()));vectorLayer.addFeatures(new OpenLayers.Feat'
                . 'ure.Vector(new OpenLayers.Geometry.LineString(new Array((new Open'
                . 'Layers.Geometry.Point(12,35)).transform(new OpenLayers.Projection'
                . '("EPSG:4326"), map.getProjectionObject()), (new OpenLayers.Geome'
                . 'try.Point(48,75)).transform(new OpenLayers.Projection("EPSG:4326"'
                . '), map.getProjectionObject()), (new OpenLayers.Geometry.Point(69'
                . ',23)).transform(new OpenLayers.Projection("EPSG:4326"), map.'
                . 'getProjectionObject()), (new OpenLayers.Geometry.Point(25,45)).'
                . 'transform(new OpenLayers.Projection("EPSG:4326"), map.'
                . 'getProjectionObject()), (new OpenLayers.Geometry.Point(14,53)).'
                . 'transform(new OpenLayers.Projection("EPSG:4326"), map.get'
                . 'ProjectionObject()), (new OpenLayers.Geometry.Point(35,78)).'
                . 'transform(new OpenLayers.Projection("EPSG:4326"), map.'
                . 'getProjectionObject()))), null, {"strokeColor":"#B02EE0",'
                . '"strokeWidth":2,"label":"Ol","fontSize":10}));',
            ],
        ];
    }
}
