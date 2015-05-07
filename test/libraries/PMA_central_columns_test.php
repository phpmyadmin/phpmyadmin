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
    private $_columnData = array(
        array(
            'col_name' => "id", "col_type" => 'integer',
            'col_length' => 0, 'col_isNull' => 0, 'col_extra' => 'UNSIGNED,auto_increment',
            'col_default' => 1
        ),
        array('col_name' => "col1", 'col_type' => 'varchar',
            'col_length' => 100, 'col_isNull' => 1, 'col_extra' => 'BINARY',
            'col_default' => 1
        ),
        array(
            'col_name' => "col2", 'col_type' => 'DATETIME',
            'col_length' => 0, 'col_isNull' => 1, 'col_extra' => 'on update CURRENT_TIMESTAMP',
            'col_default' => 'CURRENT_TIMESTAMP'
        )
    );

    private $_modifiedColumnData = array(
        array(
            'col_name' => "id", "col_type" => 'integer',
            'col_length' => 0, 'col_isNull' => 0, 'col_extra' => 'auto_increment',
            'col_default' => 1, 'col_attribute' => 'UNSIGNED'
        ),
        array('col_name' => "col1", 'col_type' => 'varchar',
            'col_length' => 100, 'col_isNull' => 1, 'col_extra' => '',
            'col_default' => 1, 'col_attribute' => 'BINARY'
        ),
        array(
            'col_name' => "col2", 'col_type' => 'DATETIME',
            'col_length' => 0, 'col_isNull' => 1, 'col_extra' => '',
            'col_default' => 'CURRENT_TIMESTAMP', 'col_attribute' => 'on update CURRENT_TIMESTAMP'
        )
    );

    /**
     * prepares environment for tests
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $GLOBALS['cfg']['Server']['user'] = 'pma_user';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
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
            'central_columnswork' => true,
            'relwork' => 1,
            'db' => 'phpmyadmin',
            'relation' => 'relation',
            'central_columns' => 'pma_central_columns'
        );

        // mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        // set some common expectations
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
            ->method('tryQuery')
            ->will($this->returnValue(true));
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
        $GLOBALS['dbi']->expects($this->at(1))
            ->method('fetchResult')
            ->with("SELECT * FROM `pma_central_columns` WHERE db_name = 'phpmyadmin' LIMIT 0, 25;", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue($this->_columnData)
            );

        $GLOBALS['dbi']->expects($this->at(3))
            ->method('fetchResult')
            ->with("SELECT * FROM `pma_central_columns` WHERE db_name = 'phpmyadmin' LIMIT 1, 2;", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue(
                    array_slice($this->_columnData, 1, 2)
                )
            );

        $this->assertEquals(
            $this->_modifiedColumnData,
            PMA_getColumnsList('phpmyadmin')
        );
        $this->assertEquals(
            array_slice($this->_modifiedColumnData, 1, 2),
            PMA_getColumnsList('phpmyadmin', 1, 2)
        );
    }

    /**
     * Test for PMA_getCentralColumnsCount
     *
     * @return void
     */
    function testPMAGetCentralColumnsCount()
    {
        $GLOBALS['dbi']->expects($this->at(1))
            ->method('fetchResult')
            ->with("SELECT count(db_name) FROM `pma_central_columns` WHERE db_name = 'phpmyadmin';", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue(array(3))
            );

        $this->assertEquals(
            3,
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
        $_REQUEST['db'] = 'PMA_db';
        $_REQUEST['table'] = 'PMA_table';

        $this->assertTrue(
            PMA_syncUniqueColumns(array('PMA_table'))
        );
    }

    /**
     * Test for PMA_deleteColumnsFromList
     *
     * @return void
     */
    public function testPMADeleteColumnsFromList()
    {
        $_REQUEST['db'] = 'PMA_db';
        $_REQUEST['table'] = 'PMA_table';

        // when column exists in the central column list
        $GLOBALS['dbi']->expects($this->at(2))
            ->method('fetchResult')
            ->with("SELECT col_name FROM `pma_central_columns` WHERE db_name = 'PMA_db' AND col_name IN ('col1');", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue(array('col1'))
            );

        $GLOBALS['dbi']->expects($this->at(4))
            ->method('tryQuery')
            ->with("DELETE FROM `pma_central_columns` WHERE db_name = 'PMA_db' AND col_name IN ('col1');", $GLOBALS['controllink'])
            ->will(
                $this->returnValue(array('col1'))
            );

        $this->assertTrue(
            PMA_deleteColumnsFromList(array("col1"), false)
        );

        // when column does not exist in the central column list
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
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchResult')
            ->will(
                $this->returnValue($this->_columnData)
            );
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchValue')
            ->will(
                $this->returnValue('PMA_table=CREATE table `PMA_table` (id integer)')
            );
        $this->assertTrue(
            PMA_makeConsistentWithList("phpmyadmin", array('PMA_table'))
        );
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

        $GLOBALS['dbi']->expects($this->at(3))
            ->method('fetchResult')
            ->with("SELECT col_name FROM `pma_central_columns` WHERE db_name = 'PMA_db' AND col_name IN ('id','col1','col2');", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue(array('id','col1'))
            );
        $this->assertEquals(
            array("id", "col1"),
            PMA_getCentralColumnsFromTable($db, $table)
        );
    }

    /**
     * Test for PMA_getCentralColumnsFromTable with $allFields = true
     *
     * @return void
     */
    public function testPMAGetCentralColumnsFromTableWithAllFields()
    {
        $db = 'PMA_db';
        $table = 'PMA_table';

        $GLOBALS['dbi']->expects($this->at(3))
            ->method('fetchResult')
            ->with("SELECT * FROM `pma_central_columns` WHERE db_name = 'PMA_db' AND col_name IN ('id','col1','col2');", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue(array_slice($this->_columnData, 0, 2))
            );
        $this->assertEquals(
            array_slice($this->_modifiedColumnData, 0, 2),
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
                "phpmyadmin", "", "", "", "","", "", "", "", ""
            )
        );
        $this->assertTrue(
            PMA_updateOneColumn(
                "phpmyadmin", "col1", "", "", "","", "", "", "", ""
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
        $this->assertContains(
            '<table',
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
        $this->assertContains(
            '<thead',
            PMA_getCentralColumnsTableHeader(
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
            'col_extra'=> '',
            'col_attribute'=>''
        );
        $result = PMA_getHTMLforCentralColumnsTableRow($row, false, 1, 'phpmyadmin');
        $this->assertContains(
            '<tr',
            $result
        );
        $this->assertContains(
            PMA_URL_getHiddenInputs('phpmyadmin'),
            $result
        );
        $this->assertContains(
            '<span',
            $result
        );
        $this->assertContains(
            'col_test',
            $result
        );
        $this->assertContains(
            __('on update CURRENT_TIMESTAMP'),
            $result
        );
        $this->assertContains(
            PMA_getHtmlForColumnDefault(
                1, 6, 0, /*overload*/mb_strtoupper($row['col_type']), '',
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
                1, 6, 0, /*overload*/mb_strtoupper($row['col_type']), '',
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
                1, 6, 0, /*overload*/mb_strtoupper($row['col_type']), '',
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
        $GLOBALS['dbi']->expects($this->at(1))
            ->method('fetchResult')
            ->with("SELECT * FROM `pma_central_columns` WHERE db_name = 'phpmyadmin';", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue($this->_columnData)
            );
        $this->assertEquals(
            json_encode($this->_modifiedColumnData),
            PMA_getCentralColumnsListRaw('phpmyadmin', '')
        );
    }

    /**
     * Test for PMA_getCentralColumnsListRaw with a table name
     *
     * @return void
     */
    public function testPMAGetCentralColumnsListRawWithTable()
    {
        $GLOBALS['dbi']->expects($this->at(3))
            ->method('fetchResult')
            ->with("SELECT * FROM `pma_central_columns` WHERE db_name = 'phpmyadmin' AND col_name NOT IN ('id','col1','col2');", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue($this->_columnData)
            );
        $this->assertEquals(
            json_encode($this->_modifiedColumnData),
            PMA_getCentralColumnsListRaw('phpmyadmin', 'table1')
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
        $this->assertContains(
            '<form',
            $result
        );
        $this->assertContains(
            '<table',
            $result
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
        $GLOBALS['dbi']->expects($this->at(1))
            ->method('fetchResult')
            ->with("SELECT * FROM `pma_central_columns` WHERE db_name = 'phpmyadmin' AND col_name IN ('col1');", null, null, $GLOBALS['controllink'])
            ->will(
                $this->returnValue(array_slice($this->_columnData, 1, 1))
            );
        $this->assertEquals(
            array_slice($this->_modifiedColumnData, 1, 1),
            PMA_findExistingColNames('phpmyadmin', "'col1'", true)
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
        $this->assertContains(
            '<select name="table-select" id="table-select"',
            $result
        );
        $this->assertContains(
            '<option value="PMA_table"',
            $result
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
            '<option value="id">id</option><option value="col1">col1</option><option value="col2">col2</option>',
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
        $this->assertContains(
            '<table',
            $result
        );
        $this->assertContains(
            '<form',
            $result
        );
        $this->assertContains(
            PMA_URL_getHiddenInputs('phpmyadmin')
            . '<input type="hidden" name="add_column" value="add">'
            . '<input type="hidden" name="pos" value="0" />'
            . '<input type="hidden" name="total_rows" value="20"/>',
            $result
        );
    }
}
