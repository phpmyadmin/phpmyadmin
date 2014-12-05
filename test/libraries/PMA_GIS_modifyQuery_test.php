<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_GIS_modifyQuery method
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/Util.class.php';
/*
 * Include to test
 */
require_once 'libraries/tbl_gis_visualization.lib.php';

/**
 * Tests for PMA_GIS_modifyQuery method
 *
 * @package PhpMyAdmin-test
 */
class PMA_GIS_ModifyQueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test PMA_GIS_modifyQuery method
     *
     * @param string $sql_query      query to modify
     * @param array  $settings       visualization settings
     * @param string $modified_query modified query
     *
     * @dataProvider provider
     * @return void
     */
    public function testModifyQuery($sql_query, $settings, $modified_query)
    {
        $this->assertEquals(
            PMA_GIS_modifyQuery($sql_query, $settings),
            $modified_query
        );
    }

    /**
     * data provider for testModifyQuery
     *
     * @return data for testModifyQuery
     */
    public function provider()
    {
        return array(
            // with label column
            array(
                "SELECT * FROM `foo` WHERE `bar` = `zoo`",
                array('spatialColumn' => 'moo', 'labelColumn' => 'noo'),
                "SELECT `noo`, ASTEXT(`moo`) AS `moo`, SRID(`moo`) AS `srid` "
                    . "FROM (SELECT * FROM `foo` WHERE `bar` = `zoo`) AS `temp_gis`"
            ),
            // with no label column
            array(
                "SELECT * FROM `foo` WHERE `bar` = `zoo`",
                array('spatialColumn' => 'moo'),
                "SELECT ASTEXT(`moo`) AS `moo`, SRID(`moo`) AS `srid` "
                    . "FROM (SELECT * FROM `foo` WHERE `bar` = `zoo`) AS `temp_gis`"
            ),
            // with spatial column generated on the fly
            array(
                "SELECT name, PointFromText( Concat( 'POINT (', geo_lat, ' ', geo_lon, ')' ) ) AS coordinates FROM users",
                array('spatialColumn' => 'coordinates', 'labelColumn' => 'name'),
                "SELECT `name`, ASTEXT(`coordinates`) AS `coordinates`, SRID(`coordinates`) AS `srid` "
                    . "FROM (SELECT name, PointFromText( Concat( 'POINT (', geo_lat, ' ', geo_lon, ')' ) ) AS coordinates FROM users) AS `temp_gis`"
            ),
        );
    }
}
?>
