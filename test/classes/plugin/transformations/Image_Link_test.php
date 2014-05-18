<?php
/**
 * Tests for Image_JPEG_Link class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Image_JPEG_Link.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for Image_JPEG_Link class
 *
 * @package PhpMyAdmin-test
 */
class Image_JPEG_Link_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Image_JPEG_Link(new PluginManager());
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
        $info = __('Displays a link to download this image.');
        $this->assertEquals(
            $info,
            Image_JPEG_Link::getInfo()
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
            "ImageLink",
            Image_JPEG_Link::getName()
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
            Image_JPEG_Link::getMIMEType()
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
            "JPEG",
            Image_JPEG_Link::getMIMESubtype()
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
        $buffer = "PMA_IMAGE_LINK";
        $options = array("./image/", "200", "wrapper_link"=>"PMA_wrapper_link");
        $result = '<a class="disableAjax" target="_new"'
             . ' href="transformation_wrapper.phpPMA_wrapper_link"'
             . ' alt="PMA_IMAGE_LINK">[BLOB]</a>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
