<?php
/**
 * Tests for ImportShp class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/import/ImportShp.class.php';
require_once 'libraries/Util.class.php';


/**
 * Tests for ImportShp class
 *
 * @package PhpMyAdmin-test
 */
class ImportShp_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['plugin_param'] = 'table';
        $this->object = new ImportShp(); 
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
            __('ESRI Shape File'),
            $properties->getText()
        );  
        $this->assertEquals(
            'shp',
            $properties->getExtension()
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
}
