<?php
/**
 * Abstract parent class for all PMA_GIS_<Geom_type> test classes
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/gis/pma_gis_geometry.php';

/**
 * Abstract parent class for all PMA_GIS_<Geom_type> test classes
 */
abstract class PMA_GIS_GeomTest extends PHPUnit_Framework_TestCase
{
    /**
     * test generateWkt method
     *
     * @param array  $gis_data array of GIS data
     * @param int    $index    index
     * @param string $empty    string to be insterted in place of missing values
     * @param string $wkt      expected WKT
     *
     * @return void
     * @dataProvider providerForTestGenerateWkt
     */
    public function testGenerateWkt($gis_data, $index, $empty, $wkt)
    {
        if ($empty == null) {
            $this->assertEquals($this->object->generateWkt($gis_data, $index), $wkt);
        } else {
            $this->assertEquals(
                $this->object->generateWkt($gis_data, $index, $empty),
                $wkt
            );
        }
    }

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
            $this->assertEquals($this->object->generateParams($wkt), $params);
        } else {
            $this->assertEquals(
                $this->object->generateParams($wkt, $index),
                $params
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
        $this->assertEquals($this->object->scaleRow($spatial), $min_max);
    }
}
?>