<?php
/**
 * Test for PhpMyAdmin\Gis\GisGeometry
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisGeometryCollection;
use PhpMyAdmin\Tests\AbstractTestCase;
use TCPDF;
use function function_exists;
use function imagecreatetruecolor;
use function imagesx;
use function imagesy;
use function preg_match;

/**
 * Tests for PhpMyAdmin\Gis\GisGeometryCollection class
 */
class GisGeometryCollectionTest extends AbstractTestCase
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
        $this->object = GisGeometryCollection::singleton();
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
     * Test for scaleRow
     *
     * @param string $spatial string to parse
     * @param array  $output  expected parsed output
     *
     * @dataProvider providerForScaleRow
     */
    public function testScaleRow($spatial, $output): void
    {
        $this->assertEquals($output, $this->object->scaleRow($spatial));
    }

    /**
     * Data provider for testScaleRow() test case
     *
     * @return array test data for testScaleRow() test case
     */
    public function providerForScaleRow()
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),'
                    . '(20 30,35 32,30 20,20 30)))',
                [
                    'maxX' => 45.0,
                    'minX' => 10.0,
                    'maxY' => 45.0,
                    'minY' => 10.0,
                ],
            ],
        ];
    }

    /**
     * Test for generateWkt
     *
     * @param array  $gis_data array of GIS data
     * @param int    $index    index in $gis_data
     * @param string $empty    empty parameter
     * @param string $output   expected output
     *
     * @dataProvider providerForGenerateWkt
     */
    public function testGenerateWkt($gis_data, $index, $empty, $output): void
    {
        $this->assertEquals(
            $output,
            $this->object->generateWkt($gis_data, $index, $empty)
        );
    }

    /**
     * Data provider for testGenerateWkt() test case
     *
     * @return array test data for testGenerateWkt() test case
     */
    public function providerForGenerateWkt()
    {
        $temp1 = [
            0 => [
                'gis_type' => 'LINESTRING',
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

        return [
            [
                $temp1,
                0,
                null,
                'GEOMETRYCOLLECTION(LINESTRING(5.02 8.45,6.14 0.15))',
            ],
        ];
    }

    /**
     * Test for generateParams
     *
     * @param string $value  string to parse
     * @param array  $output expected parsed output
     *
     * @dataProvider providerForGenerateParams
     */
    public function testGenerateParams($value, $output): void
    {
        $this->assertEquals($output, $this->object->generateParams($value));
    }

    /**
     * Data provider for testGenerateParams() test case
     *
     * @return array test data for testGenerateParams() test case
     */
    public function providerForGenerateParams()
    {
        return [
            [
                'GEOMETRYCOLLECTION(LINESTRING(5.02 8.45,6.14 0.15))',
                [
                    'srid' => 0,
                    'GEOMETRYCOLLECTION' => ['geom_count' => 1],
                    '0' => [
                        'gis_type' => 'LINESTRING',
                        'LINESTRING' => [
                            'no_of_points' => 2,
                            '0' => [
                                'x' => 5.02,
                                'y' => 8.45,
                            ],
                            '1' => [
                                'x' => 6.14,
                                'y' => 0.15,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for prepareRowAsPng
     *
     * @param string   $spatial    string to parse
     * @param string   $label      field label
     * @param string   $line_color line color
     * @param array    $scale_data scaling parameters
     * @param resource $image      initial image
     *
     * @return void
     *
     * @dataProvider providerForPrepareRowAsPng
     */
    public function testPrepareRowAsPng(
        $spatial,
        $label,
        $line_color,
        $scale_data,
        $image
    ) {
        $return = $this->object->prepareRowAsPng(
            $spatial,
            $label,
            $line_color,
            $scale_data,
            $image
        );
        $this->assertEquals(120, imagesx($return));
        $this->assertEquals(150, imagesy($return));
    }

    /**
     * Data provider for testPrepareRowAsPng() test case
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
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),'
                    . '(20 30,35 32,30 20,20 30)))',
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
     * Test for prepareRowAsPdf
     *
     * @param string $spatial    string to parse
     * @param string $label      field label
     * @param string $line_color line color
     * @param array  $scale_data scaling parameters
     * @param string $pdf        expected output
     *
     * @return void
     *
     * @dataProvider providerForPrepareRowAsPdf
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
     * Data provider for testPrepareRowAsPdf() test case
     *
     * @return array test data for testPrepareRowAsPdf() test case
     */
    public function providerForPrepareRowAsPdf()
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),'
                    . '(20 30,35 32,30 20,20 30)))',
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
     * Test for prepareRowAsSvg
     *
     * @param string $spatial   string to parse
     * @param string $label     field label
     * @param string $lineColor line color
     * @param array  $scaleData scaling parameters
     * @param string $output    expected output
     *
     * @return void
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        $spatial,
        $label,
        $lineColor,
        $scaleData,
        $output
    ) {
        $string = $this->object->prepareRowAsSvg(
            $spatial,
            $label,
            $lineColor,
            $scaleData
        );
        $this->assertEquals(1, preg_match($output, $string));
        // assertMatchesRegularExpression added in 9.1
        $this->assertRegExp(
            $output,
            $this->object->prepareRowAsSvg(
                $spatial,
                $label,
                $lineColor,
                $scaleData
            )
        );
    }

    /**
     * Data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg()
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),'
                    . '(20 30,35 32,30 20,20 30)))',
                'svg',
                '#B02EE0',
                [
                    'x' => 12,
                    'y' => 69,
                    'scale' => 2,
                    'height' => 150,
                ],
                '/^(<path d=" M 46, 268 L -4, 248 L 6, 208 L 66, 198 Z  M 16,'
                    . ' 228 L 46, 224 L 36, 248 Z " name="svg" id="svg)(\d+)'
                    . '(" class="polygon vector" stroke="black" stroke-width="0.5"'
                    . ' fill="#B02EE0" fill-rule="evenodd" fill-opacity="0.8"\/>)$/',
            ],
        ];
    }

    /**
     * Test for prepareRowAsOl
     *
     * @param string $spatial    string to parse
     * @param int    $srid       SRID
     * @param string $label      field label
     * @param string $line_color line color
     * @param array  $scale_data scaling parameters
     * @param string $output     expected output
     *
     * @return void
     *
     * @dataProvider providerForPrepareRowAsOl
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
            $output,
            $this->object->prepareRowAsOl(
                $spatial,
                $srid,
                $label,
                $line_color,
                $scale_data
            )
        );
    }

    /**
     * Data provider for testPrepareRowAsOl() test case
     *
     * @return array test data for testPrepareRowAsOl() test case
     */
    public function providerForPrepareRowAsOl()
    {
        return [
            [
                'GEOMETRYCOLLECTION(POLYGON((35 10,10 20,15 40,45 45,35 10),'
                    . '(20 30,35 32,30 20,20 30)))',
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
                . 'LonLat(0, 0).transform(new OpenLayers.Projection("EPSG:4326'
                . '"), map.getProjectionObject())); bound.extend(new OpenLayer'
                . 's.LonLat(1, 1).transform(new OpenLayers.Projection("EPSG:43'
                . '26"), map.getProjectionObject()));vectorLayer.addFeatures(n'
                . 'ew OpenLayers.Feature.Vector(new OpenLayers.Geometry.Polygo'
                . 'n(new Array(new OpenLayers.Geometry.LinearRing(new Array((n'
                . 'ew OpenLayers.Geometry.Point(35,10)).transform(new OpenLaye'
                . 'rs.Projection("EPSG:4326"), map.getProjectionObject()), (ne'
                . 'w OpenLayers.Geometry.Point(10,20)).transform(new OpenLayer'
                . 's.Projection("EPSG:4326"), map.getProjectionObject()), (new'
                . ' OpenLayers.Geometry.Point(15,40)).transform(new OpenLayers.'
                . 'Projection("EPSG:4326"), map.getProjectionObject()), (new O'
                . 'penLayers.Geometry.Point(45,45)).transform(new OpenLayers.P'
                . 'rojection("EPSG:4326"), map.getProjectionObject()), (new Op'
                . 'enLayers.Geometry.Point(35,10)).transform(new OpenLayers.Pr'
                . 'ojection("EPSG:4326"), map.getProjectionObject()))), new Op'
                . 'enLayers.Geometry.LinearRing(new Array((new OpenLayers.Geom'
                . 'etry.Point(20,30)).transform(new OpenLayers.Projection("EPS'
                . 'G:4326"), map.getProjectionObject()), (new OpenLayers.Geome'
                . 'try.Point(35,32)).transform(new OpenLayers.Projection("EPSG'
                . ':4326"), map.getProjectionObject()), (new OpenLayers.Geomet'
                . 'ry.Point(30,20)).transform(new OpenLayers.Projection("EPSG:'
                . '4326"), map.getProjectionObject()), (new OpenLayers.Geometry'
                . '.Point(20,30)).transform(new OpenLayers.Projection("EPSG:43'
                . '26"), map.getProjectionObject()))))), null, {"strokeColor":'
                . '"#000000","strokeWidth":0.5,"fillColor":"#B02EE0","fillOpac'
                . 'ity":0.8,"label":"Ol","fontSize":10}));',
            ],
        ];
    }
}
