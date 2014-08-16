<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests to compare String Functions for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/StringNative.class.php';
require_once 'libraries/StringMB.class.php';

/**
 * Tests to compare String Functions for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */
class PMA_String_Compare_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMA_StringNative
     */
    private $_native;

    /**
     * @var PMA_StringMB
     */
    private $_mb;

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        if (!@function_exists('mb_strlen')) {
            $this->markTestSkipped('Multibyte String Functions are not available.');
        }
        $this->_native = new PMA_StringNative();
        $this->_mb = new PMA_StringMB();
    }

    /**
     * Tests for strlen
     *
     * @param mixed $value Value to test
     *
     * @return void
     * @test
     * @dataProvider providerStrlen
     */
    public function testStrlen($value)
    {
        $native = $this->_native->strlen($value);
        $multibytes = $this->_mb->strlen($value);
        $this->assertTrue(
            $native === $multibytes,
            'native length: ' . var_export($native, true)
            . ' - mb length: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrlen
     *
     * @return array Test data
     */
    public function providerStrlen()
    {
        return array(
            array('test'),
            array('3'),
            array(''),
            array(""),
            array(false),
            array(true),
            array(null),
            array(3),
            array(10),
        );
    }

    /**
     * Tests for substr
     *
     * @param mixed $value  Value to test
     * @param int   $start  Position to start cutting
     * @param int   $length Number of characters to cut
     *
     * @return void
     * @test
     * @dataProvider providerSubstr
     */
    public function testSubstr($value, $start, $length = 2147483647)
    {
        $native = $this->_native->substr($value, $start, $length);
        $multibytes = $this->_mb->substr($value, $start, $length);
        $this->assertTrue(
            $native === $multibytes,
            'native substr: ' . var_export($native, true)
            . ' - mb substr: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testSubstr
     *
     * @return array Test data
     */
    public function providerSubstr()
    {
        return array(
            array('abcdef', 0),
            array('abcdef', 0, 3),
            array('abcdef', 0, 10),
            array('abcdef', 0, -2),
            array('abcdef', 2),
            array('abcdef', 2, 3),
            array('abcdef', 2, 10),
            array('abcdef', 2, -1),
            array('abcdef', 2, -4),
            array('abcdef', 2, -5),
            array('abcdef', 6),
            array('abcdef', 6, 2),
            array('abcdef', 6, 10),
            array('abcdef', 6, -4),
            array('abcdef', -3),
            array('abcdef', -3, 1),
            array('abcdef', -3, 10),
            array('abcdef', -3, -1),
            array('abcdef', -3, -5),
            array(false, 0),
            array(false, 0, 2),
            array(false, 10),
            array(false, 10, 2),
            array(true, 0),
            array(true, 0, 1),
            array(true, 0, 10),
            array(true, 0, -1),
            array(true, 0, -10),
            array(true, 10),
            array(true, 10, 1),
            array(true, 10, 10),
            array(true, 10, -3),
            array(3, 0),
            array(3, 10),
            array('3', 0),
            array('3', 10),
            array('', 0),
            array('', 10),
            array("", 0),
            array("", 10),
        );
    }
}
