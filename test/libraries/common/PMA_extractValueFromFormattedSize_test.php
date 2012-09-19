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

class PMA_extractValueFromFormattedSize_test extends PHPUnit_Framework_TestCase
{

    function testExtractValueFromFormattedSizeNoFormat()
    {
        $this->assertEquals(
            -1,
            PMA_Util::extractValueFromFormattedSize(100)
        );
    }

    function testExtractValueFromFormattedSizeGB()
    {
        $this->assertEquals(
            10737418240,
            PMA_Util::extractValueFromFormattedSize("10GB")
        );
    }

    function testExtractValueFromFormattedSizeMB()
    {
        $this->assertEquals(
            15728640,
            PMA_Util::extractValueFromFormattedSize("15MB")
        );
    }

    function testExtractValueFromFormattedSizeK()
    {
        $this->assertEquals(
            262144,
            PMA_Util::extractValueFromFormattedSize("256K")
        );
    }
}
