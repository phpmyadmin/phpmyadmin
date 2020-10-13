<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisLineString;
use TCPDF;
use function function_exists;
use function imagecreatetruecolor;
use function preg_match;

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
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = GisLineString::singleton();
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
     * data provider for testGenerateWkt
     *
     * @return array data for testGenerateWkt
     */
    public function providerForTestGenerateWkt(): array
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
    public function providerForTestGenerateParams(): array
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
                [2 => $temp1],
            ],
        ];
    }

    /**
     * data provider for testScaleRow
     *
     * @return array data for testScaleRow
     */
    public function providerForTestScaleRow(): array
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
     */
    public function testPrepareRowAsPng(
        string $spatial,
        string $label,
        string $line_color,
        array $scale_data,
        $image
    ): void {
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
    public function providerForPrepareRowAsPng(): array
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
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        string $line_color,
        array $scale_data,
        TCPDF $pdf
    ): void {
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
    public function providerForPrepareRowAsPdf(): array
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
     * @param string $spatial   GIS LINESTRING object
     * @param string $label     label for the GIS LINESTRING object
     * @param string $lineColor color for the GIS LINESTRING object
     * @param array  $scaleData array containing data related to scaling
     * @param string $output    expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        string $lineColor,
        array $scaleData,
        string $output
    ): void {
        $string = $this->object->prepareRowAsSvg(
            $spatial,
            $label,
            $lineColor,
            $scaleData
        );
        $this->assertEquals(1, preg_match($output, $string));
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array test data for testPrepareRowAsSvg() test case
     */
    public function providerForPrepareRowAsSvg(): array
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
     * @param array  $line_color color for the GIS LINESTRING object
     * @param array  $scale_data array containing data related to scaling
     * @param string $output     expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $line_color,
        array $scale_data,
        string $output
    ): void {
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
    public function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'LINESTRING(12 35,48 75,69 23,25 45,14 53,35 78)',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'minX' => '0',
                    'minY' => '0',
                    'maxX' => '1',
                    'maxY' => '1',
                ],
                'var style = new ol.style.Style({stroke: new ol.style.Stroke({"color":[176,46,224],'
                . '"width":2}), text: new ol.style.Text({"text":"Ol"})});var minLoc = [0, 0];var ma'
                . 'xLoc = [1, 1];var ext = ol.extent.boundingExtent([minLoc, maxLoc]);ext = ol.proj'
                . '.transformExtent(ext, ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'));map.'
                . 'getView().fit(ext, map.getSize());var line = new ol.Feature({geometry: new ol.ge'
                . 'om.LineString(new Array((new ol.geom.Point([12,35]).transform(ol.proj.get("EPSG:'
                . '4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([48,75'
                . ']).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinat'
                . 'es(), (new ol.geom.Point([69,23]).transform(ol.proj.get("EPSG:4326"), ol.proj.ge'
                . 't(\'EPSG:3857\'))).getCoordinates(), (new ol.geom.Point([25,45]).transform(ol.pr'
                . 'oj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\'))).getCoordinates(), (new ol.geom'
                . '.Point([14,53]).transform(ol.proj.get("EPSG:4326"), ol.proj.get(\'EPSG:3857\')))'
                . '.getCoordinates(), (new ol.geom.Point([35,78]).transform(ol.proj.get("EPSG:4326"'
                . '), ol.proj.get(\'EPSG:3857\'))).getCoordinates()))});line.setStyle(style);vector'
                . 'Layer.addFeature(line);',
            ],
        ];
    }
}
