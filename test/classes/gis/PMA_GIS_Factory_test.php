<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_GIS_Factory
 *
 * @package PhpMyAdmin-test
 */
require_once 'libraries/gis/GIS_Geometry.class.php';
require_once 'libraries/gis/GIS_Linestring.class.php';
require_once 'libraries/gis/GIS_Multilinestring.class.php';
require_once 'libraries/gis/GIS_Point.class.php';
require_once 'libraries/gis/GIS_Multipoint.class.php';
require_once 'libraries/gis/GIS_Polygon.class.php';
require_once 'libraries/gis/GIS_Multipolygon.class.php';
require_once 'libraries/gis/GIS_Geometrycollection.class.php';

/*
 * Include to test
 */
require_once 'libraries/gis/GIS_Factory.class.php';

/**
 * Test class for PMA_GIS_Factory
 *
 * @package PhpMyAdmin-test
 */
class PMA_GIS_FactoryTest extends PHPUnit_Framework_TestCase
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
        $this->assertInstanceOf($geom, PMA_GIS_Factory::factory($type));
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
                'PMA_GIS_Multipolygon'
            ),
            array(
                'POLYGON',
                'PMA_GIS_Polygon'
            ),
            array(
                'MULTILINESTRING',
                'PMA_GIS_Multilinestring'
            ),
            array(
                'LINESTRING',
                'PMA_GIS_Linestring'
            ),
            array(
                'MULTIPOINT',
                'PMA_GIS_Multipoint'
            ),
            array(
                'POINT',
                'PMA_GIS_Point'
            ),
            array(
                'GEOMETRYCOLLECTION',
                'PMA_GIS_Geometrycollection'
            ),
        );
    }
}
?>
