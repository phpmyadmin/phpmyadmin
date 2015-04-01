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

        $limit_data = PMA_Util::analyzeLimitClause("limit 3,2,5");
        $this->assertFalse($limit_data);

        $limit_data = PMA_Util::analyzeLimitClause("limit");
        $this->assertFalse($limit_data);

        $limit_data = PMA_Util::analyzeLimitClause("limit ");
        $this->assertFalse($limit_data);
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
    /*
    public function testGetLatestVersion()
    {
        $GLOBALS['cfg']['ProxyUrl'] = '';
        $GLOBALS['cfg']['VersionCheckProxyUrl'] = '';
        $version = PMA_Util::getLatestVersion();
        $this->assertNotEmpty($version->version);
        $this->assertNotEmpty($version->date);
    }
    */
    /**
     * Test version to int conversion.
     *
     * @param string $version Version string
     * @param int    $numeric Integer matching version
     *
     * @return void
     *
     * @dataProvider dataVersions
     */
    public function testVersionToInt($version, $numeric)
    {
        $this->assertEquals(
            $numeric,
            PMA_Util::versionToInt($version)
        );
    }

    /**
     * Data provider for version parsing
     *
     * @return array with test data
     */
    public function dataVersions()
    {
        return array(
            array('1.0.0', 1000050),
            array('2.0.0.2-dev', 2000002),
            array('3.4.2.1', 3040251),
            array('3.4.2-dev3', 3040203),
            array('3.4.2-dev', 3040200),
            array('3.4.2-pl', 3040260),
            array('3.4.2-pl3', 3040263),
            array('4.4.2-rc22', 4040252),
            array('4.4.2-rc', 4040230),
            array('4.4.22-beta22', 4042242),
            array('4.4.22-beta', 4042220),
            array('4.4.21-alpha22', 4042132),
            array('4.4.20-alpha', 4042010),
            array('4.40.20-alpha-dev', 4402010),
            array('4.4a', 4000050),
            array('4.4.4-test', 4040400),
            array('4.1.0', 4010050),
            array('4.0.1.3', 4000153),
            array('4.1-dev', 4010000),
        );
    }

}
