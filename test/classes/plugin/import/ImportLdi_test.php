<?php
/**
 * Tests for ImportLdi class
 *
 * @package PhpMyAdmin-test
 */
 
/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
$GLOBALS['server'] = 0;
$GLOBALS['plugin_param'] = "table";   

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Table.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';
/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/import/ImportLdi.class.php';


/**
 * Tests for ImportLdi class
 *
 * @package PhpMyAdmin-test
 */
class ImportLdi_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    { 
        //setting        
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['cfg']['AllowUserDropDatabase'] = false;
        $GLOBALS['cfg']['MySQLManualType'] = 'none';
        
        $GLOBALS['import_file'] = 'test/test_data/db_test_ldi.csv';
        $GLOBALS['import_text'] = 'ImportLdi_Test';
        $GLOBALS['compression'] = 'none'; 
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'csv';
        $GLOBALS['import_handle'] = @fopen($GLOBALS['import_file'], 'r');
        
        //setting for Ldi
        $GLOBALS['cfg']['Import']['ldi_replace'] = false;
        $GLOBALS['cfg']['Import']['ldi_ignore'] = false;
        $GLOBALS['cfg']['Import']['ldi_terminated'] = ';';
        $GLOBALS['cfg']['Import']['ldi_enclosed'] = '"';
        $GLOBALS['cfg']['Import']['ldi_escaped'] = '\\';
        $GLOBALS['cfg']['Import']['ldi_new_line'] = 'auto';
        $GLOBALS['cfg']['Import']['ldi_columns'] = '';
        $GLOBALS['cfg']['Import']['ldi_local_option'] = false;
        $GLOBALS['table'] = "phpmyadmintest";
        
        $this->object = new ImportLdi();    
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }
    
    /**
     * Test for getProperties
     *
     * @return void
     *
     * @group medium
     */
    public function testGetProperties()
    {
        $properties = $this->object->getProperties();
        $this->assertEquals(
            __('CSV using LOAD DATA'),
            $properties->getText()
        );  
        $this->assertEquals(
            'ldi',
            $properties->getExtension()
        );   
    }
    
    /**
     * Test for doImport
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImport()
    {
        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;
        
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;
        
        //Test function called
        $this->object->doImport();
        
        //asset that all sql are executed
        $this->assertContains(
            "LOAD DATA INFILE 'test/test_data/db_test_ldi.csv' INTO TABLE `phpmyadmintest`",
            $sql_query
        );     
   
        $this->assertEquals(
            true,
            $GLOBALS['finished']
        ); 
    
    }
}



