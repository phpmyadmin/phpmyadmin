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
$GLOBALS['server'] = 1;
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/tbl_columns_definition_form.lib.php';
require_once 'libraries/Types.class.php';
require_once 'libraries/mysql_charsets.inc.php';
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
        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['central_columns'] = 'pma_central_columns';
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['cfg']['CharEditing'] = '';

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
     * Test for PMA_getCentralColumnsCount
     *
     * @return void
     */
    function testPMAGetCentralColumnsCount()
    {
        $this->assertEquals(
            0,
            PMA_getCentralColumnsCount('phpmyadmin')
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
        $result = PMA_getHTMLforTableNavigation(0, 0, 'phpmyadmin');
        $this->assertTag(
            array('tag' => 'table'),
            $result
        );
        $this->assertContains(
            __('Search this table'),
            $result
        );
        $result_1 = PMA_getHTMLforTableNavigation(25, 10, 'phpmyadmin');
        $this->assertContains(
            '<form action="db_central_columns.php" method="post">'
            . PMA_URL_getHiddenInputs(
                'phpmyadmin'
            ),
            $result_1
        );
        $this->assertContains(
            '<input type="submit" name="navig"'
            . ' class="ajax" '
            . 'value="&lt" />',
            $result_1
        );
        $this->assertContains(
            PMA_Util::pageselector(
                'pos', 10, 2, 3
            ),
            $result_1
        );
        $this->assertContains(
            '<input type="submit" name="navig"'
            . ' class="ajax" '
            . 'value="&gt" />',
            $result_1
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
            array('tag' => 'thead'), PMA_getCentralColumnsTableHeader(
                'column_heading', __('Click to sort'), 2
            )
        );
    }

    /**
     * Test for PMA_getHTMLforCentralColumnsTableRow
     *
     * @return void
     */
    public function testPMAGetHTMLforCentralColumnsTableRow()
    {
        $row = array(
            'col_name'=>'col_test',
            'col_type'=>'int',
            'col_length'=>12,
            'col_collation'=>'utf8_general_ci',
            'col_isNull'=>1,
            'col_extra'=>''
        );
        $result = PMA_getHTMLforCentralColumnsTableRow($row, false, 1, 'phpmyadmin');
        $this->assertTag(
            array('tag' => 'tr'), $result
        );
        $this->assertContains(
            PMA_URL_getHiddenInputs('phpmyadmin'),
            $result
        );
        $this->assertTag(
            array('tag' => 'span', 'content'=>'col_test'), $result
        );
        $this->assertContains(
            __('on update CURRENT_TIMESTAMP'),
            $result
        );
        $this->assertContains(
            PMA_getHtmlForColumnDefault(
                1, 5, 0, strtoupper($row['col_type']), '',
                array('DefaultType'=>'NONE')
            ),
            $result
        );
        $row['col_default'] = 100;
        $result_1 = PMA_getHTMLforCentralColumnsTableRow(
            $row, false, 1, 'phpmyadmin'
        );
        $this->assertContains(
            PMA_getHtmlForColumnDefault(
                1, 5, 0, strtoupper($row['col_type']), '',
                array('DefaultType'=>'USER_DEFINED', 'DefaultValue'=>100)
            ),
            $result_1
        );
        $row['col_default'] = 'CURRENT_TIMESTAMP';
        $result_2 = PMA_getHTMLforCentralColumnsTableRow(
            $row, false, 1, 'phpmyadmin'
        );
        $this->assertContains(
            PMA_getHtmlForColumnDefault(
                1, 5, 0, strtoupper($row['col_type']), '',
                array('DefaultType'=>'CURRENT_TIMESTAMP')
            ),
            $result_2
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

    /**
     * Test for PMA_getHTMLforAddNewColumn
     *
     * @return void
     */
    public function testPMAGetHTMLforAddNewColumn()
    {
        $result = PMA_getHTMLforAddNewColumn('phpmyadmin');
        $this->assertTag(
            array('tag' => 'form','tag'=>'table'), $result
        );
        $this->assertContains(
            __('Add new column'),
            $result
        );
        $this->assertContains(
            PMA_URL_getHiddenInputs('phpmyadmin'),
            $result
        );
    }
}