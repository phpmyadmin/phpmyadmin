<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Util class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

/**
 * Test for PMA_Util class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Util_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Test for createGISData
     *
     * @return void
     */
    public function testCreateGISData()
    {
        $this->assertEquals(
            "abc",
            PMA_Util::createGISData("abc")
        );
        $this->assertEquals(
            "GeomFromText('POINT()',10)",
            PMA_Util::createGISData("'POINT()',10")
        );
    }

    /**
     * Test for getGISFunctions
     *
     * @return void
     */
    public function testGetGISFunctions()
    {
        $funcs = PMA_Util::getGISFunctions();
        $this->assertArrayHasKey(
            'Dimension',
            $funcs
        );
        $this->assertArrayHasKey(
            'GeometryType',
            $funcs
        );
        $this->assertArrayHasKey(
            'MBRDisjoint',
            $funcs
        );
    }

    /**
     * Test for Page Selector
     *
     * @return void
     */
    public function testPageSelector()
    {
        $this->assertContains(
            '<select class="pageselector ajax" name="pma" >',
            PMA_Util::pageselector("pma", 3)
        );
    }

    /**
     * Test for isForeignKeyCheck
     *
     * @return void
     */
    public function testIsForeignKeyCheck()
    {
        $GLOBALS['cfg']['DBG'] = array();
        $GLOBALS['cfg']['DBG']['sql'] = false;

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'enable';
        $this->assertEquals(
            true,
            PMA_Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'disable';
        $this->assertEquals(
            false,
            PMA_Util::isForeignKeyCheck()
        );

        $GLOBALS['cfg']['DefaultForeignKeyChecks'] = 'default';
        $this->assertEquals(
            true,
            PMA_Util::isForeignKeyCheck()
        );
    }

}
