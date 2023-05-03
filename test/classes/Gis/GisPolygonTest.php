<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Gis\ScaleData;
use PhpMyAdmin\Image\ImageWrapper;
use TCPDF;

/**
 * @covers \PhpMyAdmin\Gis\GisPolygon
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GisPolygonTest extends GisGeomTestCase
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
                'no_of_lines' => 2,
                0 => [
                    'no_of_points' => 5,
                    0 => ['x' => 35, 'y' => 10],
                    1 => ['x' => 10, 'y' => 20],
                    2 => ['x' => 15, 'y' => 40],
                    3 => ['x' => 45, 'y' => 45],
                    4 => ['x' => 35, 'y' => 10],
                ],
                1 => [
                    'no_of_points' => 4,
                    0 => ['x' => 20, 'y' => 30],
                    1 => ['x' => 35, 'y' => 32],
                    2 => ['x' => 30, 'y' => 20],
                    3 => ['x' => 20, 'y' => 30],
                ],
            ],
        ];
    }

    /**
     * Test for generateWkt
     *
     * @param array<mixed> $gisData
     * @param int          $index   index in $gis_data
     * @param string|null  $empty   empty parameter
     * @param string       $output  expected output
     *
     * @dataProvider providerForTestGenerateWkt
     */
    public function testGenerateWkt(array $gisData, int $index, string|null $empty, string $output): void
    {
        $object = GisPolygon::singleton();
        $this->assertEquals($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string|null, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [0 => self::getData()];

        $temp1 = $temp;
        unset($temp1[0]['POLYGON'][1][3]['y']);

        $temp2 = $temp;
        $temp2[0]['POLYGON']['no_of_lines'] = 0;

        $temp3 = $temp;
        $temp3[0]['POLYGON'][1]['no_of_points'] = 3;

        return [
            [$temp, 0, null, 'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))'],
            // values at undefined index
            [$temp, 1, null, 'POLYGON(( , , , ))'],
            // if a coordinate is missing, default is empty string
            [$temp1, 0, null, 'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 ))'],
            // missing coordinates are replaced with provided values (3rd parameter)
            [$temp1, 0, '0', 'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 0))'],
            // should have at least one ring
            [$temp2, 0, '0', 'POLYGON((35 10,10 20,15 40,45 45,35 10))'],
            // a ring should have at least four points
            [$temp3, 0, '0', 'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))'],
        ];
    }

    /**
     * test generateParams method
     *
     * @param string       $wkt    point in WKT form
     * @param array<mixed> $params expected output array
     *
     * @dataProvider providerForTestGenerateParams
     */
    public function testGenerateParams(string $wkt, array $params): void
    {
        $object = GisPolygon::singleton();
        $this->assertEquals($params, $object->generateParams($wkt));
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
                '\'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))\',124',
                ['srid' => 124, 0 => self::getData()],
            ],
        ];
    }

    /**
     * test for Area
     *
     * @param mixed[] $ring array of points forming the ring
     * @param float   $area area of the ring
     *
     * @dataProvider providerForTestArea
     */
    public function testArea(array $ring, float $area): void
    {
        $object = GisPolygon::singleton();
        $this->assertEquals($area, $object->area($ring));
    }

    /**
     * data provider for testArea
     *
     * @return array<array{mixed[], float}>
     */
    public static function providerForTestArea(): array
    {
        return [
            [[0 => ['x' => 35, 'y' => 10], 1 => ['x' => 10, 'y' => 10], 2 => ['x' => 15, 'y' => 40]], -375.00],
            // first point of the ring repeated as the last point
            [
                [
                    0 => ['x' => 35, 'y' => 10],
                    1 => ['x' => 10, 'y' => 10],
                    2 => ['x' => 15, 'y' => 40],
                    3 => ['x' => 35, 'y' => 10],
                ],
                -375.00,
            ],
            // anticlockwise gives positive area
            [[0 => ['x' => 15, 'y' => 40], 1 => ['x' => 10, 'y' => 10], 2 => ['x' => 35, 'y' => 10]], 375.00],
        ];
    }

    /**
     * test for isPointInsidePolygon
     *
     * @param mixed[] $point    x, y coordinates of the point
     * @param mixed[] $polygon  array of points forming the ring
     * @param bool    $isInside output
     *
     * @dataProvider providerForTestIsPointInsidePolygon
     */
    public function testIsPointInsidePolygon(array $point, array $polygon, bool $isInside): void
    {
        $object = GisPolygon::singleton();
        $this->assertEquals($isInside, $object->isPointInsidePolygon($point, $polygon));
    }

    /**
     * data provider for testIsPointInsidePolygon
     *
     * @return array<array{mixed[], mixed[], bool}>
     */
    public static function providerForTestIsPointInsidePolygon(): array
    {
        $ring = [
            0 => ['x' => 35, 'y' => 10],
            1 => ['x' => 10, 'y' => 10],
            2 => ['x' => 15, 'y' => 40],
            3 => ['x' => 35, 'y' => 10],
        ];

        return [
            // point inside the ring
            [['x' => 20, 'y' => 15], $ring, true],
            // point on an edge of the ring
            [['x' => 20, 'y' => 10], $ring, false],
            // point on a vertex of the ring
            [['x' => 10, 'y' => 10], $ring, false],
            // point outside the ring
            [['x' => 5, 'y' => 10], $ring, false],
        ];
    }

    /**
     * test for getPointOnSurface
     *
     * @param mixed[] $ring array of points forming the ring
     *
     * @dataProvider providerForTestGetPointOnSurface
     */
    public function testGetPointOnSurface(array $ring): void
    {
        $object = GisPolygon::singleton();
        $point = $object->getPointOnSurface($ring);
        $this->assertIsArray($point);
        $this->assertTrue($object->isPointInsidePolygon($point, $ring));
    }

    /**
     * data provider for testGetPointOnSurface
     *
     * @return list{list{mixed}, list{mixed}}
     */
    public static function providerForTestGetPointOnSurface(): array
    {
        $temp = self::getData();
        unset($temp['POLYGON'][0]['no_of_points']);
        unset($temp['POLYGON'][1]['no_of_points']);

        return [[$temp['POLYGON'][0]], [$temp['POLYGON'][1]]];
    }

    /**
     * test scaleRow method
     *
     * @param string    $spatial spatial data of a row
     * @param ScaleData $minMax  expected results
     *
     * @dataProvider providerForTestScaleRow
     */
    public function testScaleRow(string $spatial, ScaleData $minMax): void
    {
        $object = GisPolygon::singleton();
        $this->assertEquals($minMax, $object->scaleRow($spatial));
    }

    /**
     * data provider for testScaleRow
     *
     * @return array<array{string, ScaleData}>
     */
    public static function providerForTestScaleRow(): array
    {
        return [
            ['POLYGON((123 0,23 30,17 63,123 0))', new ScaleData(123, 17, 63, 0)],
            ['POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))', new ScaleData(45, 10, 45, 10)],
        ];
    }

    /** @requires extension gd */
    public function testPrepareRowAsPng(): void
    {
        $object = GisPolygon::singleton();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        $this->assertNotNull($image);
        $return = $object->prepareRowAsPng(
            'POLYGON((0 0,100 0,100 100,0 100,0 0),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,60 60))',
            'image',
            [176, 46, 224],
            ['x' => -56, 'y' => -16, 'scale' => 0.94, 'height' => 124],
            $image,
        );
        $this->assertEquals(200, $return->width());
        $this->assertEquals(124, $return->height());

        $fileExpected = $this->testDir . '/polygon-expected.png';
        $fileActual = $this->testDir . '/polygon-actual.png';
        $this->assertTrue($image->png($fileActual));
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string                   $spatial   GIS POLYGON object
     * @param string                   $label     label for the GIS POLYGON object
     * @param int[]                    $color     color for the GIS POLYGON object
     * @param array<string, int|float> $scaleData array containing data related to scaling
     *
     * @dataProvider providerForPrepareRowAsPdf
     */
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        TCPDF $pdf,
    ): void {
        $object = GisPolygon::singleton();
        $return = $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpected = $this->testDir . '/polygon-expected.pdf';
        $fileActual = $this->testDir . '/polygon-actual.pdf';
        $return->Output($fileActual, 'F');
        $this->assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array<array{string, string, int[], array<string, int|float>, TCPDF}>
     */
    public static function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'POLYGON((0 0,100 0,100 100,0 100,0 0),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,60 6'
                . '0))',
                'pdf',
                [176, 46, 224],
                ['x' => -8, 'y' => -32, 'scale' => 1.80, 'height' => 297],

                parent::createEmptyPdf('POLYGON'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string             $spatial   GIS POLYGON object
     * @param string             $label     label for the GIS POLYGON object
     * @param int[]              $color     color for the GIS POLYGON object
     * @param array<string, int> $scaleData array containing data related to scaling
     * @param string             $output    expected output
     *
     * @dataProvider providerForPrepareRowAsSvg
     */
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        array $color,
        array $scaleData,
        string $output,
    ): void {
        $object = GisPolygon::singleton();
        $svg = $object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        $this->assertEquals($output, $svg);
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array<array{string, string, int[], array<string, int>, string}>
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0),(99 12,30 35,25 55,99 12))',
                'svg',
                [176, 46, 224],
                ['x' => 12, 'y' => 69, 'scale' => 2, 'height' => 150],
                '<path d=" M 222, 288 L 22, 228 L 10, 162 Z  M 174, 264 L 36, 218 L 26, 178 Z " name="svg" id="svg12'
                . '34567890" class="polygon vector" stroke="black" stroke-width="0.5" fill="#b02ee0" fill-rule="evenod'
                . 'd" fill-opacity="0.8"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string $spatial GIS POLYGON object
     * @param int    $srid    spatial reference ID
     * @param string $label   label for the GIS POLYGON object
     * @param int[]  $color   color for the GIS POLYGON object
     * @param string $output  expected output
     *
     * @dataProvider providerForPrepareRowAsOl
     */
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        string $output,
    ): void {
        $object = GisPolygon::singleton();
        $ol = $object->prepareRowAsOl($spatial, $srid, $label, $color);
        $this->assertEquals($output, $ol);
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array<array{string, int, string, int[], string}>
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0))',
                4326,
                'Ol',
                [176, 46, 224],
                'var feature = new ol.Feature(new ol.geom.Polygon([[[123,0],[23,30],[17,63],[123,0'
                . ']]]).transform(\'EPSG:4326\', \'EPSG:3857\'));feature.setStyle(new ol.style.Sty'
                . 'le({fill: new ol.style.Fill({"color":[176,46,224,0.8]}),stroke: new ol.style.St'
                . 'roke({"color":[0,0,0],"width":0.5}),text: new ol.style.Text({"text":"Ol"})}));v'
                . 'ectorSource.addFeature(feature);',
            ],
        ];
    }

    /**
     * test case for isOuterRing() method
     *
     * @param array<array<string, int>> $ring coordinates of the points in a ring
     *
     * @dataProvider providerForIsOuterRing
     */
    public function testIsOuterRing(array $ring): void
    {
        $object = GisPolygon::singleton();
        $this->assertTrue($object->isOuterRing($ring));
    }

    /**
     * data provider for testIsOuterRing() test case
     *
     * @return array<array{array<array<string, int>>}>
     */
    public static function providerForIsOuterRing(): array
    {
        return [[[['x' => 0, 'y' => 0], ['x' => 0, 'y' => 1], ['x' => 1, 'y' => 1], ['x' => 1, 'y' => 0]]]];
    }
}
