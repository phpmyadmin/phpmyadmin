<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::getFormattedMaximumUploadSize from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 ** Test for PMA_Util::getFormattedMaximumUploadSize from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_GetFormattedMaximumUploadSize_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Data provider for test
     *
     * @return array
     */
    public function dataProvider()
    {
        return array(
            array(10, __('B'), "10"),
            array(100, __('B'), "100"),
            array(1024, __('B'), "1,024"),
            array(102400, __('KiB'), "100"),
            array(10240000, __('MiB'), "10"),
            array(2147483648, __('MiB'), "2,048"),
            array(21474836480, __('GiB'), "20")
        );
    }

    /**
     * Test for PMA_Util::getFormattedMaximumUploadSize
     *
     * @param int    $size Size
     * @param string $unit Unit
     * @param string $res  Result
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    function testMaximumUploadSize($size, $unit, $res)
    {
        $this->assertEquals(
            "(" . __('Max: ') . $res . $unit . ")",
            PMA_Util::getFormattedMaximumUploadSize($size)
        );

    }
}
