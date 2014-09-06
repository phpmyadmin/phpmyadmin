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
require_once 'test/libraries/PMA_StringNative_test.php';

/**
 * Tests for Specialized String Functions (multi-byte) for phpMyAdmin
 *
 * @package PhpMyAdmin-test
 */
class PMA_String_Mb_Test extends PMA_StringNative_Test
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
        if (@function_exists('mb_strlen')) {
            $this->internal_encoding = mb_internal_encoding();
            $this->testObject = new PMA_StringMB();
        } else {
            $this->markTestSkipped(
                "Multibyte functions don't exist, skipping test."
            );
        }
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
     * TearDown function for tests, restores internal encoding
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        if (isset($this->internal_encoding)) {
            mb_internal_encoding($this->internal_encoding);
        }
    }
}
?>
