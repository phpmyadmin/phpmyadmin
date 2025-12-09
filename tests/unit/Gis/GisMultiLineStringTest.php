<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Gis\GisMultiLineString;
use PhpMyAdmin\Image\ImageWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use TCPDF;

#[CoversClass(GisMultiLineString::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class GisMultiLineStringTest extends GisGeomTestCase
{
    /**
     * data provider for testGenerateWkt
     *
     * @return array<array{array<mixed>, int, string, string}>
     */
    public static function providerForTestGenerateWkt(): array
    {
        $temp = [
            [
                'MULTILINESTRING' => [
                    'data_length' => 2,
                    0 => ['data_length' => 2, 0 => ['x' => 5.02, 'y' => 8.45], 1 => ['x' => 6.14, 'y' => 0.15]],
                    1 => ['data_length' => 2, 0 => ['x' => 1.23, 'y' => 4.25], 1 => ['x' => 9.15, 'y' => 0.47]],
                ],
            ],
        ];

        $temp1 = $temp;
        unset($temp1[0]['MULTILINESTRING'][1][1]['y']);

        $temp2 = $temp;
        $temp2[0]['MULTILINESTRING']['data_length'] = 0;

        $temp3 = $temp;
        $temp3[0]['MULTILINESTRING'][1]['data_length'] = 1;

        return [
            [$temp, 0, '', 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))'],
            // if a coordinate is missing, default is empty string
            [$temp1, 0, '', 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 ))'],
            // missing coordinates are replaced with provided values (3rd parameter)
            [$temp1, 0, '0', 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0))'],
            // at least one line should be there
            [$temp2, 0, '', 'MULTILINESTRING((5.02 8.45,6.14 0.15))'],
            // a line should have at least two points
            [$temp3, 0, '0', 'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))'],
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
        $object = new GisMultiLineString();
        self::assertSame($output, $object->generateWkt($gisData, $index, $empty));
    }

    /**
     * test getShape method
     */
    public function testGetShape(): void
    {
        $rowData = [
            'numparts' => 2,
            'parts' => [
                ['points' => [['x' => 5.02, 'y' => 8.45], ['x' => 6.14, 'y' => 0.15]]],
                ['points' => [['x' => 1.23, 'y' => 4.25], ['x' => 9.15, 'y' => 0.47]]],
            ],
        ];

        $object = new GisMultiLineString();
        self::assertSame(
            'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',
            $object->getShape($rowData),
        );
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
        $object = new GisMultiLineString();
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
                "'MULTILINESTRING((5.02 8.45,6.14 0.15),(1.23 4.25,9.15 0.47))',124",
                [
                    'srid' => 124,
                    0 => [
                        'MULTILINESTRING' => [
                            'data_length' => 2,
                            0 => ['data_length' => 2, 0 => ['x' => 5.02,'y' => 8.45], 1 => ['x' => 6.14,'y' => 0.15]],
                            1 => ['data_length' => 2, 0 => ['x' => 1.23,'y' => 4.25], 1 => ['x' => 9.15,'y' => 0.47]],
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
        $object = new GisMultiLineString();
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
            [
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                new Extent(minX: 17, minY: 10, maxX: 178, maxY: 75),
            ],
        ];
    }

    #[RequiresPhpExtension('gd')]
    public function testPrepareRowAsPng(): void
    {
        $object = new GisMultiLineString();
        $image = ImageWrapper::create(200, 124, ['red' => 229, 'green' => 229, 'blue' => 229]);
        self::assertNotNull($image);
        $object->prepareRowAsPng(
            'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
            'image',
            [176, 46, 224],
            new ScaleData(offsetX: 3, offsetY: -16, scale: 1.06, height: 124),
            $image,
        );
        self::assertSame(200, $image->width());
        self::assertSame(124, $image->height());

        $fileExpected = $this->testDir . '/multilinestring-expected.png';
        $fileActual = $this->testDir . '/multilinestring-actual.png';
        self::assertTrue($image->png($fileActual));
        self::assertFileEquals($fileExpected, $fileActual);
    }

    /**
     * test case for prepareRowAsPdf() method
     *
     * @param string    $spatial   GIS MULTILINESTRING object
     * @param string    $label     label for the GIS MULTILINESTRING object
     * @param int[]     $color     color for the GIS MULTILINESTRING object
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
        $object = new GisMultiLineString();
        $object->prepareRowAsPdf($spatial, $label, $color, $scaleData, $pdf);

        $fileExpected = $this->testDir . '/multilinestring-expected.pdf';
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                'pdf',
                [176, 46, 224],
                new ScaleData(offsetX: 4, offsetY: -90, scale: 1.12, height: 297),

                parent::createEmptyPdf('MULTILINESTRING'),
            ],
        ];
    }

    /**
     * test case for prepareRowAsSvg() method
     *
     * @param string    $spatial   GIS MULTILINESTRING object
     * @param string    $label     label for the GIS MULTILINESTRING object
     * @param int[]     $color     color for the GIS MULTILINESTRING object
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
        $object = new GisMultiLineString();
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                'svg',
                [176, 46, 224],
                new ScaleData(offsetX: 12, offsetY: 69, scale: 2, height: 150),
                '<polyline points="48,260 70,242 100,138 " '
                . 'class="linestring vector" fill="none" stroke="#b02ee0" '
                . 'stroke-width="2" data-label="svg"/><polyline points="48,268 10,'
                . '242 332,182 " class="linestring vector" fill="none" '
                . 'stroke="#b02ee0" stroke-width="2" data-label="svg"/>',
            ],
        ];
    }

    /**
     * test case for prepareRowAsOl() method
     *
     * @param string  $spatial  GIS MULTILINESTRING object
     * @param int     $srid     spatial reference ID
     * @param string  $label    label for the GIS MULTILINESTRING object
     * @param int[]   $color    color for the GIS MULTILINESTRING object
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
        $object = new GisMultiLineString();
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
                'MULTILINESTRING((36 14,47 23,62 75),(36 10,17 23,178 53))',
                4326,
                'Ol',
                [176, 46, 224],
                [
                    'geometry' => [
                        'type' => 'MultiLineString',
                        'coordinates' => [
                            [[36.0, 14.0], [47.0, 23.0], [62.0, 75.0]],
                            [[36.0, 10.0], [17.0, 23.0], [178.0, 53.0]],
                        ],
                        'srid' => 4326,
                    ],
                    'style' => ['stroke' => ['color' => [176, 46, 224], 'width' => 2], 'text' => ['text' => 'Ol']],
                ],
            ],
        ];
    }
}
