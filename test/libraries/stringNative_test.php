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
        if (@function_exists('mb_strlen')) {
            $this->markTestSkipped(
                "Multibyte functions exist, can't test standard functions skipping "
                . "test."
            );
        }
        include_once 'libraries/stringNative.lib.php';
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
     * Data provider for testSubStr
     *
     * @return array Test data
     */
    public function providerSubstr()
    {
        return array(
            array("b", "ab", 1, 1),
            array("data", "testdata", 4, 4)
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
     * Data provider for testStrpos
     *
     * @return array Test data
     */
    public function providerStrpos()
    {
        return array(
            array(1, "ab", "b", 0),
            array(4, "test data", " ", 0)
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
     * Data provider for testStrpos
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
