<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisVisualization;
use PhpMyAdmin\Tests\AbstractTestCase;

class GisVisualizationTest extends AbstractTestCase
{
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
