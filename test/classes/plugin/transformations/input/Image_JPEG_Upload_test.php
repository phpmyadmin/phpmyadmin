<?php
/**
 * Tests for Image_JPEG_Upload class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/plugins/transformations/input/Image_JPEG_Upload.class.php';

/**
 * Tests for Image_JPEG_Upload class
 *
 * @package PhpMyAdmin-test
 */
class Image_JPEG_Upload_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Image_JPEG_Upload();
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
        $info
            = 'Image upload functionality which also displays a thumbnail.'
            . ' The options are the width and height of the thumbnail'
            . ' in pixels. Defaults to 100 X 100.';
        $this->assertEquals(
            $info,
            Image_JPEG_Upload::getInfo()
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
            "Image upload",
            Image_JPEG_Upload::getName()
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
            Image_JPEG_Upload::getMIMEType()
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
            Image_JPEG_Upload::getMIMESubtype()
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
        $buffer = 'test';
        $options = array('150', '100');
        $result = 'test';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );
    }

    /**
     * Test for getScripts
     *
     * @return void
     *
     * @group medium
     */
    public function testGetScripts()
    {
        $this->assertEquals(
            array(
                'transformations/image_upload.js'
            ),
            $this->object->getScripts()
        );
    }

    /**
     * Test for isSuccess
     *
     * @return void
     *
     * @group medium
     */
    public function testIsSuccess()
    {
        $this->assertEquals(
            true,
            $this->object->isSuccess()
        );
    }

    /**
     * Test for getError
     *
     * @return void
     *
     * @group medium
     */
    public function testGetError()
    {
        $this->assertEquals(
            '',
            $this->object->getError()
        );
    }

    /**
     * Test for getInputHtml
     *
     * @return void
     *
     * @group medium
     */
    public function testGetInputHtml()
    {
        // Case 1
        $column_name_appx = 'test';
        $options = array('150');

        $actual = $this->object->getInputHtml(
            array(), 0, $column_name_appx, $options, ''
        );
        $expected = '<img src="" width="150" height="100" '
            . 'alt="Image preview here"/><br/><input type="file" '
            . 'name="fields_uploadtest" accept="image/*" class="image-upload"/>';
        $this->assertEquals(
            $expected,
            $actual
        );

        // Case 2
        $column_name_appx = '2ndtest';
        $options = array(
            'wrapper_link' => '?table=a'
        );
        $value = 'something';

        $actual = $this->object->getInputHtml(
            array(), 0, $column_name_appx, $options, $value
        );
        $expected = '<input type="hidden" name="fields_prev2ndtest" '
            . 'value="736f6d657468696e67"/><input type="hidden" '
            . 'name="fields2ndtest" value="736f6d657468696e67"/>'
            . '<img src="transformation_wrapper.php?table=a" width="100" '
            . 'height="100" alt="Image preview here"/><br/><input type="file" '
            . 'name="fields_upload2ndtest" accept="image/*" class="image-upload"/>';
        $this->assertEquals(
            $expected,
            $actual
        );
    }
}
