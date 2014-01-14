<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::convertBitDefaultValue from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

class PMA_ConvertBitDefaultValueTest extends PHPUnit_Framework_TestCase
{

    /**
     * Provider for testConvertBitDefaultValueTest
     *
     * @return array 
     *
     * @dataProvider dataProvider
     */
    function dataProvider()
    {
        return array(
            array("b'",""),
            array("b'01'","01"),
            array("b'010111010'","010111010")
        );
    }

    /**
     * Test for convertBitDefaultValue
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    function testConvertBitDefaultValueTest($bit, $val)
    {
        $this->assertEquals(
            $val, PMA_Util::convertBitDefaultValue($bit)
        );

    }
}
