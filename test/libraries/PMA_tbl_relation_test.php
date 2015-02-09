<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_relation.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tbl_relation.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/Theme.class.php';

/**
 * Tests for libraries/tbl_relation.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblRelationTest extends PHPUnit_Framework_TestCase
{
    /**
     * Configures environment
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['ShowHint'] = true;
        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        $GLOBALS['pma'] = new DataBasePMAMockForTblRelation();
        $GLOBALS['pma']->databases = new DataBaseMockForTblRelation();

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Tests for PMA_generateRelationalDropdown() method.
     *
     * @return void
     * @test
     */
    public function testGenerateRelationalDropdown()
    {
        // test for start tag
        $this->assertStringStartsWith(
            '<select',
            PMA_generateRelationalDropdown('name')
        );

        // test for end tag
        $this->assertStringEndsWith(
            '</select>',
            PMA_generateRelationalDropdown('name')
        );

        // test for name
        $this->assertStringStartsWith(
            '<select name="name"',
            PMA_generateRelationalDropdown('name')
        );

        // test for title
        $this->assertStringStartsWith(
            '<select name="name" title="title"',
            PMA_generateRelationalDropdown('name', array(), false, 'title')
        );

        $values = array('value1', '<alue2', 'value3');
        // test for empty option
        $this->assertContains(
            '<option value=""></option>',
            PMA_generateRelationalDropdown('name', $values)
        );

        // test for options and escaping
        $this->assertContains(
            '<option value="&lt;alue2">&lt;alue2</option>',
            PMA_generateRelationalDropdown('name', $values)
        );

        // test for selected option
        $this->assertContains(
            '<option value="value1" selected="selected">value1</option>',
            PMA_generateRelationalDropdown('name', $values, 'value1')
        );

        // test for selected value not found in values array and its escaping
        $this->assertContains(
            '<option value="valu&lt;4" selected="selected">valu&lt;4'
            . '</option></select>',
            PMA_generateRelationalDropdown('name', $values, 'valu<4')
        );
    }

    /**
     * Tests for PMA_generateDropdown() method.
     *
     * @return void
     * @test
     */
    public function testPMAGenerateDropdown()
    {
        $dropdown_question = "dropdown_question";
        $select_name = "select_name";
        $choices = array("choice1", "choice2");
        $selected_value = "";

        $html_output = PMA_generateDropdown(
            $dropdown_question, $select_name, $choices, $selected_value
        );

        $this->assertContains(
            htmlspecialchars($dropdown_question),
            $html_output
        );

        $this->assertContains(
            htmlspecialchars($select_name),
            $html_output
        );

        $this->assertContains(
            htmlspecialchars("choice1"),
            $html_output
        );

        $this->assertContains(
            htmlspecialchars("choice2"),
            $html_output
        );
    }

    /**
     * Tests for PMA_backquoteSplit() method.
     *
     * @return void
     * @test
     */
    public function testPMABackquoteSplit()
    {
        $text = "test `PMA` Back `quote` Split";

        $this->assertEquals(
            array('`PMA`', '`quote`'),
            PMA_backquoteSplit($text)
        );
    }

    /**
     * Tests for PMA_getSQLToCreateForeignKey() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetSQLToCreateForeignKey()
    {
        $table = "PMA_table";
        $field = array("PMA_field1", "PMA_field2");
        $foreignDb = "foreignDb";
        $foreignTable = "foreignTable";
        $foreignField = array("foreignField1", "foreignField2");

        $sql =  PMA_getSQLToCreateForeignKey(
            $table, $field, $foreignDb, $foreignTable, $foreignField
        );
        $sql_excepted = 'ALTER TABLE `PMA_table` ADD  '
            . 'FOREIGN KEY (`PMA_field1`, `PMA_field2`) REFERENCES '
            . '`foreignDb`.`foreignTable`(`foreignField1`, `foreignField2`);';
        $this->assertEquals(
            $sql_excepted,
            $sql
        );
    }

    /**
     * Tests for PMA_getSQLToDropForeignKey() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetSQLToDropForeignKey()
    {
        $table = "pma_table";
        $fk = "pma_fk";

        $this->assertEquals(
            "ALTER TABLE `pma_table` DROP FOREIGN KEY `pma_fk`;",
            PMA_getSQLToDropForeignKey($table, $fk)
        );
    }

    /**
     * Tests for PMA_getHtmlForCommonForm() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForCommonForm()
    {
        $db = "pma_db";
        $table = "pma_table";
        $columns = array(
            array("Field" => "Field1")
        );
        $cfgRelation = array(
            'displaywork' => true,
            'relwork' => true,
            'displaywork' => true,
        );
        $tbl_storage_engine = "InnoDB";
        $existrel =  array();
        $existrel_foreign =  array();
        $options_array =  array();

        $save_row =  array();
        foreach ($columns as $row) {
            $save_row[] = $row;
        }

        $html = PMA_getHtmlForCommonForm(
            $db, $table, $columns, $cfgRelation,
            $tbl_storage_engine, $existrel, $existrel_foreign, $options_array
        );

        //case 1: PMA_getHtmlForInternalRelationForm
        $this->assertContains(
            PMA_getHtmlForInternalRelationForm(
                $columns, $tbl_storage_engine,
                $existrel, $db
            ),
            $html
        );

        //case 2: PMA_getHtmlForForeignKeyForm
        $this->assertContains(
            PMA_getHtmlForForeignKeyForm(
                $columns, $existrel_foreign, $db,
                $tbl_storage_engine, $options_array
            ),
            $html
        );

        $this->assertContains(
            PMA_URL_getHiddenInputs($db, $table),
            $html
        );

        $this->assertContains(
            __('Column'),
            $html
        );

        $this->assertContains(
            __('Internal relation'),
            $html
        );

        $this->assertContains(
            __('Choose column to display:'),
            $html
        );

        //case 3: PMA_getHtmlForInternalRelationRow
        $row = PMA_getHtmlForInternalRelationRow(
            $save_row, 0, true,
            $existrel, $db
        );
        $this->assertContains(
            $row,
            $html
        );

        //case 4: PMA_getHtmlForForeignKeyRow
        $row = PMA_getHtmlForForeignKeyRow(
            array(), true, $columns, 0,
            $options_array, $tbl_storage_engine, $db
        );
        $this->assertContains(
            $row,
            $html
        );

        //case 5: PMA_getHtmlForDisplayFieldInfos
        $this->assertContains(
            PMA_getHtmlForDisplayFieldInfos($db, $table, $save_row),
            $html
        );

        //case 6: PMA_getHtmlForCommonFormFooter
        $this->assertContains(
            PMA_getHtmlForCommonFormFooter(),
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForDisplayFieldInfos() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDisplayFieldInfos()
    {
        $db = "pma_db";
        $table = "pma_table";
        $save_row = array(
            array("Field" => "Field1"),
            array("Field" => "Field2"),
        );

        $html = PMA_getHtmlForDisplayFieldInfos($db, $table, $save_row);

        $this->assertContains(
            __('Choose column to display:'),
            $html
        );
        $this->assertContains(
            htmlspecialchars($save_row[0]['Field']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($save_row[1]['Field']),
            $html
        );
    }

    /**
     * Tests for PMA_getQueryForDisplayUpdate() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetQueryForDisplayUpdate()
    {
        $disp = true;
        $display_field = '';
        $db = "pma_db";
        $table = "pma_table";
        $cfgRelation = array(
            'displaywork' => true,
            'relwork' => true,
            'displaywork' => true,
            'table_info' => 'table_info',
        );

        $GLOBALS['cfgRelation']['db'] = 'global_db';

        //case 1: $disp == true && $display_field == ''
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = "DELETE FROM `global_db`.`table_info` "
            . "WHERE db_name  = 'pma_db' AND table_name = 'pma_table'";
        $this->assertEquals(
            $query_expect,
            $query
        );

        //case 2: $disp == true && $display_field == 'display_field'
        $display_field == 'display_field';
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = "DELETE FROM `global_db`.`table_info` "
            . "WHERE db_name  = 'pma_db' AND table_name = 'pma_table'";
        $this->assertEquals(
            $query_expect,
            $query
        );

        //case 3: $disp == false && $display_field == 'display_field'
        $disp = false;
        $display_field = 'display_field';
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = "INSERT INTO `global_db`.`table_info`"
            . "(db_name, table_name, display_field)"
            . " VALUES('pma_db','pma_table','display_field')";
        $this->assertEquals(
            $query_expect,
            $query
        );

        //case 4: $disp == false && $display_field == ''
        $disp = false;
        $display_field = '';
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = '';
        $this->assertEquals(
            $query_expect,
            $query
        );
    }
}

/**
 * Mock class for DataBasePMAMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBasePMAMockForTblRelation
{
    var $databases;
}

/**
 * Mock class for DataBaseMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBaseMockForTblRelation
{
    /**
     * mock function to return table is existed
     *
     * @param string $name table name
     *
     * @return bool
     */
    function exists($name)
    {
        return true;
    }
}
?>
