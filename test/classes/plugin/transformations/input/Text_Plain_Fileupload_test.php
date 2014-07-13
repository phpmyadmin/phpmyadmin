<?php
/**
 * Tests for Text_Plain_Fileupload class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/plugins/transformations/input/'
    . 'Text_Plain_Fileupload.class.php';

/**
 * Tests Text_Plain_Fileupload class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Fileupload_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_Fileupload();
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
            = 'File upload functionality for TEXT columns. '
            . 'It does not have a textarea for input.';
        $this->assertEquals(
            $info,
            Text_Plain_Fileupload::getInfo()
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
            "Text file upload",
            Text_Plain_Fileupload::getName()
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
            Text_Plain_Fileupload::getMIMEType()
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
            Text_Plain_Fileupload::getMIMESubtype()
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
        $options = array();
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
            array(),
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
        $options = array();

        $actual = $this->object->getInputHtml(
            array(), 0, $column_name_appx, $options, ''
        );
        $expected = '<input type="file" name="fields_uploadtest"/>';
        $this->assertEquals(
            $expected,
            $actual
        );

        // Case 2
        $column_name_appx = '2ndtest';
        $options = array();
        $value = 'something';

        $actual = $this->object->getInputHtml(
            array(), 0, $column_name_appx, $options, $value
        );
        $expected = '<input type="hidden" name="fields_prev2ndtest" '
            . 'value="something"/><input type="hidden" name="fields2ndtest" '
            . 'value="something"/><input type="file" name="fields_upload2ndtest"/>';
        $this->assertEquals(
            $expected,
            $actual
        );
    }
}
