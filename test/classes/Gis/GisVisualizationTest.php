<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Gis\GisVisualization
 */
class GisVisualizationTest extends AbstractTestCase
{
    /**
     * Scale the data set
     */
    public function testScaleDataSet(): void
    {
        $gis = GisVisualization::getByData([], [
            'mysqlVersion' => 50500,
            'spatialColumn' => 'abc',
            'isMariaDB' => false,
        ]);
        $this->callFunction(
            $gis,
            GisVisualization::class,
            'handleOptions',
            []
        );
        $dataSet = $this->callFunction(
            $gis,
            GisVisualization::class,
            'scaleDataSet',
            [
                [
                    ['abc' => null],// The column is nullable
                    ['abc' => 2],// Some impossible test case
                ],
            ]
        );
        $this->assertSame(
            [
                'scale' => 1,
                'x' => -15.0,
                'y' => -210.0,
                'minX' => 0.0,
                'maxX' => 0.0,
                'minY' => 0.0,
                'maxY' => 0.0,
                'height' => 450,
            ],
            $dataSet
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
            ]
        );
        $this->assertSame(
            [
                'scale' => 2.1,
                'x' => -38.21428571428572,
                'y' => 42.85714285714286,
                'minX' => 17.0,
                'maxX' => 178.0,
                'minY' => 50.0 ,
                'maxY' => 250.0,
                'height' => 450,

            ],
            $dataSet
        );
    }

    /**
     * Modify the query for an old version
     */
    public function testModifyQueryOld(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 50500,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                '',
                0,
                0,
            ]
        );

        $this->assertEquals('SELECT ASTEXT(`abc`) AS `abc`, SRID(`abc`) AS `srid` FROM () AS `temp_gis`', $queryString);
    }

    /**
     * Modify the query for an MySQL 8.0 version
     */
    public function testModifyQuery(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 80000,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                '',
                0,
                0,
            ]
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }

    /**
     * Modify the query for an MySQL 8.0 version and trim the SQL end character
     */
    public function testModifyQueryTrimSqlEnd(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 80000,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                'SELECT 1 FROM foo;',
                0,
                0,
            ]
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM (SELECT 1 FROM foo) AS `temp_gis`',
            $queryString
        );
    }

    /**
     * Modify the query for an MySQL 8.0 version using a label column
     */
    public function testModifyQueryLabelColumn(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 80000,
                'spatialColumn' => 'country_geom',
                'labelColumn' => 'country name',
                'isMariaDB' => false,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                '',
                0,
                0,
            ]
        );

        $this->assertEquals(
            'SELECT `country name`, ST_ASTEXT(`country_geom`) AS `country_geom`,'
            . ' ST_SRID(`country_geom`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }

    /**
     * Modify the query for an MySQL 8.0 version adding a LIMIT statement
     */
    public function testModifyQueryWithLimit(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 80000,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                '',
                10,// 10 rows
                0,
            ]
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis` LIMIT 0, 10',
            $queryString
        );

        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 80000,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                '',
                15,// 15 rows
                10,// position 10
            ]
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis` LIMIT 10, 15',
            $queryString
        );
    }

    /**
     * Modify the query for an MySQL 8.0.1 version
     */
    public function testModifyQueryVersion8(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 80001,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                '',
                0,
                0,
            ]
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`, \'axis-order=long-lat\') AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }

    /**
     * Modify the query for a MariaDB 10.4 version
     */
    public function testModifyQueryMariaDB(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 100400,
                'spatialColumn' => 'abc',
                'isMariaDB' => true,
            ]),
            GisVisualization::class,
            'modifySqlQuery',
            [
                '',
                0,
                0,
            ]
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }
}
