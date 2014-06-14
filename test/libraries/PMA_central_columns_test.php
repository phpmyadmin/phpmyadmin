<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for central_columns.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/central_columns.lib.php';

/**
 * tests for central_columns.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_Central_Columns_Test extends PHPUnit_Framework_TestCase
{
    /**
     * prepares environment for tests
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['central_columns'] = 'pma_central_columns';
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['pmaThemeImage'] = 'image';

        //$_SESSION
        $GLOBALS['server'] = 1;
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * Test for PMA_centralColumnsGetParams
     *
     * @return void
     */
    public function testPMACentralColumnsGetParams()
    {
        $this->assertFalse(
            PMA_centralColumnsGetParams()
        );
    }

    /**
     * Test for PMA_getColumnsList
     *
     * @return void
     */
    public function testPMAGetColumnsList()
    {
        $this->assertEquals(
            array(),
            PMA_getColumnsList('phpmyadmin')
        );
    }

    /**
     * Test for PMA_syncUniqueColumns
     *
     * @return void
     */
    public function testPMASyncUniqueColumns()
    {
        $field_select = array("column1");
        $this->assertInstanceOf(
            'PMA_Message', PMA_syncUniqueColumns($field_select)
        );
        $field_select = array("table1");
        $this->assertInstanceOf(
            'PMA_Message', PMA_syncUniqueColumns($field_select, true)
        );
    }

    /**
     * Test for PMA_deleteColumnsFromList
     *
     * @return void
     */
    public function testPMADeleteColumnsFromList()
    {
        $field_select = array("phpmydmin");
        $this->assertInstanceOf(
            'PMA_Message', PMA_deleteColumnsFromList($field_select)
        );
        $this->assertInstanceOf(
            'PMA_Message', PMA_deleteColumnsFromList($field_select, true)
        );
    }

    /**
     * Test for PMA_makeConsistentWithList
     *
     * @return void
     */
    public function testPMAMakeConsistentWithList()
    {
        $this->assertTrue(
            PMA_makeConsistentWithList("phpmyadmin", array())
        );
    }

    /**
     * Test for PMA_updateOneColumn
     *
     * @return void
     */
    public function testPMAUpdateOneColumn()
    {
        $this->assertInstanceOf(
            'PMA_Message', PMA_updateOneColumn(
                "phpmyadmin", "", "", "", "", "", "", "", ""
            )
        );
    }

    /**
     * Test for PMA_getHTMLforTableNavigation
     *
     * @return void
     */
    public function testPMAGetHTMLforTableNavigation()
    {
        $this->assertTag(
            array('tag' => 'table'),
            PMA_getHTMLforTableNavigation(1, 0, 'phpmyadmin')
        );
    }

    /**
     * Test for PMA_getCentralColumnsTableHeader
     *
     * @return void
     */
    public function testPMAGetCentralColumnsTableHeader()
    {
        $this->assertTag(
            array('tag' => 'thead'), PMA_getCentralColumnsTableHeader()
        );
    }

    /**
     * Test for PMA_getCentralColumnsListRaw
     *
     * @return void
     */
    public function testPMAGetCentralColumnsListRaw()
    {
        $this->assertEquals(
            array(),
            PMA_getCentralColumnsListRaw('phpmyadmin', 'pma_central_columns')
        );
    }

}