<?php
/**
 * Tests for Image_PNG_Inline class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Image_PNG_Inline.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Config.class.php';
require_once 'libraries/config.default.php';

/**
 * Tests for Image_PNG_Inline class
 *
 * @package PhpMyAdmin-test
 */
class Image_PNG_Inline_Test extends PHPUnit_Framework_TestCase
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
        $GLOBALS['PMA_Config'] = new PMA_Config();
        $GLOBALS['PMA_Config']->enableBc();
        $this->object = new Image_PNG_Inline(new PluginManager());
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
        $info = 'Displays a clickable thumbnail. The options are the maximum width'
            . ' and height in pixels. The original aspect ratio is preserved.';
        $this->assertEquals(
            $info,
            Image_PNG_Inline::getInfo()
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
            "Inline",
            Image_PNG_Inline::getName()
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
            "Image",
            Image_PNG_Inline::getMIMEType()
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
            "PNG",
            Image_PNG_Inline::getMIMESubtype()
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
        $buffer = "PMA_PNG_Inline";
        $options = array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link");
        $result = '<a href="transformation_wrapper.phpPMA_wrapper_link"'
            . ' target="_blank"><img src="transformation_wrapper.php'
            . 'PMA_wrapper_link&amp;'
            . 'resize=jpeg&amp;newWidth=./image/&amp;newHeight=200" '
            . 'alt="PMA_PNG_Inline" border="0" /></a>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
