<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Gis\GisMultiPoint;
use PhpMyAdmin\Image\ImageWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use TCPDF;

use function file_exists;

#[CoversClass(GisMultiPoint::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class GisMultiPointTest extends GisGeomTestCase
{
    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $gisData1 = [
            [
                'MULTIPOINT' => [
                    'data_length' => 2,
                    0 => ['x' => 5.02, 'y' => 8.45],
                    1 => ['x' => 1.56, 'y' => 4.36],
                ],
            ],
        ];

        $gisData2 = $gisData1;
        $gisData2[0]['MULTIPOINT']['data_length'] = -1;

        return [
            [$gisData1, 0, '', 'MULTIPOINT(5.02 8.45,1.56 4.36)'],
            [$gisData2, 0, '', 'MULTIPOINT(5.02 8.45)'],
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
        $object = GisMultiPoint::singleton();
        self::assertSame($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * test getShape method
     */
    public function testGetShape(): void
    {
        $gisData = ['numpoints' => 2, 'points' => [['x' => 5.02, 'y' => 8.45], ['x' => 6.14, 'y' => 0.15]]];

        $object = GisMultiPoint::singleton();
        self::assertSame('MULTIPOINT(5.02 8.45,6.14 0.15)', $object->getShape($gisData));
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
        $object = GisMultiPoint::singleton();
        self::assertSame($params, $object->generateParams($wkt));
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
                "'MULTIPOINT(5.02 8.45,6.14 0.15)',124",
                [
                    'srid' => 124,
                    0 => [
                        'MULTIPOINT' => [
                            'data_length' => 2,
                            0 => ['x' => 5.02, 'y' => 8.45],
                            1 => ['x' => 6.14, 'y' => 0.15],
                        ],
                    ],
                ],
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
        $object = GisMultiPoint::singleton();
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
            ['MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)', new Extent(minX: 12, minY: 23, maxX: 69, maxY: 78)],
        ];
    }

    #[RequiresPhpExtension('gd')]
    public function testPrepareRowAsPng(): void
    {
        $object = GisMultiPoint::singleton();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        self::assertNotNull($image);
        $object->prepareRowAsPng(
            'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
            'image',
            [176, 46, 224],
            new ScaleData(offsetX: -18, offsetY: 14, scale: 1.71, height: 124),
            $image,
        );
        self::assertSame(200, $image->width());
        self::assertSame(124, $image->height());

        $fileExpected = $this->testDir . '/multipoint-expected.png';
        $fileActual = $this->testDir . '/multipoint-actual.png';
        self::assertTrue($image->png($fileActual));
        self::assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string    $spatial   GIS MULTIPOINT object
     * @param string    $label     label for the GIS MULTIPOINT object
     * @param int[]     $color     color for the GIS MULTIPOINT object
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
        $object = GisMultiPoint::singleton();
        $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpectedArch = $this->testDir . '/multipoint-expected-' . $this->getArch() . '.pdf';
        $fileExpectedGeneric = $this->testDir . '/multipoint-expected.pdf';
        $fileExpected = file_exists($fileExpectedArch) ? $fileExpectedArch : $fileExpectedGeneric;
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'pdf',
                [176, 46, 224],
                new ScaleData(offsetX: 7, offsetY: 3, scale: 3.16, height: 297),

                parent::createEmptyPdf('MULTIPOINT'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string    $spatial   GIS MULTIPOINT object
     * @param string    $label     label for the GIS MULTIPOINT object
     * @param int[]     $color     color for the GIS MULTIPOINT object
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
        $object = GisMultiPoint::singleton();
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                'svg',
                [176, 46, 224],
                new ScaleData(offsetX: 12, offsetY: 69, scale: 2, height: 150),
                '<circle cx="72" cy="138" r="3" class="multipoint '
                . 'vector" fill="white" stroke="#b02ee0" stroke-width="2" data-label="svg"'
                . '/><circle cx="114" cy="242" r="3" class="mult'
                . 'ipoint vector" fill="white" stroke="#b02ee0" stroke-width="2" data-label="svg"'
                . '/><circle cx="26" cy="198" r="3" class='
                . '"multipoint vector" fill="white" stroke="#b02ee0" stroke-width='
                . '"2" data-label="svg"/><circle cx="4" cy="182" r="3" '
                . 'class="multipoint vector" fill="white" stroke="#b02ee0" stroke-'
                . 'width="2" data-label="svg"/><circle cx="46" cy="132" r="3" class="multipoint vector"'
                . ' fill="white" stroke="#b02ee0" stroke-width="2" data-label="svg"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string  $spatial  GIS MULTIPOINT object
     * @param int     $srid     spatial reference ID
     * @param string  $label    label for the GIS MULTIPOINT object
     * @param int[]   $color    color for the GIS MULTIPOINT object
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
        $object = GisMultiPoint::singleton();
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
                'MULTIPOINT(12 35,48 75,69 23,25 45,14 53,35 78)',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'geometry' => [
                        'type' => 'MultiPoint',
                        'coordinates' => [
                            [12.0, 35.0],
                            [48.0, 75.0],
                            [69.0, 23.0],
                            [25.0, 45.0],
                            [14.0, 53.0],
                            [35.0, 78.0],
                        ],
                        'srid' => 4326,
                    ],
                    'style' => [
                        'circle' => [
                            'fill' => ['color' => 'white'],
                            'stroke' => ['color' => [176, 46, 224], 'width' => 2],
                            'radius' => 3,
                        ],
                        'text' => ['text' => 'Ol', 'offsetY' => -9],
                    ],
                ],
            ],
        ];
    }
}
