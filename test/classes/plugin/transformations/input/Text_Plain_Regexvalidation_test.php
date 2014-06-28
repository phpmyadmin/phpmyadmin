<?php
/**
 * Tests for Text_Plain_RegexValidation class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/plugins/transformations/input/'
    . 'Text_Plain_Regexvalidation.class.php';

/**
 * Tests Text_Plain_RegexValidation class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_RegexValidation_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_RegexValidation();
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
            = 'Validates the string using regular expression '
            . 'and performs insert only if string matches it. '
            . 'The first option is the Regular Expression.';
        $this->assertEquals(
            $info,
            Text_Plain_RegexValidation::getInfo()
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
            "Regex Validation",
            Text_Plain_RegexValidation::getName()
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
            Text_Plain_RegexValidation::getMIMEType()
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
            Text_Plain_RegexValidation::getMIMESubtype()
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
        // Case 1
        $buffer = 'phpMyAdmin';
        $options = array('/php/i');
        $this->assertEquals(
            $buffer,
            $this->object->applyTransformation($buffer, $options)
        );
        $this->assertAttributeEquals(
            true,
            'success',
            $this->object
        );
        $this->assertAttributeEquals(
            '',
            'error',
            $this->object
        );

        // Case 2
        $buffer = 'qwerty';
        $this->assertEquals(
            $buffer,
            $this->object->applyTransformation($buffer, $options)
        );
        $this->assertAttributeEquals(
            false,
            'success',
            $this->object
        );
        $this->assertAttributeEquals(
            'Validation failed for the input string qwerty.',
            'error',
            $this->object
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
        // Case 1
        $buffer = 'PqRabCdEfgH';
        $options = array('/^[a-zA-Z]+$/');
        $this->object->applyTransformation($buffer, $options);
        $this->assertEquals(
            true,
            $this->object->isSuccess()
        );

        // Case 2
        $buffer = 'Abcde12sd';
        $this->object->applyTransformation($buffer, $options);
        $this->assertEquals(
            false,
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
        // Case 1
        $buffer = 'test';
        $options = array('/^a/');
        $this->object->applyTransformation($buffer, $options);
        $this->assertEquals(
            'Validation failed for the input string test.',
            $this->object->getError()
        );

        // Case 2
        $buffer = 'a';
        $this->object->applyTransformation($buffer, $options);
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
        $this->assertEquals(
            '',
            $this->object->getInputHtml(
                array(), 0, '', array(), ''
            )
        );
    }
}
