<?php
/**
 * Tests for Text_Plain_Longtoipv4 class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_Longtoipv4.class.php';

/**
 * Tests for Text_Plain_Longtoipv4 class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Longtoipv4_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_Longtoipv4(new PluginManager());
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
        $info = 'Converts an (IPv4) Internet network address into a string in'
            . ' Internet standard dotted format.';
        $this->assertEquals(
            $info,
            Text_Plain_Longtoipv4::getInfo()
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
            "Long To IPv4",
            Text_Plain_Longtoipv4::getName()
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
            "Text",
            Text_Plain_Longtoipv4::getMIMEType()
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
            "Plain",
            Text_Plain_Longtoipv4::getMIMESubtype()
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
        $buffer = 42949672;
        $options = array("option1", "option2");
        $this->assertEquals(
            "2.143.92.40",
            $this->object->applyTransformation($buffer, $options)
        );

        //too big
        $buffer = 4294967295;
        $options = array("option1", "option2");
        $this->assertEquals(
            "255.255.255.255",
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
