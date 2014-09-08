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
