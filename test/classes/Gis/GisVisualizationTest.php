<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Tests\AbstractTestCase;

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

        $this->assertEquals(
            'SELECT ASTEXT(`abc`) AS `abc`, SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
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
     * Modify the query for an MySQL 8.1 version
     */
    public function testModifyQueryVersion8(): void
    {
        $queryString = $this->callFunction(
            GisVisualization::getByData([], [
                'mysqlVersion' => 80010,
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
