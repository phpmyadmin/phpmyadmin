<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_convert_bit_default_value from common.lib
 *
 * @package PhpMyAdmin-test
 * @version $Id: PMA_convert_bit_default_value_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_convert_bit_default_value_test extends PHPUnit_Framework_TestCase
{

    function dataProvider(){
        return array(
            array("b'",""),
            array("b'01'","01"),
            array("b'010111010'","010111010")
        );
    }

    /**
     * @dataProvider dataProvider
     */
    function testConvert_bit_default_value_test($bit, $val){
        $this->assertEquals($val, PMA_convert_bit_default_value($bit));

    }
}
