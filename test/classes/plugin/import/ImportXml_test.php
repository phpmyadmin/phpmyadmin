<?php
/**
 * Tests for ImportXml class
 *
 * @package PhpMyAdmin-test
 */
 
/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
$GLOBALS['server'] = 0;

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
require_once 'libraries/plugins/import/ImportXml.class.php';

/**
 * Tests for ImportXml class
 *
 * @package PhpMyAdmin-test
 */
class ImportXml_Test extends PHPUnit_Framework_TestCase
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
        global $compression, $import_handle, $read_multiply, $cfg;
        
        $this->object = new ImportXml();    

        //setting        
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin_importXML_For_Testing.xml';
        $GLOBALS['import_text'] = 'ImportXml_Test';
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 0;
        
        //global variable
        $compression = 'none'; 
        $read_multiply = 10;
        $import_file = 'test/test_data/phpmyadmin_importXML_For_Testing.xml';
        $import_type = 'Xml';
        $import_handle = @fopen($import_file, 'r');
        $cfg['AllowUserDropDatabase'] = false;
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
            __('XML'),
            $properties->getText()
        );  
        $this->assertEquals(
            'xml',
            $properties->getExtension()
        ); 
        $this->assertEquals(
            'text/xml',
            $properties->getMimeType()
        ); 
        $this->assertEquals(
            array(),
            $properties->getOptions()
        ); 
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText()
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
        //$import_notice will show the import detail result
        global $import_notice;        
        
        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;
        
        //Test function called
        $this->object->doImport();
 
        //If import successfully, PMA will show all databases and tables imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           phpmyadmintest (Options)
           pma_bookmarktest (Structure) (Options)
        */      
        $import_succesful_display_message =
               '<br /><br /><strong>The following structures have either '
             . 'been created or altered. Here you can:</strong>' 
             . '<br />' 
             
             . '<ul>' 
             .    '<li>View a structure\'s contents by clicking on its name</li>'
             .    '<li>Change any of its settings by clicking the corresponding "Options" link</li>'
             .    '<li>Edit structure by following the "Structure" link</li>'
             
             . '<br />' 
             .    '<li><a href="db_structure.php?db=phpmyadmintest&amp;lang=en&amp;token=token" '
             .    'title="Go to database: `phpmyadmintest`">phpmyadmintest</a> (<a href="'
             .    'db_operations.php?db=phpmyadmintest&amp;lang=en&amp;token=token" '
             .    'title="Edit settings for `phpmyadmintest`">Options</a>)' 
             .    '</li>' 
             
             . '<ul>' 
             .     '<li>'
             .     '<a href="sql.php?db=phpmyadmintest&amp;table=pma_bookmarktest&amp;lang=en&amp;token=token"'
             .     ' title="Go to table: `pma_bookmarktest`">pma_bookmarktest</a> '
             .     '(<a href="tbl_structure.php?db=phpmyadmintest&amp;table=pma_bookmarktest&amp;lang=en&amp;token=token"'
             .     ' title="Structure of `pma_bookmarktest`">Structure</a>) (<a href="tbl_operations.php?'
             .     'db=phpmyadmintest&amp;table=pma_bookmarktest&amp;lang=en&amp;token=token" title="Edit settings'
             .     ' for `pma_bookmarktest`">Options</a>)' 
             .     '</li>' 
             . '</ul>' 
             . '</ul>';
        
        //asset that all databases and tables are imported
        $this->assertEquals(
            $import_succesful_display_message,
            $import_notice
        );         
        $this->assertEquals(
            true,
            $GLOBALS['finished']
        ); 
    
    }
}



