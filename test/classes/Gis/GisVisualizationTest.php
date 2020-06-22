<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Gis\GisVisualization
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisVisualization;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for PhpMyAdmin\Gis\GisVisualization class
 *
 * @package PhpMyAdmin-test
 */
class GisVisualizationTest extends TestCase
{
    /**
     * Call private functions by setting visibility to public.
     *
     * @param string           $name      method name
     * @param array            $params    parameters for the invocation
     * @param GisVisualization $gisObject The GisVisualization instance
     *
     * @return mixed the output from the private method.
     */
    private function _callPrivateFunction(string $name, array $params, GisVisualization $gisObject)
    {
        $class = new ReflectionClass(GisVisualization::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($gisObject, $params);
    }

    /**
     * Modify the query for an old version
     * @return void
     */
    public function testModifyQueryOld(): void
    {
        $queryString = $this->_callPrivateFunction(
            '_modifySqlQuery',
            [
                '',
                0,
                0,
            ],
            GisVisualization::getByData([], [
                'mysqlVersion' => 50500,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ])
        );

        $this->assertEquals(
            'SELECT ASTEXT(`abc`) AS `abc`, SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }

    /**
     * Modify the query for a MySQL 8.0 version
     * @return void
     */
    public function testModifyQuery(): void
    {
        $queryString = $this->_callPrivateFunction(
            '_modifySqlQuery',
            [
                '',
                0,
                0,
            ],
            GisVisualization::getByData([], [
                'mysqlVersion' => 80000,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ])
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }

    /**
     * Modify the query for a MySQL 8.1 version
     * @return void
     */
    public function testModifyQueryVersion8(): void
    {
        $queryString = $this->_callPrivateFunction(
            '_modifySqlQuery',
            [
                '',
                0,
                0,
            ],
            GisVisualization::getByData([], [
                'mysqlVersion' => 80010,
                'spatialColumn' => 'abc',
                'isMariaDB' => false,
            ])
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`, \'axis-order=long-lat\') AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }

    /**
     * Modify the query for a MariaDB 10.4 version
     * @return void
     */
    public function testModifyQueryMariaDB(): void
    {
        $queryString = $this->_callPrivateFunction(
            '_modifySqlQuery',
            [
                '',
                0,
                0,
            ],
            GisVisualization::getByData([], [
                'mysqlVersion' => 100400,
                'spatialColumn' => 'abc',
                'isMariaDB' => true,
            ])
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`) AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }
}
