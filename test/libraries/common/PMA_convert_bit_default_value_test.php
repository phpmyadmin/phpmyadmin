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

    function dataProvider()
    {
        return array(
            array("b'",""),
            array("b'01'","01"),
            array("b'010111010'","010111010")
        );
    }

    /**
     * @dataProvider dataProvider
     */
    function testConvert_bit_default_value_test($bit, $val)
    {
        $this->assertEquals(
            $val, PMA_Util::convertBitDefaultValue($bit)
        );

    }
}
