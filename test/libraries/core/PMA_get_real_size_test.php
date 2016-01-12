<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/**
 *
 * @package PhpMyAdmin-test
 */
class PMA_GetRealSize_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for
     *
     * @param string $size     Size
     * @param int    $expected Expected value
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testNull($size, $expected)
    {
        $this->assertEquals($expected, PMA_getRealSize($size));
    }

    /**
     * Data provider for testExtractValueFromFormattedSize
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array('0', 0),
            array('1kb', 1024),
            array('1024k', 1024 * 1024),
            array('8m', 8 * 1024 * 1024),
            array('12gb', 12 * 1024 * 1024 * 1024),
            array('1024', 1024),
        );
    }

}
