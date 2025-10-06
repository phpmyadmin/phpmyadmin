<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\Ds\Extent;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Gis\GisGeometry;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(GisGeometry::class)]
class GisGeometryTest extends AbstractTestCase
{
    protected GisGeometry&MockObject $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = $this->getMockBuilder(GisGeometry::class)
            ->onlyMethods([
                'prepareRowAsSvg',
                'prepareRowAsPng',
                'prepareRowAsPdf',
                'prepareRowAsOl',
                'getExtent',
                'generateWkt',
                'getCoordinateParams',
                'getType',
            ])
            ->getMock();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * tests getCoordinatesExtent method
     *
     * @param string $pointSet Point set
     * @param Extent $expected Expected output extent
     */
    #[DataProvider('providerForTestGetCoordinatesExtent')]
    public function testGetCoordinatesExtent(string $pointSet, Extent $expected): void
    {
        $extent = $this->callFunction(
            $this->object,
            GisGeometry::class,
            'getCoordinatesExtent',
            [$pointSet],
        );
        self::assertEquals($expected, $extent);
    }

    /**
     * data provider for testGetCoordinatesExtent
     *
     * @return array<array{string, Extent}>
     */
    public static function providerForTestGetCoordinatesExtent(): array
    {
        return [
            [
                '12 35,48 75,69 23,25 45,14 53,35 78',
                new Extent(minX: 12, minY: 23, maxX: 69, maxY: 78),
            ],
            [
                '12 35,48 75,69 23,25 45,14 53,35 78',
                new Extent(minX:12, minY: 23, maxX: 69, maxY: 78),
            ],
        ];
    }

    /**
     * tests parseWktAndSrid method
     *
     * @param string                    $value  Geometry data
     * @param array<string, int|string> $output Expected output
     */
    #[DataProvider('providerForTestParseWktAndSrid')]
    public function testParseWktAndSrid(string $value, array $output): void
    {
        self::assertSame(
            $output,
            $this->callFunction(
                $this->object,
                GisGeometry::class,
                'parseWktAndSrid',
                [$value],
            ),
        );
    }

    /**
     * data provider for testParseWktAndSrid
     *
     * @return array<array{string, array<string, int|string>}>
     */
    public static function providerForTestParseWktAndSrid(): array
    {
        return [
            [
                "'MULTIPOINT(125 50,156 25,178 43,175 80)',125",
                ['srid' => 125, 'wkt' => 'MULTIPOINT(125 50,156 25,178 43,175 80)'],
            ],
            [
                'MULTIPOINT(125 50,156 25,178 43,175 80)',
                ['srid' => 0, 'wkt' => 'MULTIPOINT(125 50,156 25,178 43,175 80)'],
            ],
            ['foo', ['srid' => 0, 'wkt' => '']],
        ];
    }

    /**
     * tests extractPointsInternal method
     *
     * @param string         $pointSet  String of comma separated points
     * @param ScaleData|null $scaleData Data related to scaling
     * @param bool           $linear    If true, as a 1D array, else as a 2D array
     * @param int[][]|int[]  $output    Expected output
     */
    #[DataProvider('providerForTestExtractPointsInternal')]
    public function testExtractPointsInternal(
        string $pointSet,
        ScaleData|null $scaleData,
        bool $linear,
        array $output,
    ): void {
        $points = $this->callFunction(
            $this->object,
            GisGeometry::class,
            'extractPointsInternal',
            [$pointSet, $scaleData, $linear],
        );
        self::assertEquals($output, $points);
    }

    /**
     * data provider for testExtractPointsInternal
     *
     * @return array<array{string, ScaleData|null, bool, int[][]|int[]}>
     */
    public static function providerForTestExtractPointsInternal(): array
    {
        return [
            // with no scale data
            ['12 35,48 75,69 23', null, false, [[12, 35], [48, 75], [69, 23]]],
            // with scale data
            [
                '12 35,48 75,69 23',
                new ScaleData(offsetX: 5, offsetY: 5, scale: 2, height: 200),
                false,
                [[14, 140], [86, 60], [128, 164]],
            ],
            // linear output
            ['12 35,48 75,69 23', null, true, [12, 35, 48, 75, 69, 23]],
            // if a single part of a coordinate is empty
            ['12 35,48 75,69 ', null, false, [[12, 35], [48, 75], [0, 0]]],
        ];
    }
}
