<?php
namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisVisualization;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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
            ])
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
            ])
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
            ])
        );

        $this->assertEquals(
            'SELECT ST_ASTEXT(`abc`, \'axis-order=long-lat\') AS `abc`, ST_SRID(`abc`) AS `srid` FROM () AS `temp_gis`',
            $queryString
        );
    }
}
