<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use Generator;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Gis\Ds\ScaleData;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Gis\GisVisualizationSettings;
use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use TCPDF;
use TCPDF_STATIC;
use Throwable;

use function array_map;
use function assert;
use function file_exists;
use function file_put_contents;
use function json_encode;
use function md5;
use function ob_get_clean;
use function ob_start;
use function php_uname;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr_replace;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;
use const PNG_ALL_FILTERS;

#[CoversClass(GisVisualization::class)]
class GisVisualizationTest extends AbstractTestCase
{
    private static string $testDataDir = '';

    /** @psalm-suppress PropertyNotSetInConstructor */
    private DatabaseInterface $dbi;

    public static function setUpBeforeClass(): void
    {
        self::$testDataDir = __DIR__ . '/../../test_data/gis';
        (new ReflectionProperty(TCPDF_STATIC::class, 'tcpdf_version'))->setValue(null, '6.6.2');
    }

    public static function tearDownAfterClass(): void
    {
        $property = new ReflectionProperty(TCPDF_STATIC::class, 'tcpdf_version');
        $property->setValue(null, $property->getDefaultValue());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $this->dbi;
    }

    private function getArch(): string
    {
        $arch = php_uname('m');
        if ($arch === 'x86_64' && PHP_INT_MAX === 2147483647) {
            $arch = 'x86';
        }

        return $arch;
    }

    /** @param list<array{wkt:string|null}> $data */
    #[DataProvider('providerForTestScaleDataSet')]
    public function testScaleDataSet(ScaleData|null $expected, array $data): void
    {
        $vis = GisVisualization::getByData(
            $data,
            new GisVisualizationSettings(width: 200, height: 150, spatialColumn: 'wkt'),
        );
        /** @var ScaleData|null $scaleData */
        $scaleData = (new ReflectionMethod(GisVisualization::class, 'scaleDataSet'))
            ->invoke($vis, $data);

        self::assertEquals($expected, $scaleData);
    }

    /** @return array<string,list{ScaleData|null,list<array{wkt:string|null}>}> */
    public static function providerForTestScaleDataSet(): array
    {
        return [
            'empty' => [null, []],
            'null' => [null, [['wkt' => null]]],
            'invalid and valid' => [
                new ScaleData(scale: 1.0, offsetX: 100.0, offsetY: -75.0),
                [
                    ['wkt' => 'asdf'],
                    ['wkt' => 'POINT(0 0)'],
                ],
            ],
            'Point - multiple' => [
                new ScaleData(scale: 120.0, offsetX: 40.0, offsetY: -135.0),
                [
                    ['wkt' => 'POINT(0 0)'],
                    ['wkt' => 'POINT(1 1)'],
                ],
            ],
            'Point - centered' => [
                new ScaleData(scale: 1.0, offsetX: 100.0, offsetY: -75.0),
                [['wkt' => 'POINT(0 0)']],
            ],
            'Linestring - vertically centered' => [
                new ScaleData(scale: 0.1, offsetX: 0.0, offsetY: -76.0),
                [['wkt' => 'LINESTRING(150 10,1850 10)']],
            ],
            'Polygon - empty space at the top and bottom' => [
                new ScaleData(scale: 17.0, offsetX: -155.0, offsetY: -75.0),
                [['wkt' => 'POLYGON((10 -1,20 -1,20 1,10 1,10 -1))']],
            ],
            'MultiPoint - horizontally centered' => [
                new ScaleData(scale: 10.0, offsetX: -99900.0, offsetY: -135.0),
                [['wkt' => 'MULTIPOINT(10000 0,10000 12)']],
            ],
            'MultiLineString - fitting exactly' => [
                new ScaleData(scale: 1.0, offsetX: 0.0, offsetY: -150.0),
                [['wkt' => 'MULTILINESTRING((15 15,100 100),(185 135,100 50))']],
            ],
            'MultiPolygon - fitting exactly' => [
                new ScaleData(scale: 60.0, offsetX: 40.0, offsetY: -75.0),
                [['wkt' => 'MULTIPOLYGON(((0 0,1 1,0 1,0 0)),((1 -1,2 -1,2 0,1 -1)))']],
            ],
            'GeometryCollection - empty space at either side' => [
                new ScaleData(scale: 6.0, offsetX: 1000.0, offsetY: -1335.0),
                [['wkt' => 'GEOMETRYCOLLECTION(MULTIPOINT(-149 201,-151 219),LINESTRING(-150 200,-150 220))']],
            ],
        ];
    }

    /** @param array{version:string,sql:string,spatialColumn:string,labelColumn?:string,rows?:int,pos?:int} $config */
    #[DataProvider('providerForTestModifyQuery')]
    public function testModifyQuery(string $expected, array $config): void
    {
        $this->dbi->setVersion(['@@version' => $config['version']]);

        $vis = new ReflectionClass(GisVisualization::class);
        $obj = $vis->newInstanceWithoutConstructor();
        $vis->getProperty('pos')->setValue($obj, $config['pos'] ?? 0);
        $vis->getProperty('rows')->setValue($obj, $config['rows'] ?? 0);
        $vis->getProperty('spatialColumn')->setValue($obj, $config['spatialColumn']);
        $vis->getProperty('labelColumn')->setValue($obj, $config['labelColumn'] ?? '');

        $queryString = $vis->getMethod('modifySqlQuery')->invoke($obj, $config['sql']);

        self::assertSame($expected, $queryString);
    }

    /**
     * @return Generator<
     *   string,
     *   list{
     *     string,
     *     array{
     *       version: string,
     *       sql: string,
     *       spatialColumn: string,
     *       labelColumn?: string,
     *       rows?: int,
     *       pos?: int,
     *     }
     *   }
     * >
     */
    public static function providerForTestModifyQuery(): Generator
    {
        yield 'Modify the query for an old version' => [
            'SELECT ASTEXT(`abc`) AS `abc`, SRID(`abc`) AS `srid` FROM (SELECT POINT(0, 0) AS abc) AS `temp_gis`',
            [
                'version' => '5.5.0',
                'sql' => 'SELECT POINT(0, 0) AS abc',
                'spatialColumn' => 'abc',
            ],
        ];

        yield 'Modify the query for an MySQL 8.0 version' => [
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid`'
            . ' FROM (SELECT POINT(0, 0) AS abc) AS `temp_gis`',
            [
                'version' => '8.0.0',
                'spatialColumn' => 'abc',
                'sql' => 'SELECT POINT(0, 0) AS abc',
            ],
        ];

        yield 'Modify the query for an MySQL 8.0 version and trim the SQL end character' => [
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM (SELECT 1 FROM foo) AS `temp_gis`',
            [
                'version' => '8.0.0',
                'spatialColumn' => 'abc',
                'sql' => 'SELECT 1 FROM foo;',
            ],
        ];

        yield 'Modify the query for an MySQL 8.0 version using a label column' => [
            'SELECT `country name`, ST_ASTEXT(`country_geom`) AS `country_geom`,'
            . ' ST_SRID(`country_geom`) AS `srid`'
            . " FROM (SELECT POINT(0, 0) AS country_geom, 'country name') AS `temp_gis`",
            [
                'version' => '8.0.0',
                'spatialColumn' => 'country_geom',
                'labelColumn' => 'country name',
                'sql' => "SELECT POINT(0, 0) AS country_geom, 'country name'",
            ],
        ];

        yield 'Modify the query for an MySQL 8.0 version adding a LIMIT statement' => [
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid`'
            . ' FROM (SELECT POINT(0, 0) AS abc) AS `temp_gis` LIMIT 10',
            [
                'version' => '8.0.0',
                'spatialColumn' => 'abc',
                'sql' => 'SELECT POINT(0, 0) AS abc',
                'rows' => 10,
            ],
        ];

        yield 'Modify the query for an MySQL 8.0 version adding a LIMIT statement with offset' => [
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid`'
            . ' FROM (SELECT POINT(0, 0) AS abc) AS `temp_gis` LIMIT 10, 15',
            [
                'version' => '8.0.0',
                'spatialColumn' => 'abc',
                'sql' => 'SELECT POINT(0, 0) AS abc',
                'rows' => 15,
                'pos' => 10,
            ],
        ];

        yield 'Modify the query for an MySQL 8.0.1 version' => [
            'SELECT ST_ASTEXT(`abc`, \'axis-order=long-lat\') AS `abc`, ST_SRID(`abc`) AS `srid`'
            . ' FROM (SELECT POINT(0, 0) AS abc) AS `temp_gis`',
            [
                'version' => '8.0.1',
                'spatialColumn' => 'abc',
                'sql' => 'SELECT POINT(0, 0) AS abc',
            ],
        ];

        yield 'Modify the query for a MariaDB 10.4 version' => [
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid`'
            . ' FROM (SELECT POINT(0, 0) AS abc) AS `temp_gis`',
            [
                'version' => '8.0.0-MariaDB',
                'spatialColumn' => 'abc',
                'sql' => 'SELECT POINT(0, 0) AS abc',
            ],
        ];
    }

    /** @return array<string,list{string,list<array{label:string,wkt:string,srid:int|null}>}> */
    public static function providerTestGisData(): array
    {
        return [
            'empty' => ['empty', []],
            'Geometries' => [
                'Geometries',
                [
                    [
                        'label' => 'Point',
                        'wkt' => 'POINT(85 0)',
                        'srid' => null,
                    ],
                    [
                        'label' => 'LineString',
                        'wkt' => 'LINESTRING(10 -10,0 -60,50 -50)',
                        'srid' => null,
                    ],
                    [
                        'label' => 'Polygon',
                        'wkt' => 'POLYGON((0 60,0 10,50 60,0 60),(7 47,7 27,16 37,7 47),(13 53,33 53,23 44,13 53))',
                        'srid' => null,
                    ],
                    [
                        'label' => 'MultiPoint',
                        'wkt' => 'MULTIPOINT(10 0,160 0)',
                        'srid' => null,
                    ],
                    [
                        'label' => 'MultiLineString',
                        'wkt' => 'MULTILINESTRING((120 60,165 60,120 50),(170 10,170 55,160 10))',
                        'srid' => null,
                    ],
                    [
                        'label' => 'MultiPolygon',
                        'wkt' => 'MULTIPOLYGON(((170 -60,170 -10,120 -60,170 -60),(160 -50,160 -35,145 -50,160 -50)),'
                            . '((160 -10,110 -10,110 -60,160 -10),(135 -20,120 -20,120 -35,135 -20)))',
                        'srid' => null,
                    ],
                ],
            ],
            'GeometryCollection' => [
                'GeometryCollection',
                [
                    [
                        'label' => 'GeometryCollection',
                        'wkt' => 'GEOMETRYCOLLECTION('
                        . 'POINT(85 0),'
                        . 'LINESTRING(10 -10,0 -60,50 -50),'
                        . 'POLYGON((0 60,0 10,50 60,0 60),(7 47,7 27,16 37,7 47),(13 53,33 53,23 44,13 53)),'
                        . 'MULTIPOINT(10 0,160 0),'
                        . 'MULTILINESTRING((120 60,165 60,120 50),(170 10,170 55,160 10)),'
                        . 'MULTIPOLYGON(((170 -60,170 -10,120 -60,170 -60),(160 -50,160 -35,145 -50,160 -50)),'
                        . '((160 -10,110 -10,110 -60,160 -10),(135 -20,120 -20,120 -35,135 -20))))',
                        'srid' => null,
                    ],
                ],
            ],
        ];
    }

    /** @return Generator<string,list{string,list<array{label:string,wkt:string,srid:int|null}>}> */
    public static function providerTestGisDataOl(): Generator
    {
        foreach (self::providerTestGisData() as $case) {
            yield $case[0] . '-null' => [$case[0] . '-null', $case[1]];

            if ($case[1] === []) {
                continue;
            }

            yield $case[0] . '-0' => [
                $case[0] . '-0',
                array_map(
                    static fn (array $geom) => ['label' => $geom['label'], 'wkt' => $geom['wkt'], 'srid' => 0],
                    $case[1],
                ),
            ];

            yield $case[0] . '-4326' => [
                $case[0] . '-4326',
                array_map(
                    static fn (array $geom) => ['label' => $geom['label'], 'wkt' => $geom['wkt'], 'srid' => 4326],
                    $case[1],
                ),
            ];
        }
    }

    /** @param list<array{label:string,wkt:string,srid:int|null}> $data */
    #[DataProvider('providerTestGisDataOl')]
    public function testOl(string $name, array $data): void
    {
        $vis = GisVisualization::getByData(
            $data,
            new GisVisualizationSettings(width: 200, height: 150, spatialColumn: 'wkt'),
        );
        $ol = $vis->asOl();

        $this->assertSameOrSaveNewVersion(
            'ol-' . $name,
            'json',
            json_encode($ol, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );
    }

    /** @param list<array{label:string,wkt:string,srid:int|null}> $data */
    #[DataProvider('providerTestGisData')]
    public function testSvg(string $name, array $data): void
    {
        $vis = GisVisualization::getByData(
            $data,
            new GisVisualizationSettings(width: 200, height: 150, spatialColumn: 'wkt', labelColumn: 'label'),
        );
        $svg = $vis->asSVG();

        $this->assertSameOrSaveNewVersion($name, 'svg', str_replace('><', ">\n<", $svg));
    }

    /** @param list<array{label:string,wkt:string,srid:int|null}> $data */
    #[DataProvider('providerTestGisData')]
    public function testPdf(string $name, array $data): void
    {
        $visualization = GisVisualization::getByData(
            $data,
            new GisVisualizationSettings(width: 560, height: 420, spatialColumn: 'wkt', labelColumn: 'label'),
        );
        $vis = new ReflectionClass($visualization);
        /** @var TCPDF $pdf */
        $pdf = $vis->getMethod('createEmptyPdf')->invoke($visualization, 'A4');
        $pdf->setDocCreationTimestamp(1700000000);
        $pdf->setDocModificationTimestamp(1700000000);
        $pdf->setCompression(false);
        (new ReflectionProperty($pdf, 'file_id'))->setValue($pdf, md5($name));

        $vis->getMethod('prepareDataSet')->invoke($visualization, $data, 'pdf', $pdf);
        $pdfBlob = $pdf->Output(dest: 'S');

        $this->assertSameOrSaveNewVersion($name, 'pdf', $pdfBlob);
    }

    /** @param list<array{label:string,wkt:string,srid:int|null}> $data */
    #[RequiresPhpExtension('gd')]
    #[DataProvider('providerTestGisData')]
    public function testPng(string $name, array $data): void
    {
        $vis = GisVisualization::getByData(
            $data,
            new GisVisualizationSettings(width: 200, height: 150, spatialColumn: 'wkt', labelColumn: 'label'),
        );
        /** @var ImageWrapper $image */
        $image = (new ReflectionMethod($vis, 'png'))->invoke($vis);
        ob_start();
        $image->png(null, 9, PNG_ALL_FILTERS);
        $blob = ob_get_clean();
        assert($blob !== false);

        $this->assertSameOrSaveNewVersion($name, 'png', $blob);
    }

    private function assertSameOrSaveNewVersion(string $name, string $extension, string $content): void
    {
        $name = strtolower($name);
        $fileExpectedArch = self::$testDataDir . '/' . $name . '-expected-' . $this->getArch() . '.' . $extension;
        $fileExpectedGeneric = self::$testDataDir . '/' . $name . '-expected.' . $extension;
        $fileExpected = file_exists($fileExpectedArch) ? $fileExpectedArch : $fileExpectedGeneric;
        try {
            self::assertStringEqualsFile($fileExpected, $content);
        } catch (Throwable $e) {
            $pos = strrpos($fileExpected, 'expected');
            assert($pos !== false);
            file_put_contents(substr_replace($fileExpected, 'actual', $pos, 8), $content);

            throw $e;
        }
    }
}
