<?php
/**
 * Tests for Text_Plain_Append class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_Append.class.php';
require_once 'libraries/php-gettext/gettext.inc';
/**
 * Tests for Text_Plain_Append class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Append_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_Append(new PluginManager());
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
        $info = 'Appends text to a string. The only option is '
            . 'the text to be appended'
            . ' (enclosed in single quotes, default empty string).';
        $this->assertEquals(
            $info,
            Text_Plain_Append::getInfo()
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
            "Append",
            Text_Plain_Append::getName()
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
            Text_Plain_Append::getMIMEType()
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
            Text_Plain_Append::getMIMESubtype()
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
        $options = array("option1", "option2");
        $this->assertEquals(
            "PMA_BUFFERoption1",
            $this->object->applyTransformation($buffer, $options)
        );
        //no options
        $result = "PMA_BUFFER";
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer)
        );
        //html string
        $result = "PMA_BUFFER&lt;a&gt;abc&lt;/a&gt;";
        $options = array("<a>abc</a>");
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
