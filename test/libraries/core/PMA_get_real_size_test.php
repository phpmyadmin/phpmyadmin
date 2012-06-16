<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getRealSize()  from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

class PMA_getRealSize_test extends PHPUnit_Framework_TestCase
{
    public function testNull()
    {
        $this->assertEquals(0, PMA_getRealSize('0'));
    }

    public function testKilobyte()
    {
        $this->assertEquals(1024, PMA_getRealSize('1kb'));
    }

    public function testKilobyte2()
    {
        $this->assertEquals(1024 * 1024, PMA_getRealSize('1024k'));
    }

    public function testMegabyte()
    {
        $this->assertEquals(8 * 1024 * 1024, PMA_getRealSize('8m'));
    }

    public function testGigabyte()
    {
        $this->assertEquals(12 * 1024 * 1024 * 1024, PMA_getRealSize('12gb'));
    }

    public function testUnspecified()
    {
        $this->assertEquals(1024, PMA_getRealSize('1024'));
    }
}
?>
