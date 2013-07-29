<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Specialized String Functions (native) for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/StringNative.class.php';

/**
 * Tests for Specialized String Functions (native) for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */
class PMA_StringNative_Test extends PHPUnit_Framework_TestCase
{
    protected $testObject;

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->testObject = new PMA_StringNative();
    }

    /**
     * Test for PMA_StringNative::strlen
     *
     * @param integer $length Length of the string
     * @param string  $str    String to check for
     *
     * @return void
     * @test
     * @dataProvider strlenData
     */
    public function testNativeStrlen($length, $str)
    {
        $this->assertEquals(
            $length,
            $this->testObject->strlen($str)
        );
    }

    /**
     * Data provider for testNativeStrlen
     *
     * @return array Test data
     */
    public function strlenData()
    {
        return array(
            array(2, "ab"),
            array(9, "test data"),
            array(0, "")
        );
    }

    /**
     * Test for PMA_StringNative::substr
     *
     * @param string $str      Expected substring
     * @param string $haystack String to check in
     * @param int    $start    Starting position of substring
     * @param int    $length   Length of substring
     *
     * @return void
     * @test
     * @dataProvider subStrData
     */
    public function testNativeSubStr($str, $haystack, $start, $length)
    {
        $this->assertEquals(
            $str,
            $this->testObject->substr($haystack, $start, $length)
        );
    }

    /**
     * Data provider for testNativeSubStr
     *
     * @return array Test data
     */
    public function subStrData()
    {
        return array(
            array("b", "ab", 1, 1),
            array("data", "testdata", 4, 4)
        );
    }

    /**
     * Test for PMA_StringNative::strpos
     *
     * @param int    $pos      Expected position
     * @param string $haystack String to search in
     * @param string $needle   String to search for
     * @param int    $offset   Search offset
     *
     * @return void
     * @test
     * @dataProvider strposData
     */
    public function testNativeStrpos($pos, $haystack, $needle, $offset)
    {
        $this->assertEquals(
            $pos,
            $this->testObject->strpos($haystack, $needle, $offset)
        );
    }

    /**
     * Data provider for testNativeStrpos
     *
     * @return array Test data
     */
    public function strposData()
    {
        return array(
            array(1, "ab", "b", 0),
            array(4, "test data", " ", 0)
        );
    }

    /**
     * Test for PMA_StringNative::strtolower
     *
     * @param string $expected Expected lowercased string
     * @param string $string   String to convert to lowercase
     *
     * @return void
     * @test
     * @dataProvider strToLowerData
     */
    public function testNativeStrToLower($expected, $string)
    {
        $this->assertEquals(
            $expected,
            $this->testObject->strtolower($string)
        );
    }

    /**
     * Data provider for testNativeStrpos
     *
     * @return array Test data
     */
    public function strToLowerData()
    {
        return array(
            array("mary had a", "Mary Had A"),
            array("test string", "TEST STRING")
        );
    }
}
?>
