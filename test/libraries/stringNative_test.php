<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for standard string library
 *
 * @package PhpMyAdmin-test
 */

require_once 'test/libraries/string_test_abstract.php';

/**
 * tests for string library
 *
 * @package PhpMyAdmin-test
 */
class PMA_StringNativeTest extends PMA_StringTest
{
    /**
     * Setup function for test cases
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        require_once 'libraries/string.lib.php';
        if (MULTIBYTES_STATUS === MULTIBYTES_ON) {
            $this->markTestSkipped(
                "Multibyte functions exist, can't test standard functions, skipping "
                . "test."
            );
        }
    }
}
