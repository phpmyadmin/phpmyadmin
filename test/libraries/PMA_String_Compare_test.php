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
            array('abcdefabcdef', 0),
            array('abcdefabcdef', 0, 3),
            array('abcdefabcdef', 0, 10),
            array('abcdefabcdef', 0, -2),
            array('abcdefabcdef', 2),
            array('abcdefabcdef', 2, 3),
            array('abcdefabcdef', 2, 10),
            array('abcdefabcdef', 2, -1),
            array('abcdefabcdef', 2, -4),
            array('abcdefabcdef', 2, -5),
            array('abcdefabcdef', 6),
            array('abcdefabcdef', 6, 2),
            array('abcdefabcdef', 6, 10),
            array('abcdefabcdef', 6, -4),
            array('abcdefabcdef', -3),
            array('abcdefabcdef', -3, 1),
            array('abcdefabcdef', -3, 10),
            array('abcdefabcdef', -3, -1),
            array('abcdefabcdef', -3, -3),
            array('abcdefabcdef', -3, -5),
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
            array(3, 0, 1),
            array(3, 0, 2),
            array(3, 0, -1),
            array(3, 10),
            array(3, 10, 1),
            array(3, 10, 10),
            array(3, 10, -1),
            array('3', 0),
            array('3', 0, 1),
            array('3', 0, 2),
            array('3', 0, -1),
            array('3', 10),
            array('3', 10, 1),
            array('3', 10, 10),
            array('3', 10, -1),
            array('', 0),
            array('', 0, 1),
            array('', 0, 10),
            array('', 0, -1),
            array('', 10),
            array('', 10, 1),
            array('', 10, 10),
            array('', 10, -1),
            array(null, 10),
            array(null, 10, 1),
            array(null, 10, 10),
            array(null, 10, -1),
        );
    }

    /**
     * Tests for substr_count
     *
     * @param string $haystack String to look into
     * @param int    $needle   String to look for
     *
     * @return void
     * @test
     * @dataProvider providerSubstrCount
     */
    public function testSubstrCount($haystack, $needle)
    {
        $native = $this->_native->substrCount($haystack, $needle);
        $multibytes = $this->_mb->substrCount($haystack, $needle);
        $this->assertTrue(
            $native === $multibytes,
            'native substrCount: ' . var_export($native, true)
            . ' - mb substrCount: ' . var_export($multibytes, true)
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
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'ab'),
            array('abcdefabcdef', 'ba'),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'z'),
            array(false, 'a'),
            array(false, 0),
            array(true, 0),
            array(true, 0, 1),
            array(true, 1),
            array(true, 1, 1),
            array(3, 0),
            array(3, 3),
            array(3, '3'),
            array('3', '3'),
            array(null, 0),
            array('', 0),
        );
    }

    /**
     * Tests for substr_count
     *
     * @param string $haystack String to look into
     * @param int    $needle   String to look for
     *
     * @return void
     * @test
     * @dataProvider providerSubstrCountException
     */
    public function testSubstrCountException($haystack, $needle)
    {
        $native = null;
        $multibytes = null;
        $nativeException = false;
        $multibytesException = false;
        try {
            $native = $this->_native->substrCount($haystack, $needle);
        } catch (PHPUnit_Framework_Error $e) {
            $nativeException = true;
        }
        try {
            $multibytes = $this->_mb->substrCount($haystack, $needle);
        } catch (PHPUnit_Framework_Error $e) {
            $multibytesException = true;
        }

        $this->assertTrue(
            true === $nativeException && true === $multibytesException,
            'native substrCount: ' . var_export($native, true)
            . ' - mb substrCount: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testSubstrCountException
     *
     * @return array Test data
     */
    public function providerSubstrCountException()
    {
        return array(
            array('abcdefabcdef', false),
            array(false, false),
            array(null, false),
            array('', false),
        );
    }

    /**
     * Tests for strpos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrpos
     */
    public function testStrpos($haystack, $needle, $offset = 0)
    {
        $native = $this->_native->strpos($haystack, $needle, $offset);
        $multibytes = $this->_mb->strpos($haystack, $needle, $offset);
        $this->assertTrue(
            $native === $multibytes,
            'native strpos: ' . var_export($native, true)
            . ' - mb strpos: ' . var_export($multibytes, true)
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
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'a', 2),
            array('abcdefabcdef', 'a', 10),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 'A', 2),
            array('abcdefabcdef', 'A', 10),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'e', 2),
            array('abcdefabcdef', 'e', 10),
            array('abcdefabcdef', 'z'),
            array('abcdefabcdef', 'z', 2),
            array('abcdefabcdef', ord('a')),
            array('abcdefabcdef', ord('a'), 2),
            array('abcdefabcdef', ord('A')),
            array('abcdefabcdef', ord('A'), 2),
            array('abcdefabcdef', ord('e')),
            array('abcdefabcdef', ord('e'), 2),
            array('abcdefabcdef', ord('z')),
            array('abcdefabcdef', ord('z'), 2),
            array('abcdefabcdef', false),
            array(false, 'a'),
            array(false, 0),
            array(false, false),
            array(true, 0),
            array(true, 0, 1),
            array(true, 1),
            array(true, 1, 1),
            array(3, 0),
            array(3, 3),
            array(3, '3'),
            array('3', '3'),
            array(null, 0),
            array(null, false),
            array('', 0),
            array('', false),
        );
    }

    /**
     * Tests for strpos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrposException
     */
    public function testStrposException($haystack, $needle, $offset = 0)
    {
        $native = null;
        $multibytes = null;
        $nativeException = false;
        $multibytesException = false;
        try {
            $native = $this->_native->strpos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $nativeException = true;
        }
        try {
            $multibytes = $this->_mb->strpos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $multibytesException = true;
        }

        $this->assertTrue(
            true === $nativeException && true === $multibytesException,
            'native strpos: ' . var_export($native, true)
            . ' - mb strpos: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrposException
     *
     * @return array Test data
     */
    public function providerStrposException()
    {
        return array(
            array('abcdefabcdef', 'a', 20),
            array('abcdefabcdef', 'e', 20),
            array('abcdefabcdef', 'z', 20),
            array('abcdefabcdef', ord('a'), 20),
            array('abcdefabcdef', ord('e'), 20),
            array('abcdefabcdef', ord('z'), 20),
            array(false, 0, 1),
            array(3, 0, 2),
            array(3, 3, 2),
            array(3, '3', 2),
            array('3', '3', 2),
            array('', 0, 2),
        );
    }

    /**
     * Tests for stripos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStripos
     */
    public function testStripos($haystack, $needle, $offset = 0)
    {
        $native = $this->_native->stripos($haystack, $needle, $offset);
        $multibytes = $this->_mb->stripos($haystack, $needle, $offset);
        $this->assertTrue(
            $native === $multibytes,
            'native stripos: ' . var_export($native, true)
            . ' - mb stripos: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStripos
     *
     * @return array Test data
     */
    public function providerStripos()
    {
        return array(
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'a', 2),
            array('abcdefabcdef', 'a', 10),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 'A', 2),
            array('abcdefabcdef', 'A', 10),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'e', 2),
            array('abcdefabcdef', 'e', 10),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'z'),
            array('abcdefabcdef', 'z', 2),
            array('abcdefabcdef', ord('a')),
            array('abcdefabcdef', ord('a'), 2),
            array('abcdefabcdef', ord('A')),
            array('abcdefabcdef', ord('A'), 2),
            array('abcdefabcdef', ord('e')),
            array('abcdefabcdef', ord('e'), 2),
            array('abcdefabcdef', ord('z')),
            array('abcdefabcdef', ord('z'), 2),
            array('abcdefabcdef', false),
            array(false, 'a'),
            array(false, 0),
            array(false, 0, 1),
            array(false, false),
            array(true, 0),
            array(true, 0, 1),
            array(true, 1),
            array(true, 1, 1),
            array(3, 0),
            array(3, 3),
            array(3, '3'),
            array('3', '3'),
            array(null, 0),
            array(null, false),
            array('', 0),
            array('', 0, 2),
            array('', false),
        );
    }

    /**
     * Tests for stripos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStriposException
     */
    public function testStriposException($haystack, $needle, $offset = 0)
    {
        $native = null;
        $multibytes = null;
        $nativeException = false;
        $multibytesException = false;
        try {
            $native = $this->_native->stripos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $nativeException = true;
        }
        try {
            $multibytes = $this->_mb->stripos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $multibytesException = true;
        }

        $this->assertTrue(
            true === $nativeException && true === $multibytesException,
            'native stripos: ' . var_export($native, true)
            . ' - mb stripos: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStriposException
     *
     * @return array Test data
     */
    public function providerStriposException()
    {
        return array(
            array('abcdefabcdef', 'a', 20),
            array('abcdefabcdef', 'e', 20),
            array('abcdefabcdef', 'z', 20),
            array('abcdefabcdef', ord('a'), 20),
            array('abcdefabcdef', ord('e'), 20),
            array('abcdefabcdef', ord('z'), 20),
            array(3, 0, 2),
            array(3, 3, 2),
            array(3, '3', 2),
            array('3', '3', 2),
        );
    }

    /**
     * Tests for strrpos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrrpos
     */
    public function testStrrpos($haystack, $needle, $offset = 0)
    {
        $native = $this->_native->strrpos($haystack, $needle, $offset);
        $multibytes = $this->_mb->strrpos($haystack, $needle, $offset);
        $this->assertTrue(
            $native === $multibytes,
            'native strrpos: ' . var_export($native, true)
            . ' - mb strrpos: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrrpos
     *
     * @return array Test data
     */
    public function providerStrrpos()
    {
        return array(
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'a', 2),
            array('abcdefabcdef', 'a', 10),
            array('abcdefabcdef', 'a', -10),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 'A', 2),
            array('abcdefabcdef', 'A', 10),
            array('abcdefabcdef', 'A', -10),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'e', 2),
            array('abcdefabcdef', 'e', 10),
            array('abcdefabcdef', 'e', -2),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'z'),
            array('abcdefabcdef', 'z', 2),
            array('abcdefabcdef', 'z', -2),
            array('abcdefabcdef', ord('a')),
            array('abcdefabcdef', ord('a'), 2),
            array('abcdefabcdef', ord('a'), -2),
            array('abcdefabcdef', ord('A')),
            array('abcdefabcdef', ord('A'), 2),
            array('abcdefabcdef', ord('A'), -2),
            array('abcdefabcdef', ord('e')),
            array('abcdefabcdef', ord('e'), 2),
            array('abcdefabcdef', ord('e'), -2),
            array('abcdefabcdef', ord('z')),
            array('abcdefabcdef', ord('z'), 2),
            array('abcdefabcdef', ord('z'), -2),
            array('abcdefabcdef', false),
            array(false, 'a'),
            array(false, 0),
            array(false, 0, 1),
            array(false, false),
            array(true, 0),
            array(true, 0, 1),
            array(true, 1),
            array(true, 1, 1),
            array(3, 0),
            array(3, 3),
            array(3, '3'),
            array('3', '3'),
            array(null, 0),
            array(null, false),
            array('', 0),
            array('', 0, 2),
            array('', false),
        );
    }

    /**
     * Tests for strrpos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrrposException
     */
    public function testStrrposException($haystack, $needle, $offset = 0)
    {
        $native = null;
        $multibytes = null;
        $nativeException = false;
        $multibytesException = false;
        try {
            $native = $this->_native->strrpos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $nativeException = true;
        }
        try {
            $multibytes = $this->_mb->strrpos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $multibytesException = true;
        }

        $this->assertTrue(
            true === $nativeException && true === $multibytesException,
            'native strrpos: ' . var_export($native, true)
            . ' - mb strrpos: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrrposException
     *
     * @return array Test data
     */
    public function providerStrrposException()
    {
        return array(
            array('abcdefabcdef', 'a', 20),
            array('abcdefabcdef', 'a', -20),
            array('abcdefabcdef', 'e', 20),
            array('abcdefabcdef', 'e', -20),
            array('abcdefabcdef', 'z', 20),
            array('abcdefabcdef', 'z', -20),
            array('abcdefabcdef', ord('a'), 20),
            array('abcdefabcdef', ord('e'), 20),
            array('abcdefabcdef', ord('z'), 20),
            array(3, 0, 2),
            array(3, 3, 2),
            array(3, '3', 2),
            array('3', '3', 2),
        );
    }

    /**
     * Tests for strripos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrripos
     */
    public function testStrripos($haystack, $needle, $offset = 0)
    {
        $native = $this->_native->strripos($haystack, $needle, $offset);
        $multibytes = $this->_mb->strripos($haystack, $needle, $offset);
        $this->assertTrue(
            $native === $multibytes,
            'native strripos: ' . var_export($native, true)
            . ' - mb strripos: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrripos
     *
     * @return array Test data
     */
    public function providerStrripos()
    {
        return array(
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'a', 2),
            array('abcdefabcdef', 'a', 10),
            array('abcdefabcdef', 'a', -10),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 'A', 2),
            array('abcdefabcdef', 'A', 10),
            array('abcdefabcdef', 'A', -10),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'e', 2),
            array('abcdefabcdef', 'e', 10),
            array('abcdefabcdef', 'e', -2),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'z'),
            array('abcdefabcdef', 'z', 2),
            array('abcdefabcdef', 'z', -2),
            array('abcdefabcdef', ord('a')),
            array('abcdefabcdef', ord('a'), 2),
            array('abcdefabcdef', ord('a'), -2),
            array('abcdefabcdef', ord('A')),
            array('abcdefabcdef', ord('A'), 2),
            array('abcdefabcdef', ord('A'), -2),
            array('abcdefabcdef', ord('e')),
            array('abcdefabcdef', ord('e'), 2),
            array('abcdefabcdef', ord('e'), -2),
            array('abcdefabcdef', ord('z')),
            array('abcdefabcdef', ord('z'), 2),
            array('abcdefabcdef', ord('z'), -2),
            array('abcdefabcdef', false),
            array(false, 'a'),
            array(false, 0),
            array(false, 0, 1),
            array(false, false),
            array(true, 0),
            array(true, 0, 1),
            array(true, 1),
            array(true, 1, 1),
            array(3, 0),
            array(3, 3),
            array(3, '3'),
            array('3', '3'),
            array(null, 0),
            array(null, false),
            array('', 0),
            array('', 0, 2),
            array('', false),
        );
    }

    /**
     * Tests for strripos
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     * @param int    $offset   Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrriposException
     */
    public function testStrriposException($haystack, $needle, $offset = 0)
    {
        $native = null;
        $multibytes = null;
        $nativeException = false;
        $multibytesException = false;
        try {
            $native = $this->_native->strripos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $nativeException = true;
        }
        try {
            $multibytes = $this->_mb->strripos($haystack, $needle, $offset);
        } catch (PHPUnit_Framework_Error $e) {
            $multibytesException = true;
        }

        $this->assertTrue(
            true === $nativeException && true === $multibytesException,
            'native strripos: ' . var_export($native, true)
            . ' - mb strripos: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrriposException
     *
     * @return array Test data
     */
    public function providerStrriposException()
    {
        return array(
            array('abcdefabcdef', 'a', 20),
            array('abcdefabcdef', 'a', -20),
            array('abcdefabcdef', 'e', 20),
            array('abcdefabcdef', 'e', -20),
            array('abcdefabcdef', 'z', 20),
            array('abcdefabcdef', 'z', -20),
            array('abcdefabcdef', ord('a'), 20),
            array('abcdefabcdef', ord('e'), 20),
            array('abcdefabcdef', ord('z'), 20),
            array(3, 0, 2),
            array(3, 3, 2),
            array(3, '3', 2),
            array('3', '3', 2),
        );
    }

    /**
     * Tests for strstr
     *
     * @param string $haystack      String to search in
     * @param mixed  $needle        Characters to search
     * @param bool   $before_needle Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrstr
     */
    public function testStrstr($haystack, $needle, $before_needle = false)
    {
        $native = $this->_native->strstr($haystack, $needle, $before_needle);
        $multibytes = $this->_mb->strstr($haystack, $needle, $before_needle);
        $this->assertTrue(
            $native === $multibytes,
            'native strstr: ' . var_export($native, true)
            . ' - mb strstr: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrstr
     *
     * @return array Test data
     */
    public function providerStrstr()
    {
        return array(
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'a', true),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 'A', true),
            array('abcdefabcdef', 97),
            array('abcdefabcdef', 97, true),
            array('abcdefabcdef', 65),
            array('abcdefabcdef', 65, true),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'e', true),
            array('abcdefabcdef', 'z'),
            array('abcdefabcdef', 'z', true),
            array('abcdefabcdef', null),
            array('abcdefabcdef', null, true),
            array('abcdefabcdef', false),
            array('abcdefabcdef', false, true),
            array(false, 'a'),
            array(false, false),
            array(true, 0),
            array(true, 1),
            array(true, true),
            array(true, true, true),
            array(3, 0),
            array(3, 3),
            array(3, 3, true),
            array(123456789, 0),
            array(123456789, 3),
            array(123456789, 3, true),
            array('3', '3'),
            array('3', '3', true),
            array('123456789', 3),
            array('123456789', 3, true),
            array('123456789', 49), //ASCII 49 = 1
            array('123456789', 49, true),
            array(null, 0),
            array(null, null),
            array('', 0),
            array('', false),
            array('', null),
        );
    }

    /**
     * Tests for strstr
     *
     * @param string $haystack      String to search in
     * @param mixed  $needle        Characters to search
     * @param bool   $before_needle Start position
     *
     * @return void
     * @test
     * @dataProvider providerStrstrException
     */
    public function testStrstrException($haystack, $needle, $before_needle = false)
    {
        $native = null;
        $multibytes = null;
        $nativeException = false;
        $multibytesException = false;
        try {
            $native = $this->_native->strstr($haystack, $needle, $before_needle);
        } catch (PHPUnit_Framework_Error $e) {
            $nativeException = true;
        }
        try {
            $multibytes = $this->_mb->strstr($haystack, $needle, $before_needle);
        } catch (PHPUnit_Framework_Error $e) {
            $multibytesException = true;
        }

        $this->assertTrue(
            true === $nativeException && true === $multibytesException,
            'native strstr: ' . var_export($native, true)
            . ' - mb strstr: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrstrException
     *
     * @return array Test data
     */
    public function providerStrstrException()
    {
        return array(
            array('abcdefabcdef', ''),
            array('abcdefabcdef', '', true),
        );
    }

    /**
     * Tests for stristr
     *
     * @param string $haystack      String to search in
     * @param mixed  $needle        Characters to search
     * @param bool   $before_needle Start position
     *
     * @return void
     * @test
     * @dataProvider providerStristr
     */
    public function testStristr($haystack, $needle, $before_needle = false)
    {
        $this->markTestSkipped('Skip until hhvm implements third parameter.');
        $native = $this->_native->stristr($haystack, $needle, $before_needle);
        $multibytes = $this->_mb->stristr($haystack, $needle, $before_needle);
        $this->assertTrue(
            $native === $multibytes,
            'native stristr: ' . var_export($native, true)
            . ' - mb stristr: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStristr
     *
     * @return array Test data
     */
    public function providerStristr()
    {
        return array(
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'a', true),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 'A', true),
            array('abcdefabcdef', 97),
            array('abcdefabcdef', 97, true),
            array('abcdefabcdef', 65),
            array('abcdefabcdef', 65, true),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'e', true),
            array('abcdefabcdef', 'z'),
            array('abcdefabcdef', 'z', true),
            array('abcdefabcdef', null),
            array('abcdefabcdef', null, true),
            array('abcdefabcdef', false),
            array('abcdefabcdef', false, true),
            array(false, 'a'),
            array(false, false),
            array(true, 0),
            array(true, 1),
            array(true, true),
            array(true, true, true),
            array(3, 0),
            array(3, 3),
            array(3, 3, true),
            array(123456789, 0),
            array(123456789, 3),
            array(123456789, 3, true),
            array('3', '3'),
            array('3', '3', true),
            array('123456789', 3),
            array('123456789', 3, true),
            array('123456789', 49), //ASCII 49 = 1
            array('123456789', 49, true),
            array(null, 0),
            array(null, null),
            array('', 0),
            array('', false),
            array('', null),
        );
    }

    /**
     * Tests for stristr
     *
     * @param string $haystack      String to search in
     * @param mixed  $needle        Characters to search
     * @param bool   $before_needle Start position
     *
     * @return void
     * @test
     * @dataProvider providerStristrException
     */
    public function testStristrException($haystack, $needle, $before_needle = false)
    {
        $this->markTestSkipped('Skip until hhvm implements third parameter.');
        $native = null;
        $multibytes = null;
        $nativeException = false;
        $multibytesException = false;
        try {
            $native = $this->_native->stristr($haystack, $needle, $before_needle);
        } catch (PHPUnit_Framework_Error $e) {
            $nativeException = true;
        }
        try {
            $multibytes = $this->_mb->stristr($haystack, $needle, $before_needle);
        } catch (PHPUnit_Framework_Error $e) {
            $multibytesException = true;
        }

        $this->assertTrue(
            true === $nativeException && true === $multibytesException,
            'native stristr: ' . var_export($native, true)
            . ' - mb stristr: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStristrException
     *
     * @return array Test data
     */
    public function providerStristrException()
    {
        return array(
            array('abcdefabcdef', ''),
            array('abcdefabcdef', '', true),
        );
    }

    /**
     * Tests for strrchr
     *
     * @param string $haystack String to search in
     * @param mixed  $needle   Characters to search
     *
     * @return void
     * @test
     * @dataProvider providerStrstr
     */
    public function testStrrchr($haystack, $needle)
    {
        $native = $this->_native->strrchr($haystack, $needle);
        $multibytes = $this->_mb->strrchr($haystack, $needle);
        $this->assertTrue(
            $native === $multibytes,
            'native strrchr: ' . var_export($native, true)
            . ' - mb strrchr: ' . var_export($multibytes, true)
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
            array('abcdefabcdef', 'a'),
            array('abcdefabcdef', 'A'),
            array('abcdefabcdef', 97),
            array('abcdefabcdef', 65),
            array('abcdefabcdef', 'e'),
            array('abcdefabcdef', 'z'),
            array('abcdefabcdef', ''),
            array('abcdefabcdef', null),
            array('abcdefabcdef', false),
            array(false, 'a'),
            array(false, false),
            array(true, 0),
            array(true, 1),
            array(true, true),
            array(3, 0),
            array(3, 3),
            array(123456789, 0),
            array(123456789, 3),
            array('3', '3'),
            array('123456789', 3),
            array('123456789', 49), //ASCII 49 = 1
            array(null, 0),
            array(null, null),
            array('', 0),
            array('', false),
            array('', null),
        );
    }

    /**
     * Tests for strtolower
     *
     * @param string $str Input string
     *
     * @return void
     * @test
     * @dataProvider providerCase
     */
    public function testStrtolower($str)
    {
        $native = $this->_native->strtolower($str);
        $multibytes = $this->_mb->strtolower($str);
        $this->assertTrue(
            $native === $multibytes,
            'native strtolower: ' . var_export($native, true)
            . ' - mb strtolower: ' . var_export($multibytes, true)
        );
    }

    /**
     * Tests for strtoupper
     *
     * @param string $str Input string
     *
     * @return void
     * @test
     * @dataProvider providerCase
     */
    public function testStrtoupper($str)
    {
        $native = $this->_native->strtoupper($str);
        $multibytes = $this->_mb->strtoupper($str);
        $this->assertTrue(
            $native === $multibytes,
            'native strtoupper: ' . var_export($native, true)
            . ' - mb strtoupper: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testStrtolower and testStrtoupper
     *
     * @return array Test data
     */
    public function providerCase()
    {
        return array(
            array('abcdefabcdef'),
            array('abcdefABCDEF'),
            //array('abcdefABCDEFàéÀÉ'), //Error with those characters.
            array('abcdefABCDEF@+12345'),
            array(false),
            array(true),
            array(3),
            array(123456789),
            array('3'),
            array('4k'),
            array('4kb'),
            array('4K'),
            array('4KB'),
            array('123456789'),
            array(null),
            array(''),
        );
    }

    /**
     * Tests for ord
     *
     * @param string $chr Input char
     *
     * @return void
     * @test
     * @dataProvider providerOrd
     */
    public function testOrd($chr)
    {
        $native = $this->_native->ord($chr);
        $multibytes = $this->_mb->ord($chr);
        $this->assertTrue(
            $native === $multibytes,
            'native ord: ' . var_export($native, true)
            . ' - mb ord: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testOrd
     *
     * @return array Test data
     */
    public function providerOrd()
    {
        return array(
            array('a'),
            array('A'),
            array('az'),
            array('AZ'),
            array('3a'),
            array(3),
            array(3.1),
            array(true),
            array(false),
            array(null),
            array(''),
        );
    }

    /**
     * Tests for chr
     *
     * @param string $ascii Ascii code
     *
     * @return void
     * @test
     * @dataProvider providerChr
     */
    public function testChr($ascii)
    {
        $native = $this->_native->chr($ascii);
        $multibytes = $this->_mb->chr($ascii);
        $this->assertTrue(
            $native === $multibytes,
            'native chr: ' . var_export($native, true)
            . ' - mb chr: ' . var_export($multibytes, true)
        );
    }

    /**
     * Data provider for testChr
     *
     * @return array Test data
     */
    public function providerChr()
    {
        return array(
            array('a'),
            array('A'),
            array('az'),
            array('AZ'),
            array('a3'),
            array(3),
            array(3.1),
            array(false),
            array(true),
            array(null),
            array(''),
        );
    }
}
