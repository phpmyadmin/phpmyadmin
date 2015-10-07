<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA\libraries\Util::convertBitDefaultValue from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 ** Test for PMA\libraries\Util::convertBitDefaultValue from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_ConvertBitDefaultValueTest extends PHPUnit_Framework_TestCase
{

    /**
     * Provider for testConvertBitDefaultValueTest
     *
     * @return array
     *
     * @dataProvider dataProvider
     */
    public function dataProvider()
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
     * @param string $bit Value
     * @param string $val Expected value
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    public function testConvertBitDefaultValueTest($bit, $val)
    {
        $this->assertEquals(
            $val, PMA\libraries\Util::convertBitDefaultValue($bit)
        );

    }
}
