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
require_once 'libraries/gis_visualization.lib.php';

class PMA_GIS_modifyQueryTest extends PHPUnit_Framework_TestCase
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
            // select *
            array(
                'SELECT * FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo', 'labelColumn' => 'noo'),
                'SELECT `noo`, ASTEXT(`moo`) AS `moo`, SRID(`moo`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
            // select * with no label column
            array(
                'SELECT * FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo'),
                'SELECT ASTEXT(`moo`) AS `moo`, SRID(`moo`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
            // more columns
            array(
                'SELECT `aaa`, `moo`, `bbb`, `noo` FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo', 'labelColumn' => 'noo'),
                'SELECT `noo`, ASTEXT(`moo`) AS `moo`, SRID(`moo`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
            // no labelColumn defined
            array(
                'SELECT `moo`, `noo` FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo'),
                'SELECT ASTEXT(`moo`) AS `moo`, SRID(`moo`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
            // alias for spatialColumn
            array(
                'SELECT `aaa` AS `moo`, `noo` FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo', 'labelColumn' => 'noo'),
                'SELECT `noo`, ASTEXT(`aaa`) AS `moo`, SRID(`aaa`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
            // alias for labelColumn
            array(
                'SELECT `moo`, `bbb` AS `noo` FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo', 'labelColumn' => 'noo'),
                'SELECT `bbb` AS `noo`, ASTEXT(`moo`) AS `moo`, SRID(`moo`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
            // with database names
            array(
                'SELECT `db`.`moo`, `db`.`noo` FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo', 'labelColumn' => 'noo'),
                'SELECT `db`.`noo`, ASTEXT(`db`.`moo`) AS `moo`, SRID(`db`.`moo`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
            // database names plus alias
            array(
                'SELECT `db`.`aaa` AS `moo`, `noo` FROM `foo` WHERE `bar` = `zoo`',
                array('spatialColumn' => 'moo', 'labelColumn' => 'noo'),
                'SELECT `noo`, ASTEXT(`db`.`aaa`) AS `moo`, SRID(`db`.`aaa`) AS `srid` FROM `foo` WHERE `bar` = `zoo`'
            ),
        );
    }
}
?>
