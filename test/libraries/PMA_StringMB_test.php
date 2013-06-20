<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Specialized String Functions (multi-byte) for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/StringMB.class.php';

/**
 * Tests for Specialized String Functions (multi-byte) for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */
class PMA_String_Mb_Test extends PHPUnit_Framework_TestCase
{
    protected $internal_encoding;

    /**
     * Setup function for test cases
     * 
     * @access protected
     * @return void
     */
    protected function setUp() 
    {
        $this->internal_encoding = mb_internal_encoding();
    }

    /**
     * TearDown function for tests, restores internal encoding
     * 
     * @access protected
     * @return void
     */
    protected function tearDown() 
    {
        mb_internal_encoding($this->internal_encoding);
    }

    /**
     * Test for PMA_StringMB::strlen
     * 
     * @param integer $length   Length of the string
     * @param string  $str      String to check for
     * @param string  $encoding Encoding of the string
     * 
     * @return void
     * @test
     * @dataProvider mbStrlenData
     */
    public function testMbStrlen($length, $str, $encoding)
    {   
        mb_internal_encoding($encoding);
        $this->assertEquals(
            $length,
            PMA_StringMB::strlen($str)
        );
    }

    /**
     * Data provider for testMbStrlen
     * 
     * @return array Test data
     */
    public function mbStrlenData() 
    {
        return array(
            array(2, "ab", "UTF-8"),
            array(9, "åèö - doo", "UTF-8"),
            array(12, "åèö - doo", "ISO-8859-1")
        );
    }

    /**
     * Test for PMA_StringMB::substr
     * 
     * @param string $str      Expected substring
     * @param string $haystack String to check in
     * @param int    $start    Starting position of substring
     * @param int    $length   Length of substring
     * @param string $encoding Encoding of the string
     * 
     * @return void
     * @test
     * @dataProvider mbSubStrData
     */
    public function testMbSubStr($str, $haystack, $start, $length, $encoding)
    {   
        mb_internal_encoding($encoding);
        $this->assertEquals(
            $str,
            PMA_StringMB::substr($haystack, $start, $length)
        );
    }

    /**
     * Data provider for testMbSubStr
     * 
     * @return array Test data
     */
    public function mbSubStrData() 
    {
        return array(
            array("b", "ab", 1, 1, "UTF-8"),
            array("èö", "åèö - doo", 1, 2, "UTF-8"),
        );
    }

    /**
     * Test for PMA_StringMB::strpos
     * 
     * @param int    $pos      Expected position
     * @param string $haystack String to search in
     * @param string $needle   String to search for
     * @param int    $offset   Search offset
     * @param string $encoding Encoding to test against
     * 
     * @return void
     * @test
     * @dataProvider mbStrposData
     */
    public function testMbStrpos($pos, $haystack, $needle, $offset, $encoding)
    {   
        mb_internal_encoding($encoding);
        $this->assertEquals(
            $pos,
            PMA_StringMB::strpos($haystack, $needle, $offset)
        );
    }

    /**
     * Data provider for testMbStrpos
     * 
     * @return array Test data
     */
    public function mbStrposData() 
    {
        return array(
            array(1, "ab", "b", 0, "UTF-8"),
            array(2, "åèö - doo", "ö", 0, "UTF-8"),
            array(6, "åèöè", "è", 3, "ISO-8859-1")
        );
    }

    /**
     * Test for PMA_StringMB::strtolower
     * 
     * @param string $expected Expected lowercased string
     * @param string $string   String to convert to lowercase
     * @param string $encoding Encoding to test against
     * 
     * @return void
     * @test
     * @dataProvider mbStrToLowerData
     */
    public function testMbStrToLower($expected, $string, $encoding)
    {   
        mb_internal_encoding($encoding);
        $this->assertEquals(
            $expected,
            PMA_StringMB::strtolower($string)
        );
    }

    /**
     * Data provider for testMbStrpos
     * 
     * @return array Test data
     */
    public function mbStrToLowerData() 
    {
        return array(
            array("mary had a", "Mary Had A", "UTF-8"),
            array("τάχιστη", "Τάχιστη", "UTF-8")
        );
    }

}
?>
