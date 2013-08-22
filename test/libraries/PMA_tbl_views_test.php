<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_views.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tbl_views.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';

/**
 * Tests for libraries/tbl_views.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblViewsTest extends PHPUnit_Framework_TestCase
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
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
            
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue('executeResult2'));

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'relwork' => 'relwork',
            'relation' => 'relation',
            'column_info' => 'column_info',
        );
            
        $meta1 = new FieldMeta();
        $meta1->table = "meta1_table";
        $meta1->name = "meta1_name";            
        $meta2 = new FieldMeta();
        $meta2->table = "meta2_table";
        $meta2->name = "meta2_name";
        
        $getFieldsMeta = array($meta1, $meta2);
        $dbi->expects($this->any())
            ->method('getFieldsMeta')
            ->will($this->returnValue($getFieldsMeta));

        $GLOBALS['dbi'] = $dbi;
    }
    
    /**
     * Tests for PMA_getColumnMap() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetColumnMap()
    {
        $sql_query = "PMA_sql_query";
        $view_columns = array(
            "view_columns1", "view_columns2"
        );
        
        $column_map = PMA_getColumnMap($sql_query, $view_columns);

        $this->assertEquals(
            array(
                'table_name' => 'meta1_table',
                'refering_column' => 'meta1_name',
                'real_column' => 'view_columns1'
            ),
            $column_map[0]
        );
        $this->assertEquals(
            array(
                'table_name' => 'meta2_table',
                'refering_column' => 'meta2_name',
                'real_column' => 'view_columns2'
            ),
            $column_map[1]
        );
    }
    
    /**
     * Tests for PMA_getExistingTranformationData() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetExistingTranformationData()
    {
        $db = "PMA_db";
        $ret = PMA_getExistingTranformationData($db);
        
        //validate that is the same as $GLOBALS['dbi']->tryQuery
        $this->assertEquals(
            'executeResult2',
            $ret
        );
    }
}

/**
 * clas for Table Field Meta
 *
 * @package PhpMyAdmin-test
 */
class FieldMeta
{
    public $table;
    public $name;
}

?>