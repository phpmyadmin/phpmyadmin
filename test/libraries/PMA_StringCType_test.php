<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Specialized String Class (CType) for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/StringCType.class.php';

/**
 * Tests for Specialized String Class (CType) for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */
class PMA_StringCType_Test extends PHPUnit_Framework_TestCase
{
    private $_object;

    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        if (!@extension_loaded('ctype')) {
            $this->markTestSkipped(
                "ctype extension not present."
            );
        }
        $this->_object = new PMA_StringCType();
    }

    /**
     * Test for isAlnum()
     *
     * @param integer $expected Expected output
     * @param string  $str      String to check
     *
     * @return void
     * @test
     * @dataProvider isAlnumData
     */
    public function testIsAlnum($expected, $str)
    {
        $this->assertEquals(
            $expected,
            $this->_object->isAlnum($str)
        );
    }

    /**
     * Data provider for testIsAlnum
     *
     * @return array Test data
     */
    public function isAlnumData()
    {
        return array(
            array(true, "AbCd1zyZ9"),
            array(false, "foo!#bar")
        );
    }

    /**
     * Test for isAlpha()
     *
     * @param integer $expected Expected output
     * @param string  $str      String to check
     *
     * @return void
     * @test
     * @dataProvider isAlphaData
     */
    public function testIsAlpha($expected, $str)
    {
        $this->assertEquals(
            $expected,
            $this->_object->isAlpha($str)
        );
    }

    /**
     * Data provider for testIsAlpha
     *
     * @return array Test data
     */
    public function isAlphaData()
    {
        return array(
            array(true, "kJW"),
            array(false, "k12"),
        );
    }

    /**
     * Test for isDigit()
     *
     * @param integer $expected Expected output
     * @param string  $str      String to check
     *
     * @return void
     * @test
     * @dataProvider isDigitData
     */
    public function testIsDigit($expected, $str)
    {
        $this->assertEquals(
            $expected,
            $this->_object->isDigit($str)
        );
    }

    /**
     * Data provider for testIsDigit
     *
     * @return array Test data
     */
    public function isDigitData()
    {
        return array(
            array(false, "kJW"),
            array(false, "?.foo!#21"),
            array(true, "12"),
        );
    }

    /**
     * Test for isUpper()
     *
     * @param integer $expected Expected output
     * @param string  $str      String to check
     *
     * @return void
     * @test
     * @dataProvider isUpperData
     */
    public function testIsUpper($expected, $str)
    {
        $this->assertEquals(
            $expected,
            $this->_object->isUpper($str)
        );
    }

    /**
     * Data provider for testIsUpper
     *
     * @return array Test data
     */
    public function isUpperData()
    {
        return array(
            array(true, "ABCD"),
            array(false, "AbCD"),
            array(false, "ABCD12!3")
        );
    }

    /**
     * Test for isLower()
     *
     * @param integer $expected Expected output
     * @param string  $str      String to check
     *
     * @return void
     * @test
     * @dataProvider isLowerData
     */
    public function testIsLower($expected, $str)
    {
        $this->assertEquals(
            $expected,
            $this->_object->isLower($str)
        );
    }

    /**
     * Data provider for testIsLower
     *
     * @return array Test data
     */
    public function isLowerData()
    {
        return array(
            array(true, "abcd"),
            array(false, "aBcd"),
            array(false, "abcd12!3")
        );
    }

    /**
     * Test for isSpace()
     *
     * @param integer $expected Expected output
     * @param string  $str      String to check
     *
     * @return void
     * @test
     * @dataProvider isSpaceData
     */
    public function testIsSpace($expected, $str)
    {
        $this->assertEquals(
            $expected,
            $this->_object->isSpace($str)
        );
    }

    /**
     * Data provider for testIsSpace
     *
     * @return array Test data
     */
    public function isSpaceData()
    {
        return array(
            array(true, " "),
            array(false, '\n\r\t'),
            array(true, "\n\r\t"),
            array(false, "\ntest"),
        );
    }

    /**
     * Test for isHexDigit()
     *
     * @param integer $expected Expected output
     * @param string  $str      String to check
     *
     * @return void
     * @test
     * @dataProvider isHexDigitData
     */
    public function testIsHexDigit($expected, $str)
    {
        $this->assertEquals(
            $expected,
            $this->_object->isHexDigit($str)
        );
    }

    /**
     * Data provider for testIsHexDigit
     *
     * @return array Test data
     */
    public function isHexDigitData()
    {
        return array(
            array(true, "AB10BC99"),
            array(false, "AR1012"),
            array(true, "ab12bc99")
        );
    }

}
?>
