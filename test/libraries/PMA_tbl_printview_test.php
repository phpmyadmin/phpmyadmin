<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_printview.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tbl_printview.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/transformations.lib.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/js_escape.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/Index.class.php';

/**
 * Tests for libraries/tbl_printview.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblPrintViewTest extends PHPUnit_Framework_TestCase
{

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'column_info' => 'column_info',
            'relwork' => 'relwork',
            'relation' => 'relation',
            'commwork' => 'commwork',
            'bookmarkwork' => 'bookmarkwork',
        );

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $fetchResult = array(
            'column1' => array('mimetype' => 'value1', 'transformation'=> 'pdf'),
            'column2' => array('mimetype' => 'value2', 'transformation'=> 'xml'),
        );

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fetchResult));

        $dbi->expects($this->any())->method('getTableIndexes')
            ->will($this->returnValue(array()));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Tests for PMA_getHtmlForTablesInfo() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForTablesInfo()
    {
        $the_tables = array("PMA_table1", "PMA_table2");

        $html = PMA_getHtmlForTablesInfo($the_tables);

        $this->assertContains(
            __('Showing tables:'),
            $html
        );
        $this->assertContains(
            "`PMA_table1`, `PMA_table2`",
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForPrintViewFooter() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForPrintViewFooter()
    {
        $html = PMA_getHtmlForPrintViewFooter();

        $this->assertContains(
            '<input type="button" class="button" id="print" value="Print" />',
            $html
        );
        $this->assertContains(
            "PMA_disable_floating_menubar",
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForRowStatistics() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForRowStatistics()
    {
        $showtable = array(
            'Row_format' => "Fixed",
            'Rows' => 10,
            'Avg_row_length' => 123,
            'Data_length' => 345,
            'Auto_increment' => 1234,
            'Create_time' => "today",
            'Update_time' => "time2",
            'Check_time' => "yesterday",
        );
        $cell_align_left = "cell_align_left";
        $avg_size = 12;
        $avg_unit = 45;
        $mergetable = false;

        $html = PMA_getHtmlForRowStatistics(
            $showtable, $cell_align_left, $avg_size, $avg_unit, $mergetable
        );

        $this->assertContains(
            __('Row Statistics:'),
            $html
        );

        //validation 1 : Row_format
        $this->assertContains(
            __('Format'),
            $html
        );
        $this->assertContains(
            $cell_align_left,
            $html
        );
        //$showtable['Row_format'] == 'Fixed'
        $this->assertContains(
            __('static'),
            $html
        );

        //validation 2 : Avg_row_length
        $length = PMA_Util::formatNumber(
            $showtable['Avg_row_length'], 0
        );
        $this->assertContains(
            $length,
            $html
        );
        $this->assertContains(
            __('Row size'),
            $html
        );
        $this->assertContains(
            $avg_size . ' ' . $avg_unit,
            $html
        );

        //validation 3 : Auto_increment
        $average = PMA_Util::formatNumber(
            $showtable['Auto_increment'], 0
        );
        $this->assertContains(
            $average,
            $html
        );
        $this->assertContains(
            __('Next autoindex'),
            $html
        );

        //validation 4 : Create_time
        $time = PMA_Util::localisedDate(
            strtotime($showtable['Create_time'])
        );
        $this->assertContains(
            __('Creation'),
            $html
        );
        $this->assertContains(
            $time,
            $html
        );

        //validation 5 : Update_time
        $time = PMA_Util::localisedDate(
            strtotime($showtable['Update_time'])
        );
        $this->assertContains(
            __('Last update'),
            $html
        );
        $this->assertContains(
            $time,
            $html
        );

        //validation 6 : Check_time
        $time = PMA_Util::localisedDate(
            strtotime($showtable['Check_time'])
        );
        $this->assertContains(
            __('Last check'),
            $html
        );
        $this->assertContains(
            $time,
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForSpaceUsage() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForSpaceUsage()
    {
        $data_size = '10';
        $data_unit = '11';
        $index_size = '12';
        $index_unit = '13';
        $free_size = '14';
        $free_unit = '15';
        $effect_size = '16';
        $effect_unit = '17';
        $tot_size = '18';
        $tot_unit = '19';
        $mergetable = false;

        $html = PMA_getHtmlForSpaceUsage(
            $data_size, $data_unit, $index_size, $index_unit,
            $free_size, $free_unit, $effect_size, $effect_unit,
            $tot_size, $tot_unit, $mergetable
        );

        //validation 1 : title
        $this->assertContains(
            __('Space usage:'),
            $html
        );

        //validation 2 : $data_size & $data_unit
        $this->assertContains(
            $data_size,
            $html
        );
        $this->assertContains(
            $data_unit,
            $html
        );

        //validation 3 : $index_size & $index_unit
        $this->assertContains(
            $index_size,
            $html
        );
        $this->assertContains(
            $index_unit,
            $html
        );

        //validation 4 : Overhead
        $this->assertContains(
            __('Overhead'),
            $html
        );
        $this->assertContains(
            $free_size,
            $html
        );
        $this->assertContains(
            $free_unit,
            $html
        );

        //validation 5 : Effective
        $this->assertContains(
            __('Effective'),
            $html
        );
        $this->assertContains(
            $effect_size,
            $html
        );
        $this->assertContains(
            $effect_unit,
            $html
        );

        //validation 6 : $tot_size & $tot_unit
        $this->assertContains(
            $tot_size,
            $html
        );
        $this->assertContains(
            $tot_unit,
            $html
        );

    }

    /**
     * Tests for PMA_getHtmlForSpaceUsageAndRowStatistics() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForSpaceUsageAndRowStatistics()
    {
        $showtable = array(
            'Row_format' => "Fixed",
            'Rows' => 10,
            'Avg_row_length' => 123,
            'Data_length' => 345,
            'Auto_increment' => 1234,
            'Create_time' => "today",
            'Update_time' => "time2",
            'Check_time' => "yesterday",
            'Data_length' => 10,
            'Index_length' => 12334,
            'Data_length' => 4567,
            'Data_free' => 3456,
            'Check_time' => 1234,
        );
        $db = "pma_db";
        $table = "pma_table";
        $cell_align_left = "cell_align_left";

        $html = PMA_getHtmlForSpaceUsageAndRowStatistics(
            $showtable, $db, $table, $cell_align_left
        );

        //validation 1 : $data_size, $data_unit
        list($data_size, $data_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length']
        );
        $this->assertContains(
            $data_size,
            $html
        );
        $this->assertContains(
            $data_unit,
            $html
        );

        //validation 2 : $data_size, $data_unit
        list($index_size, $index_unit)
            = PMA_Util::formatByteDown(
                $showtable['Index_length']
            );
        $this->assertContains(
            $index_size,
            $html
        );
        $this->assertContains(
            $index_unit,
            $html
        );

        //validation 3 : $free_size, $free_unit
        list($free_size, $free_unit)
            = PMA_Util::formatByteDown(
                $showtable['Data_free']
            );
        $this->assertContains(
            $free_size,
            $html
        );
        $this->assertContains(
            $free_unit,
            $html
        );

        //validation 4 : $effect_size, $effect_unit
        list($effect_size, $effect_unit)
            = PMA_Util::formatByteDown(
                $showtable['Data_length'] + $showtable['Index_length']
                - $showtable['Data_free']
            );
        $this->assertContains(
            $effect_size,
            $html
        );
        $this->assertContains(
            $effect_unit,
            $html
        );

        //validation 5 : $effect_size, $effect_unit
        list($tot_size, $tot_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length']
        );
        $this->assertContains(
            $tot_size,
            $html
        );
        $this->assertContains(
            $tot_unit,
            $html
        );
    }

    /**
     * Tests for PMA_getHtmlForPrintViewColumns() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForPrintViewColumns()
    {
        $columns = array(
            array(
                "Type" => "Type1",
                "Default" => "Default1",
                "Null" => "Null1",
                "Field" => "Field1",
            )
        );
        $analyzed_sql = array(
            array(
                'create_table_fields' => array(
                     "Field1" => array(
                         "type" => "TIMESTAMP",
                         "timestamp_not_null" => true
                     )
                 )
            )
        );
        $have_rel = false;
        $res_rel = array();
        $db = "pma_db";
        $table = "pma_table";
        $cfgRelation = array('mimework' => true);

        $html = PMA_getHtmlForPrintViewColumns(
            false, $columns, $analyzed_sql, $have_rel,
            $res_rel, $db, $table, $cfgRelation
        );

        //validation 1 : $row
        $row = $columns[0];
        $this->assertContains(
            htmlspecialchars($row['Default']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($row['Field']),
            $html
        );

        //validation 2 : $field_name
        $field_name = htmlspecialchars($row['Field']);
        $comments = PMA_getComments($db, $table);
        $this->assertContains(
            $field_name,
            $html
        );

        //validation 3 : $extracted_columnspec
        $extracted_columnspec = PMA_Util::extractColumnSpec($row['Type']);
        $type = $extracted_columnspec['print_type'];
        $attribute = $extracted_columnspec['attribute'];
        $this->assertContains(
            $type,
            $html
        );
    }
}

?>
