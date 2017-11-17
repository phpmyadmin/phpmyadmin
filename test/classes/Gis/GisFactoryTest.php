<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Gis\GisFactory
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Gis;

use PhpMyAdmin\Gis\GisFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test class for PhpMyAdmin\Gis\GisFactory
 *
 * @package PhpMyAdmin-test
 */
class GisFactoryTest extends TestCase
{

    /**
     * Test factory method
     *
     * @param string $type geometry type
     * @param object $geom geometry object
     *
     * @dataProvider providerForTestFactory
     * @return void
     */
    public function testFactory($type, $geom)
    {
        $this->assertInstanceOf($geom, GisFactory::factory($type));
    }

    /**
     * data provider for testFactory
     *
     * @return data for testFactory
     */
    public function providerForTestFactory()
    {
        return array(
            array(
                'MULTIPOLYGON',
                'PhpMyAdmin\Gis\GisMultiPolygon'
            ),
            array(
                'POLYGON',
                'PhpMyAdmin\Gis\GisPolygon'
            ),
            array(
                'MULTILINESTRING',
                'PhpMyAdmin\Gis\GisMultiLineString'
            ),
            array(
                'LINESTRING',
                'PhpMyAdmin\Gis\GisLineString'
            ),
            array(
                'MULTIPOINT',
                'PhpMyAdmin\Gis\GisMultiPoint'
            ),
            array(
                'POINT',
                'PhpMyAdmin\Gis\GisPoint'
            ),
            array(
                'GEOMETRYCOLLECTION',
                'PhpMyAdmin\Gis\GisGeometryCollection'
            ),
        );
    }
}
