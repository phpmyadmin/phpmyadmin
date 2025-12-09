<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Gis\GisPoint;
use PhpMyAdmin\Image\ImageWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use TCPDF;

use function file_exists;

#[CoversClass(GisPoint::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class GisPointTest extends GisGeomTestCase
{
    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        return [
            [[['POINT' => ['x' => 5.02, 'y' => 8.45]]], 0, '', 'POINT(5.02 8.45)'],
            [[['POINT' => ['x' => 5.02, 'y' => 8.45]]], 1, '', 'POINT( )'],
            [[['POINT' => ['x' => 5.02]]], 0, '', 'POINT(5.02 )'],
            [[['POINT' => ['y' => 8.45]]], 0, '', 'POINT( 8.45)'],
            [[['POINT' => []]], 0, '', 'POINT( )'],
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
        $object = new GisPoint();
        self::assertSame($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * test getShape method
     *
     * @param array<string, float> $rowData array of GIS data
     * @param string               $shape   expected shape in WKT
     */
    #[DataProvider('providerForTestGetShape')]
    public function testGetShape(array $rowData, string $shape): void
    {
        $object = new GisPoint();
        self::assertSame($shape, $object->getShape($rowData));
    }

    /**
     * data provider for testGetShape
     *
     * @return array<array{array<string, float>, string}>
     */
    public static function providerForTestGetShape(): array
    {
        return [[['x' => 5.02, 'y' => 8.45], 'POINT(5.02 8.45)']];
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
        $object = new GisPoint();
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
                "'POINT(5.02 8.45)',124",
                [
                    'srid' => 124,
                    0 => [
                        'POINT' => [
                            'x' => 5.02,
                            'y' => 8.45,
                        ],
                    ],
                ],
            ],
            [
                '',
                [
                    'srid' => 0,
                    0 => [
                        'POINT' => [
                            'x' => 0.0,
                            'y' => 0.0,
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
        $object = new GisPoint();
        self::assertEquals($extent, $object->getExtent($spatial));
    }

    /**
     * data provider for testGetExtent
     *
     * @return array<array{string, Extent}>
     */
    public static function providerForTestGetExtent(): array
    {
        return [['POINT(12 35)', new Extent(minX: 12, minY: 35, maxX: 12, maxY: 35)]];
    }

    #[RequiresPhpExtension('gd')]
    public function testPrepareRowAsPng(): void
    {
        $object = new GisPoint();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        self::assertNotNull($image);
        $object->prepareRowAsPng(
            'POINT(12 35)',
            'image',
            [176, 46, 224],
            new ScaleData(offsetX: -88, offsetY: -27, scale: 1, height: 124),
            $image,
        );
        self::assertSame(200, $image->width());
        self::assertSame(124, $image->height());

        $fileExpected = $this->testDir . '/point-expected.png';
        $fileActual = $this->testDir . '/point-actual.png';
        self::assertTrue($image->png($fileActual));
        self::assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string    $spatial   GIS POINT object
     * @param string    $label     label for the GIS POINT object
     * @param int[]     $color     color for the GIS POINT object
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
        $object = new GisPoint();
        $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpectedArch = $this->testDir . '/point-expected-' . $this->getArch() . '.pdf';
        $fileExpectedGeneric = $this->testDir . '/point-expected.pdf';
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
                'POINT(12 35)',
                'pdf',
                [176, 46, 224],
                new ScaleData(offsetX: -93, offsetY: -114, scale: 1, height: 297),

                parent::createEmptyPdf('POINT'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string    $spatial   GIS POINT object
     * @param string    $label     label for the GIS POINT object
     * @param int[]     $color     color for the GIS POINT object
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
        $object = new GisPoint();
        $svg = $object->prepareRowAsSvg($spatial, $label, $color, $scaleData);
        self::assertSame($output, $svg);
    }

    /**
     * data provider for prepareRowAsSvg() test case
     *
     * @return array<array{string, string, int[], ScaleData, string}>
     */
    public static function providerForPrepareRowAsSvg(): array
    {
        return [
            [
                'POINT(12 35)',
                'svg',
                [176, 46, 224],
                new ScaleData(offsetX: 12, offsetY: 69, scale: 2, height: 150),
                '',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string                              $spatial  GIS POINT object
     * @param int                                 $srid     spatial reference ID
     * @param string                              $label    label for the GIS POINT object
     * @param int[]                               $color    color for the GIS POINT object
     * @param array<string, array<string, mixed>> $expected
     */
    #[DataProvider('providerForPrepareRowAsOl')]
    public function testPrepareRowAsOl(
        string $spatial,
        int $srid,
        string $label,
        array $color,
        array $expected,
    ): void {
        $object = new GisPoint();
        self::assertSame($expected, $object->prepareRowAsOl($spatial, $srid, $label, $color));
    }

    /**
     * data provider for testPrepareRowAsOl() test case
     *
     * @return array<array{string, int, string, int[], array<string, array<string, mixed>>}>
     */
    public static function providerForPrepareRowAsOl(): array
    {
        return [
            [
                'POINT(12 35)',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'geometry' => ['type' => 'Point', 'coordinates' => [12.0, 35.0], 'srid' => 4326],
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
