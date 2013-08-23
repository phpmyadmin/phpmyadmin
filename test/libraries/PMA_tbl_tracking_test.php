<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_tracking.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tbl_tracking.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';

/**
 * Tests for libraries/tbl_tracking.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblTrackingTest extends PHPUnit_Framework_TestCase
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
        $GLOBALS['db'] = "PMA_db";
        $GLOBALS['table'] = "PMA_table";
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['ServerDefault'] = "server";
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
            
        $fetchArray = array('version' => "10");
        $dbi->expects($this->any())
            ->method('fetchArray')
            ->will($this->returnValue($fetchArray));

        $GLOBALS['dbi'] = $dbi;
    }
    
    /**
     * Tests for PMA_filterTracking() method.
     *
     * @return void
     * @test
     */
    public function testPMAFilterTracking()
    {
        $data = array(
            array(
                "date" => "20120102",  
                "username"=> "username1", 
                "statement"=>"statement1"
            ),
            array(
                "date" => "20130102", 
                "username"=> "username2", 
                "statement"=>"statement2"
            ),
        );
        $filter_ts_from = 0; 
        $filter_ts_to = 999999999999;
        $filter_users = array("username1");
        
        $ret = PMA_filterTracking(
            $data, $filter_ts_from, $filter_ts_to, $filter_users
        );

        $this->assertEquals(
            array(
            'id' => 0,
            'timestamp' => 1325458800,
            'username' => 'username1',
            'statement' => 'statement1',
            ),
            $ret[0]
        );
    }
    
    /**
     * Tests for PMA_getHtmlForDataDefinitionAndManipulationStatements() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDataDefinitionAndManipulationStatements()
    {
        $url_query = "url_query";
        $last_version = "10";
        $html = PMA_getHtmlForDataDefinitionAndManipulationStatements(
            $url_query, $last_version
        );

        $this->assertContains(
            '<div id="div_create_version">',
            $html
        );
        
        $this->assertContains(
            $url_query,
            $html
        );
        
        $this->assertContains(
            PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table']),
            $html
        );
        
        $item = sprintf(
            __('Create version %1$s of %2$s'),
            ($last_version + 1),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertContains(
            $item,
            $html
        );
        
        $item = '<input type="checkbox" name="delete" value="true"'
        . ' checked="checked" /> DELETE<br/>';
        $this->assertContains(
            $item,
            $html
        );
        
        $this->assertContains(
            __('Create version'),
            $html
        );
    }
    
    /**
     * Tests for PMA_getHtmlForActivateTracking() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForActivateTracking()
    {
        $url_query = "url_query";
        $last_version = "10";
        $html = PMA_getHtmlForActivateTracking($url_query, $last_version);

        $this->assertContains(
            '<div id="div_activate_tracking">',
            $html
        );
        
        $this->assertContains(
            $url_query,
            $html
        );
        
        $item = sprintf(
            __('Activate tracking for %s'),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertContains(
            $item,
            $html
        );
        
        $this->assertContains(
            $last_version,
            $html
        );
        
        $this->assertContains(
            __('Activate now'),
            $html
        );
    }
    
    /**
     * Tests for PMA_getHtmlForDeactivateTracking() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForDeactivateTracking()
    {
        $url_query = "url_query";
        $last_version = "10";
        $html = PMA_getHtmlForDeactivateTracking($url_query, $last_version);

        $this->assertContains(
            '<div id="div_deactivate_tracking">',
            $html
        );
        
        $this->assertContains(
            $url_query,
            $html
        );
        
        $item = sprintf(
            __('Deactivate tracking for %s'),
            htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
        );
        $this->assertContains(
            $item,
            $html
        );
        
        $this->assertContains(
            $last_version,
            $html
        );
        
        $this->assertContains(
            __('Deactivate now'),
            $html
        );
    }
    
    /**
     * Tests for PMA_getTableLastVersionNumber() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetTableLastVersionNumber()
    {
        $sql_result = "sql_result";
        $last_version = PMA_getTableLastVersionNumber($sql_result);

        $this->assertEquals(
            "10",
            $last_version
        );
    }
    
    /**
     * Tests for PMA_getHtmlForColumns() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForColumns()
    {
        $columns = array(
            array(
                'Field'=>'Field1', 
                'Type'=>'Type1', 
                'Collation'=>'Collation1',  
                "Null"=>'YES',  
                'Extra'=>'Extra1',  
                'Key'=>'PRI',
                'Comment'=>'Comment1' 
            ),
            array(
                'Field'=>'Field2', 
                'Type'=>'Type2', 
                'Collation'=>'Collation2',  
                "Null"=>'No',  
                'Extra'=>'Extra2',    
                'Key'=>'Key2',
                'Comment'=>'Comment2' 
            ),
        );
        
        $html = PMA_getHtmlForColumns($columns);
        
        $this->assertContains(
            __('Column'),
            $html
        );
        $this->assertContains(
            __('Type'),
            $html
        );
        $this->assertContains(
            __('Collation'),
            $html
        );
        $this->assertContains(
            __('Default'),
            $html
        );
        $this->assertContains(
            __('Comment'),
            $html
        );
        
        //column1
        $item1= $columns[0];
        $this->assertContains(
            htmlspecialchars($item1['Field']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Type']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Collation']),
            $html
        );
        $this->assertContains(
            '<i>NULL</i>',
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Comment']),
            $html
        );
        
        //column2
        $item1= $columns[1];
        $this->assertContains(
            htmlspecialchars($item1['Field']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Type']),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Collation']),
            $html
        );
        $this->assertContains(
            _pgettext('None for default', 'None'),
            $html
        );
        $this->assertContains(
            htmlspecialchars($item1['Comment']),
            $html
        );
    }
}

?>