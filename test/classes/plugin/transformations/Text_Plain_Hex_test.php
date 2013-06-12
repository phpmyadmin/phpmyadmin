<?php
/**
 * Tests for Application_Octetstream_Hex class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Application_Octetstream_Hex.class.php';

/**
 * Tests for Application_Octetstream_Hex class
 *
 * @package PhpMyAdmin-test
 */
class Application_Octetstream_Hex_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Application_Octetstream_Hex(new PluginManager()); 
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
     * Test for getInfo
     *
     * @return void
     *
     * @group medium
     */
    public function testGetInfo()
    {
        $info = 
            'Displays hexadecimal representation of data. Optional first'
            . ' parameter specifies how often space will be added (defaults'
            . ' to 2 nibbles).';
        $this->assertEquals(
            $info,
            Application_Octetstream_Hex::getInfo()
        );  
    
    }

    /**
     * Test for getName
     *
     * @return void
     *
     * @group medium
     */
    public function testGetName()
    {       
        $this->assertEquals(
            "Hex",
            Application_Octetstream_Hex::getName()
        );    
    }

    /**
     * Test for getMIMEType
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMEType()
    {       
        $this->assertEquals(
            "Application",
            Application_Octetstream_Hex::getMIMEType()
        );    
    }

    /**
     * Test for getMIMESubtype
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMESubtype()
    {       
        $this->assertEquals(
            "OctetStream",
            Application_Octetstream_Hex::getMIMESubtype()
        );    
    }

    /**
     * Test for applyTransformation
     *
     * @return void
     *
     * @group medium
     */
    public function testApplyTransformation()
    {
        $buffer = "PMA_BUFFER";
        $options = array(3, "option2");
        $this->assertEquals(
            "504 d41 5f4 255 464 645 52 ",
            $this->object->applyTransformation($buffer, $options)
        );    
    }
}
