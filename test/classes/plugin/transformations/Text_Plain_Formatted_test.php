<?php
/**
 * Tests for Text_Plain_Formatted class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_Formatted.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for Text_Plain_Formatted class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Formatted_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_Formatted(new PluginManager());
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
        $info = 'Displays the contents of the column as-is, without running it'
            . ' through htmlspecialchars(). That is, the column is assumed'
            . ' to contain valid HTML.';
        $this->assertEquals(
            $info,
            Text_Plain_Formatted::getInfo()
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
            "Formatted",
            Text_Plain_Formatted::getName()
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
            Text_Plain_Formatted::getMIMEType()
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
            Text_Plain_Formatted::getMIMESubtype()
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
        $buffer = "<a ref='http://ci.phpmyadmin.net/'>PMA_BUFFER</a>";
        $options = array("option1", "option2");
        $this->assertEquals(
            $buffer,
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
