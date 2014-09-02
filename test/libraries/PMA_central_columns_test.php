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
require_once 'libraries/sqlparser.lib.php';

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
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['db'] = 'PMA_db';
        $GLOBALS['table'] = 'PMA_table';

        //$_SESSION
        $GLOBALS['server'] = 1;
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $_SESSION['relation'][1] = array(
            'central_columnswork'=>true, 'relwork'=>1,
            'db'=>'phpmyadmin', 'relation'=>'pma_central_columns'
        );
                //mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;
        // set expectations
        $dbi->expects($this->any())
            ->method('selectDb')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('getColumns')
            ->will(
                $this->returnValue(
                    array(
                        "id"=>array("Type"=>"integer", "Null"=>"NO"),
                        "col1"=>array("Type"=>'varchar(100)', "Null"=>"YES"),
                        "col2"=>array("Type"=>'DATETIME', "Null"=>"NO")
                    )
                )
            );
        $dbi->expects($this->any())
            ->method('getColumnNames')
            ->will($this->returnValue(array("id", "col1", "col2")));
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue(array("id", "col1")));
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('fetchValue')
            ->will(
                $this->returnValue('PMA_table=CREATE table `PMA_table` (id integer)')
            );
        $dbi->expects($this->any())
            ->method('getTables')
            ->will(
                $this->returnValue(array("PMA_table", "PMA_table1", "PMA_table2"))
            );

    }

    /**
     * Test for PMA_centralColumnsGetParams
     *
     * @return void
     */
    public function testPMACentralColumnsGetParams()
    {
        $this->assertSame(
            array(
                'user' => 'pma_user',
                'db' => 'phpmyadmin',
                'table' => 'pma_central_columns'
            ),
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
            array("id", "col1"),
            PMA_getColumnsList('phpmyadmin')
        );
        $this->assertEquals(
            array("id", "col1"),
            PMA_getColumnsList('phpmyadmin', 0, 0)
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
            'id',
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
        $field_select = array("col1");
        $_POST['db'] = 'PMA_db';
        $_POST['table'] = 'PMA_table';
        $this->assertInstanceOf(
            'PMA_Message', PMA_syncUniqueColumns($field_select, false)
        );
        $field_select = array("PMA_table");
        $this->assertInstanceOf(
            'PMA_Message', PMA_syncUniqueColumns($field_select)
        );
    }

    /**
     * Test for PMA_deleteColumnsFromList
     *
     * @return void
     */
    public function testPMADeleteColumnsFromList()
    {
        $field_select = array("col1");
        $_POST['db'] = 'PMA_db';
        $_POST['table'] = 'PMA_table';
        $this->assertTrue(
            PMA_deleteColumnsFromList($field_select, false)
        );
        $this->assertInstanceOf(
            'PMA_Message', PMA_deleteColumnsFromList(array('column1'), false)
        );
        $this->assertInstanceOf(
            'PMA_Message', PMA_deleteColumnsFromList(array('PMA_table'))
        );
    }

    /**
     * Test for PMA_makeConsistentWithList
     *
     * @return void
     */
    public function testPMAMakeConsistentWithList()
    {
        $dbi = $GLOBALS['dbi'];
        $dbitmp = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbitmp;
        $dbitmp->expects($this->any())
            ->method('selectDb')
            ->will($this->returnValue(true));
        $dbitmp->expects($this->any())
            ->method('getColumnNames')
            ->will($this->returnValue(array("id", "col1", "col2")));
        $dbitmp->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $dbitmp->expects($this->any())
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array(
                        array(
                            'col_name'=>"id", "col_type"=>'integer',
                            'col_length'=>0, 'col_isNull'=>0, 'col_extra'=>'',
                            'col_default'=>1
                        ),
                        array('col_name'=>"col1", 'col_type'=>'varchar',
                            'col_length'=>100, 'col_isNull'=>1, 'col_extra'=>'',
                            'col_default'=>1
                        ),
                        array(
                            'col_name'=>"col2", 'col_type'=>'DATETIME',
                            'col_length'=>0, 'col_isNull'=>1, 'col_extra'=>'',
                            'col_default'=>'CURRENT_TIMESTAMP'
                        )
                    )
                )
            );
        $dbitmp->expects($this->any())
            ->method('fetchValue')
            ->will(
                $this->returnValue('PMA_table=CREATE table `PMA_table` (id integer)')
            );
        $this->assertTrue(
            PMA_makeConsistentWithList("phpmyadmin", array('PMA_table'))
        );
        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test for PMA_getCentralColumnsFromTable
     *
     * @return void
     */
    public function testPMAGetCentralColumnsFromTable()
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $this->assertEquals(
            array("id", "col1"),
            PMA_getCentralColumnsFromTable($db, $table)
        );
        $this->assertEquals(
            array("id", "col1"),
            PMA_getCentralColumnsFromTable($db, $table, true)
        );
    }
    /**
     * Test for PMA_updateOneColumn
     *
     * @return void
     */
    public function testPMAUpdateOneColumn()
    {
        $this->assertTrue(
            PMA_updateOneColumn(
                "phpmyadmin", "", "", "", "", "", "", "", ""
            )
        );
        $this->assertTrue(
            PMA_updateOneColumn(
                "phpmyadmin", "col1", "", "", "", "", "", "", ""
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
        /** @var PMA_String $pmaString */
        $pmaString = $GLOBALS['PMA_String'];

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
                1, 5, 0, $pmaString->strtoupper($row['col_type']), '',
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
                1, 5, 0, $pmaString->strtoupper($row['col_type']), '',
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
                1, 5, 0, $pmaString->strtoupper($row['col_type']), '',
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
            json_encode(array("id", "col1")),
            PMA_getCentralColumnsListRaw('phpmyadmin', 'pma_central_columns')
        );
        $this->assertEquals(
            json_encode(array("id", "col1")),
            PMA_getCentralColumnsListRaw('phpmyadmin', '')
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

    /**
     * Test for PMA_configErrorMessage
     *
     * @return void
     */
    public function testPMAConfigErrorMessage()
    {
        $this->assertInstanceOf(
            'PMA_Message',
            PMA_configErrorMessage()
        );
    }

    /**
     * Test for PMA_findExistingColNames
     *
     * @return void
     */
    public function testPMAFindExistingColNames()
    {
        $this->assertEquals(
            array('id', 'col1'),
            PMA_findExistingColNames('phpmyadmin', 'col1', true)
        );
    }

    /**
     * Test for PMA_getHTMLforTableDropdown
     *
     * @return void
     */
    public function testPMAGetHTMLforTableDropdown()
    {
        $db = 'PMA_db';
        $result = PMA_getHTMLforTableDropdown($db);
        $this->assertTag(array('tag'=>'select', 'id'=>'table-select'), $result);
        $this->assertTag(
            array(
                'tag'=>'option', 'attributes'=>array('value'=>'PMA_table'),
                'content'=>'PMA_table'
            ), $result
        );
    }

    /**
     * Test for PMA_getHTMLforColumnDropdown
     *
     * @return void
     */
    public function testPMAGetHTMLforColumnDropdown()
    {
        $db = 'PMA_db';
        $selected_tbl = 'PMA_table';
        $result = PMA_getHTMLforColumnDropdown($db, $selected_tbl);
        $this->assertEquals(
            '<option value="col2">col2</option>',
            $result
        );
    }

    /**
     * Test for PMA_getHTMLforAddCentralColumn
     *
     * @return void
     */
    public function testPMAGetHTMLforAddCentralColumn()
    {
        $result = PMA_getHTMLforAddCentralColumn(20, 0, 'phpmyadmin');
        $this->assertTag(array('tag'=>'table', 'tag'=>'form'), $result);
        $this->assertContains(
            PMA_URL_getHiddenInputs('phpmyadmin')
            . '<input type="hidden" name="add_column" value="add">'
            . '<input type="hidden" name="pos" value="0" />'
            . '<input type="hidden" name="total_rows" value="20"/>',
            $result
        );
    }
}