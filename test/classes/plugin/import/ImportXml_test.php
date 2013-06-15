<?php
/**
 * Tests for ImportXml class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/import/ImportXml.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';

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
        $GLOBALS['import_file'] = 'test/classes/plugin/import/phpmyadmin_importXML_For_Testing.xml';
        $GLOBALS['import_text'] = 'ImportXml_Test';
        $compression = 'none'; 
        $GLOBALS['offset'] = 0;
        $read_multiply = 10;
        $import_file = 'test/classes/plugin/import/phpmyadmin_importXML_For_Testing.xml';
        $import_type = 'Xml';
        $import_handle = @fopen($import_file, 'r');
        $cfg['AllowUserDropDatabase'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
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
        global $import_notice;
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
             ->method('isSuperuser')
             ->with('true');
        $GLOBALS['dbi'] = $dbi;
        $this->object->doImport();
 
        //If import successfully, PMA will show all database and tables imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           phpmyadmin (Options)
           p ma_bookmark (Structure) (Options)
        */      
        $import_succesful_display_message =
               '<br /><br /><strong>The following structures have either '
             . 'been created or altered. Here you can:</strong><br /><ul><li>View a structure\'s '
             . 'contents by clicking on its name</li><li>Change any of its settings by clicking '
             . 'the corresponding "Options" link</li><li>Edit structure by following the "Structure'
             . '" link</li><br /><li><a href="db_structure.php?db=phpmyadmin&amp;lang=en&amp;'
             . 'token=token" title="Go to database: `phpmyadmin`">phpmyadmin</a> (<a href="db_op'
             . 'erations.php?db=phpmyadmin&amp;lang=en&amp;token=token" title="Edit settings for'
             . ' `phpmyadmin`">Options</a>)</li><ul><li><a href="sql.php?db=phpmyadmin&amp;table'
             . '=pma_bookmark&amp;lang=en&amp;token=token" title="Go to table: `pma_bookmark`">pma_bookmark'
             . '</a> (<a href="tbl_structure.php?db=phpmyadmin&amp;table=pma_bookmark'
             . '&amp;lang=en&amp;token=token" title="Structure of `pma_bookmark`">Structure</a>)'
             . ' (<a href="tbl_operations.php?db=phpmyadmin&amp;table=pma_bookmark&amp;lang=en&amp;'
             . 'token=token" title="Edit settings for `pma_bookmark`">Options</a>)</li></ul></ul>';
        
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
