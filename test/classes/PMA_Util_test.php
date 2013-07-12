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
     * Test for analyze Limit Clause
     *
     * @return void
     */
    public function testAnalyzeLimitClause()
    {
        $limit_data = PMA_Util::analyzeLimitClause("limit 2,4");
        $this->assertEquals(
            '2',
            $limit_data['start']
        );
        $this->assertEquals(
            '4',
            $limit_data['length']
        );

        $limit_data = PMA_Util::analyzeLimitClause("limit 3");
        $this->assertEquals(
            '0',
            $limit_data['start']
        );
        $this->assertEquals(
            '3',
            $limit_data['length']
        );
    }

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
            '<select class="pageselector  ajax" name="pma" >',
            PMA_Util::pageselector("pma", 3)
        );
    }

    /**
     * Test version checking
     *
     * @return void
     *
     * @group large
     */
    public function testGetLatestVersion()
    {
        $GLOBALS['cfg']['VersionCheckProxyUrl'] = '';
        $version = PMA_Util::getLatestVersion();
        $this->assertNotEmpty($version->version);
        $this->assertNotEmpty($version->date);
    }
}
