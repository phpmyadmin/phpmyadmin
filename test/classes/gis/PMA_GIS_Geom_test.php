<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract parent class for all PMA_GIS_<Geom_type> test classes
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/gis/GIS_Geometry.class.php';

/**
 * Abstract parent class for all PMA_GIS_<Geom_type> test classes
 *
 * @package PhpMyAdmin-test
 */
abstract class PMA_GIS_GeomTest extends PHPUnit_Framework_TestCase
{

    /**
     * test generateParams method
     *
     * @param string $wkt    point in WKT form
     * @param index  $index  index
     * @param array  $params expected output array
     *
     * @dataProvider providerForTestGenerateParams
     * @return void
     */
    public function testGenerateParams($wkt, $index, $params)
    {
        if ($index == null) {
            $this->assertEquals(
                $params,
                $this->object->generateParams($wkt)
            );
        } else {
            $this->assertEquals(
                $params,
                $this->object->generateParams($wkt, $index)
            );
        }
    }

    /**
     * test scaleRow method
     *
     * @param string $spatial spatial data of a row
     * @param array  $min_max expected results
     *
     * @dataProvider providerForTestScaleRow
     * @return void
     */
    public function testScaleRow($spatial, $min_max)
    {
        $this->assertEquals(
            $min_max,
            $this->object->scaleRow($spatial)
        );
    }

    /**
     * Tests whether content is a valid image.
     *
     * @param object $object Image
     *
     * @return void
     */
    public function assertImage($object)
    {
        $this->assertGreaterThan(0, imagesx($object));
    }
}
