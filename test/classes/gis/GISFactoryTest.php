<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA\libraries\gis\GISFactory
 *
 * @package PhpMyAdmin-test
 */
use PMA\libraries\gis\GISFactory;

/**
 * Test class for PMA\libraries\gis\GISFactory
 *
 * @package PhpMyAdmin-test
 */
class GISFactoryTest extends PHPUnit_Framework_TestCase
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
        $this->assertInstanceOf($geom, GISFactory::factory($type));
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
                'PMA\libraries\gis\GISMultipolygon'
            ),
            array(
                'POLYGON',
                'PMA\libraries\gis\GISPolygon'
            ),
            array(
                'MULTILINESTRING',
                'PMA\libraries\gis\GISMultilinestring'
            ),
            array(
                'LINESTRING',
                'PMA\libraries\gis\GISLinestring'
            ),
            array(
                'MULTIPOINT',
                'PMA\libraries\gis\GISMultipoint'
            ),
            array(
                'POINT',
                'PMA\libraries\gis\GISPoint'
            ),
            array(
                'GEOMETRYCOLLECTION',
                'PMA\libraries\gis\GISGeometrycollection'
            ),
        );
    }
}
