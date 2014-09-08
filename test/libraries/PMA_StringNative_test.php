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
    /** @var PMA_StringNative */
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
    public function testStrlen($length, $str)
    {
        $this->assertEquals(
            $length,
            $this->testObject->strlen($str)
        );
    }

    /**
     * Data provider for testStrlen
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
    public function testSubStr($str, $haystack, $start, $length)
    {
        $this->assertEquals(
            $str,
            $this->testObject->substr($haystack, $start, $length)
        );
    }

    /**
     * Data provider for testSubStr
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
     * Test for PMA_StringNative::substrCount
     *
     * @param int    $expected number of occurrences
     * @param string $haystack string to check
     * @param string $needle   string to count
     *
     * @return void
     * @test
     * @dataProvider substrCountData
     */
    public function testSubstrCount($expected, $haystack, $needle)
    {
        $this->assertEquals(
            $expected,
            $this->testObject->substrCount($haystack, $needle)
        );
    }

    /**
     * Data provider for testSubstrCount
     *
     * @return array Test data
     */
    public function substrCountData()
    {
        return array(
            array(1, "ab", "b"),
            array(1, "testdata", "data"),
            array(2, "testdata", "a"),
            array(0, "testdata", "b"),
        );
    }

    /**
     * Test for PMA_StringNative::substrCount
     *
     * @param string $haystack string to check
     * @param string $needle   string to count
     *
     * @return void
     * @test
     * @dataProvider substrCountDataException
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testSubstrCountException($haystack, $needle)
    {
        $this->testObject->substrCount($haystack, $needle);
    }

    /**
     * Data provider for testSubstrCountException
     *
     * @return array Test data
     */
    public function substrCountDataException()
    {
        return array(
            array("testdata", ""),
            array("testdata", null),
            array("testdata", false),
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
    public function testStrpos($pos, $haystack, $needle, $offset)
    {
        $this->assertEquals(
            $pos,
            $this->testObject->strpos($haystack, $needle, $offset)
        );
    }

    /**
     * Data provider for testStrpos
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
     * Test for PMA_StringNative::strrchr
     *
     * @param string $expected Expected substring
     * @param string $haystack String to cut
     * @param string $needle   Searched string
     *
     * @return void
     * @test
     * @dataProvider providerStrrchr
     */
    public function testStrrchr($expected, $haystack, $needle)
    {
        $this->assertEquals(
            $expected,
            $this->testObject->strrchr($haystack, $needle)
        );
    }

    /**
     * Data provider for testStrrchr
     *
     * @return array Test data
     */
    public function providerStrrchr()
    {
        return array(
            array('abcdef', 'abcdefabcdef', 'a'),
            array(false, 'abcdefabcdef', 'A'),
            array('f', 'abcdefabcdef', 'f'),
            array(false, 'abcdefabcdef', 'z'),
            array(false, 'abcdefabcdef', ''),
            array(false, 'abcdefabcdef', false),
            array(false, 'abcdefabcdef', true),
            array(false, '789456123', true),
            array(false, 'abcdefabcdef', null),
            array(false, null, null),
            array(false, null, 'a'),
            array(false, null, '0'),
            array(false, false, null),
            array(false, false, 'a'),
            array(false, false, '0'),
            array(false, true, null),
            array(false, true, 'a'),
            array(false, true, '0'),
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
    public function testStrToLower($expected, $string)
    {
        $this->assertEquals(
            $expected,
            $this->testObject->strtolower($string)
        );
    }

    /**
     * Data provider for testStrpos
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
