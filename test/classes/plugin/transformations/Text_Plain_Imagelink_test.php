<?php
/**
 * Tests for Text_Plain_Imagelink class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_Imagelink.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for Text_Plain_Imagelink class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Imagelink_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_Imagelink(new PluginManager());
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
        $info = 'Displays an image and a link; '
            . 'the column contains the filename. The first option'
            . ' is a URL prefix like "http://www.example.com/". '
            . 'The second and third options'
            . ' are the width and the height in pixels.';
        $this->assertEquals(
            $info,
            Text_Plain_Imagelink::getInfo()
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
            "Image Link",
            Text_Plain_Imagelink::getName()
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
            Text_Plain_Imagelink::getMIMEType()
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
            Text_Plain_Imagelink::getMIMESubtype()
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
        $buffer = "PMA_IMAGE";
        $options = array("./image/", "200");
        $result = '<a href="./image/PMA_IMAGE" target="_blank">'
             . '<img src="./image/PMA_IMAGE" border="0" width="200" '
             . 'height="50" />PMA_IMAGE</a>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
