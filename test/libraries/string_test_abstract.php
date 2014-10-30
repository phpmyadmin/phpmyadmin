<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract tests for string library with default set of tests
 *
 * @package PhpMyAdmin-test
 */

/**
 * tests for string library
 *
 * @package PhpMyAdmin-test
 */
abstract class PMA_StringTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test for mb_strlen
     *
     * @param integer $length Length of the string
     * @param string  $str    String to check for
     *
     * @return void
     * @test
     * @dataProvider providerStrlen
     */
    public function testStrlen($length, $str)
    {
        $this->assertEquals(
            $length,
            mb_strlen($str)
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
            array(2, "ab"),
            array(9, "test data"),
            array(0, ""),
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
     * @dataProvider providerSubstr
     */
    public function testSubStr($str, $haystack, $start, $length)
    {
        $this->assertEquals(
            $str,
            mb_substr($haystack, $start, $length)
        );
    }

    /**
     * Data provider for testSubStr
     *
     * @return array Test data
     */
    public function providerSubstr()
    {
        return array(
            array("b", "ab", 1, 1),
            array("data", "testdata", 4, 4),
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
     * @dataProvider providerSubstrCount
     */
    public function testSubstrCount($expected, $haystack, $needle)
    {
        $this->assertEquals(
            $expected,
            mb_substr_count($haystack, $needle)
        );
    }

    /**
     * Data provider for testSubstrCount
     *
     * @return array Test data
     */
    public function providerSubstrCount()
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
     * @dataProvider providerSubstrCountException
     *
     * @expectedException PHPUnit_Framework_Error
     */
    public function testSubstrCountException($haystack, $needle)
    {
        //No test. We're waiting for an exception.
        mb_substr_count($haystack, $needle);
    }

    /**
     * Data provider for testSubstrCountException
     *
     * @return array Test data
     */
    public function providerSubstrCountException()
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
     * @dataProvider providerStrpos
     */
    public function testStrpos($pos, $haystack, $needle, $offset = 0)
    {
        $this->assertEquals(
            $pos,
            mb_strpos($haystack, $needle, $offset)
        );
    }

    /**
     * Data provider for testStrpos
     *
     * @return array Test data
     */
    public function providerStrpos()
    {
        return array(
            array(1, "ab", "b", 0),
            array(4, "test data", " ", 0),
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
            mb_strrchr($haystack, $needle)
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
            array('123', '789456123', true),
            array(false, '7894560123', false),
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
     * @dataProvider providerStrtolower
     */
    public function testStrtolower($expected, $string)
    {
        $this->assertEquals(
            $expected,
            mb_strtolower($string)
        );
    }

    /**
     * Data provider for testStrtolower
     *
     * @return array Test data
     */
    public function providerStrtolower()
    {
        return array(
            array("mary had a", "Mary Had A"),
            array("test string", "TEST STRING")
        );
    }
}
