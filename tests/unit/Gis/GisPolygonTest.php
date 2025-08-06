<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Gis\GisPolygon;
use PhpMyAdmin\Image\ImageWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use TCPDF;

#[CoversClass(GisPolygon::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
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
        $object = GisPolygon::singleton();
        self::assertSame($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [self::getData()];

        $temp1 = $temp;
        unset($temp1[0]['POLYGON'][1][3]['y']);

        $temp2 = $temp;
        $temp2[0]['POLYGON']['data_length'] = 0;

        $temp3 = $temp;
        $temp3[0]['POLYGON'][1]['data_length'] = 3;

        return [
            [$temp, 0, '', 'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))'],
            // values at undefined index
            [$temp, 1, '', 'POLYGON(( , , , ))'],
            // if a coordinate is missing, default is empty string
            [$temp1, 0, '', 'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 ))'],
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
     */
    #[DataProvider('providerForTestGenerateParams')]
    public function testGenerateParams(string $wkt, array $params): void
    {
        $object = GisPolygon::singleton();
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
                '\'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30))\',124',
                ['srid' => 124, 0 => self::getData()],
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
        $object = GisPolygon::singleton();
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
            ['POLYGON((123 0,23 30,17 63,123 0))', new Extent(minX: 17, minY: 0, maxX: 123, maxY: 63)],
            [
                'POLYGON((35 10,10 20,15 40,45 45,35 10),(20 30,35 32,30 20,20 30)))',
                new Extent(minX: 10, minY: 10, maxX: 45, maxY: 45),
            ],
        ];
    }

    #[RequiresPhpExtension('gd')]
    public function testPrepareRowAsPng(): void
    {
        $object = GisPolygon::singleton();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        self::assertNotNull($image);
        $object->prepareRowAsPng(
            'POLYGON((0 0,100 0,100 100,0 100,0 0),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,60 60))',
            'image',
            [176, 46, 224],
            new ScaleData(offsetX: -56, offsetY: -16, scale: 0.94, height: 124),
            $image,
        );
        self::assertSame(200, $image->width());
        self::assertSame(124, $image->height());

        $fileExpected = $this->testDir . '/polygon-expected.png';
        $fileActual = $this->testDir . '/polygon-actual.png';
        self::assertTrue($image->png($fileActual));
        self::assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string    $spatial   GIS POLYGON object
     * @param string    $label     label for the GIS POLYGON object
     * @param int[]     $color     color for the GIS POLYGON object
     * @param ScaleData $scaleData array containing data related to scaling
     */
    #[DataProvider('providerForPrepareRowAsPdf')]
    public function testPrepareRowAsPdf(
        string $spatial,
        string $label,
        array $color,
        ScaleData $scaleData,
        TCPDF $pdf,
    ): void {
        $object = GisPolygon::singleton();
        $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpected = $this->testDir . '/polygon-expected.pdf';
        self::assertStringEqualsFile($fileExpected, $pdf->Output(dest: 'S'));
    }

    /**
     * data provider for testPrepareRowAsPdf() test case
     *
     * @return array<array{string, string, int[], ScaleData, TCPDF}>
     */
    public static function providerForPrepareRowAsPdf(): array
    {
        return [
            [
                'POLYGON((0 0,100 0,100 100,0 100,0 0),(10 10,10 40,40 40,40 10,10 10),(60 60,90 60,90 90,60 90,60 6'
                . '0))',
                'pdf',
                [176, 46, 224],
                new ScaleData(offsetX: -8, offsetY: -32, scale: 1.80, height: 297),

                parent::createEmptyPdf('POLYGON'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string    $spatial   GIS POLYGON object
     * @param string    $label     label for the GIS POLYGON object
     * @param int[]     $color     color for the GIS POLYGON object
     * @param ScaleData $scaleData array containing data related to scaling
     * @param string    $output    expected output
     */
    #[DataProvider('providerForPrepareRowAsSvg')]
    public function testPrepareRowAsSvg(
        string $spatial,
        string $label,
        array $color,
        ScaleData $scaleData,
        string $output,
    ): void {
        $object = GisPolygon::singleton();
        $svg = $object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        self::assertSame($output, $svg);
    }

    /**
     * data provider for testPrepareRowAsSvg() test case
     *
     * @return array<array{string, string, int[], ScaleData, string}>
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0),(99 12,30 35,25 55,99 12))',
                'svg',
                [176, 46, 224],
                new ScaleData(offsetX: 12, offsetY: 69, scale: 2, height: 150),
                '<path d="M222,288L22,228L10,162ZM174,264L36,218L26,178Z"'
                . ' class="polygon vector" stroke="black" stroke-width="0.5" fill="#b02ee0" fill-rule="evenod'
                . 'd" fill-opacity="0.8" data-label="svg"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string  $spatial  GIS POLYGON object
     * @param int     $srid     spatial reference ID
     * @param string  $label    label for the GIS POLYGON object
     * @param int[]   $color    color for the GIS POLYGON object
     * @param mixed[] $expected
     */
    #[DataProvider('providerForPrepareRowAsOl')]
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        array $expected,
    ): void {
        $object = GisPolygon::singleton();
        self::assertSame($expected, $object->prepareRowAsOl($spatial, $srid, $label, $color));
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array<array{string, int, string, int[], mixed[]}>
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'POLYGON((123 0,23 30,17 63,123 0))',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => [[[123.0, 0.0], [23.0, 30.0], [17.0, 63.0], [123.0, 0.0]]],
                        'srid' => 4326,
                    ],
                    'style' => [
                        'fill' => ['color' => [176, 46, 224, 0.8]],
                        'stroke' => ['color' => [0, 0, 0], 'width' => 0.5],
                        'text' => ['text' => 'Ol'],
                    ],
                ],
            ],
        ];
    }
}
