<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::extractValueFromFormattedSize from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

/**
 ** Test for PMA_Util::extractValueFromFormattedSize from common.lib
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_ExtractValueFromFormattedSize_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for extractValueFromFormattedSize
     *
     * @param int|string $size     Size
     * @param int        $expected Expected value
     *
     * @return void
     *
     * @dataProvider provider
     */
    function testExtractValueFromFormattedSize($size, $expected)
    {
        $this->assertEquals(
            $expected,
            PMA_Util::extractValueFromFormattedSize($size)
        );
    }

    /**
     * Data provider for testExtractValueFromFormattedSize
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array(100, -1),
            array("10GB", 10737418240),
            array("15MB", 15728640),
            array("256K", 262144)
        );
    }
}
