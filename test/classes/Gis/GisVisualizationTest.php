<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Gis\GisVisualization */
class GisVisualizationTest extends AbstractTestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private DatabaseInterface $dbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $this->dbi;
    }

    /**
     * Scale the data set
     */
    public function testScaleDataSet(): void
    {
        $this->dbi->setVersion(['@@version' => '5.5.0']);
        $gis = GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]);

        $dataSet = $this->callFunction(
            $gis,
            GisVisualization::class,
            'scaleDataSet',
            [
                [
                    ['abc' => null],// The column is nullable
                    ['abc' => 2],// Some impossible test case
                ],
            ],
        );
        $this->assertSame(
            ['scale' => 1, 'x' => -300.0, 'y' => -225.0, 'height' => 450],
            $dataSet,
        );

        $dataSet = $this->callFunction(
            $gis,
            GisVisualization::class,
            'scaleDataSet',
            [
                [
                    ['abc' => null],// The column is nullable
                    ['abc' => 2],// Some impossible test case
                    ['abc' => 'MULTILINESTRING((36 140,47 233,62 75),(36 100,17 233,178 93))'],
                    ['abc' => 'POINT(100 250)'],
                    ['abc' => 'MULTIPOINT(125 50,156 250,178 143,175 80)'],
                ],
            ],
        );
        $this->assertSame(
            ['scale' => 2.1, 'x' => -45.35714285714286, 'y' => 42.85714285714286, 'height' => 450],
            $dataSet,
        );

        // Regression test for bug with 0.0 sentinel values
        $dataSet = $this->callFunction(
            $gis,
            GisVisualization::class,
            'scaleDataSet',
            [
                [
                    ['abc' => 'MULTIPOLYGON(((0 0,0 3,3 3,3 0,0 0),(1 1,1 2,2 2,2 1,1 1)))'],
                    ['abc' => 'MULTIPOLYGON(((10 10,10 13,13 13,13 10,10 10),(11 11,11 12,12 12,12 11,11 11)))'],
                ],
            ],
        );
        $this->assertSame(
            ['scale' => 32.30769230769231, 'x' => -2.7857142857142865, 'y' => -0.4642857142857143, 'height' => 450],
            $dataSet,
        );
    }

    /**
     * Modify the query for an old version
     */
    public function testModifyQueryOld(): void
    {
        $this->dbi->setVersion(['@@version' => '5.5.0']);
        $queryString = $this->callFunction(
            GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]),
            GisVisualization::class,
            'modifySqlQuery',
            [''],
        );

        $this->assertEquals('SELECT ASTEXT(`abc`) AS `abc`, SRID(`abc`) AS `srid` FROM () AS `temp_gis`', $queryString);
    }

    /**
     * Modify the query for an MySQL 8.0 version
     */
    public function testModifyQuery(): void
    {
        $this->dbi->setVersion(['@@version' => '8.0.0']);
        $queryString = $this->callFunction(
            GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]),
            GisVisualization::class,
            'modifySqlQuery',
            [''],
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString,
        );
    }

    /**
     * Modify the query for an MySQL 8.0 version and trim the SQL end character
     */
    public function testModifyQueryTrimSqlEnd(): void
    {
        $this->dbi->setVersion(['@@version' => '8.0.0']);
        $queryString = $this->callFunction(
            GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]),
            GisVisualization::class,
            'modifySqlQuery',
            ['SELECT 1 FROM foo;'],
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM (SELECT 1 FROM foo) AS `temp_gis`',
            $queryString,
        );
    }

    /**
     * Modify the query for an MySQL 8.0 version using a label column
     */
    public function testModifyQueryLabelColumn(): void
    {
        $this->dbi->setVersion(['@@version' => '8.0.0']);
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'spatialColumn' => 'country_geom',
                'labelColumn' => 'country name',
                'width' => 600,
                'height' => 450,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [''],
        );

        $this->assertEquals(
            'SELECT `country name`, ST_ASTEXT(`country_geom`) AS `country_geom`,'
            . ' ST_SRID(`country_geom`) AS `srid` FROM () AS `temp_gis`',
            $queryString,
        );
    }

    /**
     * Modify the query for an MySQL 8.0 version adding a LIMIT statement
     */
    public function testModifyQueryWithLimit(): void
    {
        $this->dbi->setVersion(['@@version' => '8.0.0']);
        $gis = GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]);
        $this->setProperty($gis, GisVisualization::class, 'rows', 10);
        $queryString = $this->callFunction(
            $gis,
            GisVisualization::class,
            'modifySqlQuery',
            [''],
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis` LIMIT 10',
            $queryString,
        );

        $gis = GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]);
        $this->setProperty($gis, GisVisualization::class, 'pos', 10);
        $this->setProperty($gis, GisVisualization::class, 'rows', 15);
        $queryString = $this->callFunction(
            $gis,
            GisVisualization::class,
            'modifySqlQuery',
            [''],
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis` LIMIT 10, 15',
            $queryString,
        );
    }

    /**
     * Modify the query for an MySQL 8.0.1 version
     */
    public function testModifyQueryVersion8(): void
    {
        $this->dbi->setVersion(['@@version' => '8.0.1']);
        $queryString = $this->callFunction(
            GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]),
            GisVisualization::class,
            'modifySqlQuery',
            [''],
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`, \'axis-order=long-lat\') AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString,
        );
    }

    /**
     * Modify the query for a MariaDB 10.4 version
     */
    public function testModifyQueryMariaDB(): void
    {
        $this->dbi->setVersion(['@@version' => '8.0.0-MariaDB']);
        $queryString = $this->callFunction(
            GisVisualization::getByData([], ['spatialColumn' => 'abc', 'width' => 600, 'height' => 450]),
            GisVisualization::class,
            'modifySqlQuery',
            [''],
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString,
        );
    }
}
