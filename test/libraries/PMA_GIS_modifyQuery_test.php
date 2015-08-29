<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_GIS_modifyQuery method
 *
 * @package PhpMyAdmin-test
 */


/*
 * Include to test
 */
//require_once 'libraries/tbl_gis_visualization.lib.php';

/**
 * Tests for PMA_GIS_modifyQuery method
 *
 * @package PhpMyAdmin-test
 */
class PMA_GIS_ModifyQueryTest extends PHPUnit_Framework_TestCase
{
     //@todo: Move this test to GIS_Visualization's
    /**
     * Test PMA_GIS_modifyQuery method
     *
     * @internal     param string $sql_query query to modify
     * @internal     param array  $settings visualization settings
     * @internal     param string $modified_query modified query
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testModifyQuery(/*$sql_query, $settings, $modified_query*/)
    {
        // $this->assertEquals(
        //     PMA_GIS_modifyQuery($sql_query, $settings),
        //     $modified_query
        // );
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * data provider for testModifyQuery
     *
     * @return array data for testModifyQuery
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
