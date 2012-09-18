<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for sysinfo library
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/sysinfo.lib.php';

/**
 * tests for sysinfo library
 *
 * @package PhpMyAdmin-test
 */
class PMA_SysInfoTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test for OS detection
     *
     * @param string $os       OS name as returned by PHP_OS
     * @param string $expected Expected detected OS name
     *
     * @return void
     *
     * @dataProvider sysInfoOsProvider
     */
    public function testGetSysInfoOs($os, $expected)
    {
        $this->assertEquals(
            $expected,
            PMA_getSysInfoOs($os)
        );
    }

    /**
     * Data provider for OS detection tests.
     *
     * @return array with test data
     */
    public function sysInfoOsProvider()
    {
        return array(
            array('FreeBSD', 'Linux'),
            array('Linux', 'Linux'),
            array('Winnt', 'Winnt'),
            array('SunOS', 'SunOS'),
        );
    }

    /**
     * Test for getting sysinfo object.
     *
     * @return void
     */
    public function testGetSysInfo()
    {
        $this->assertInstanceOf('PMA_SysInfo', PMA_getSysInfo());
    }

    /**
     * Test for getting supported sysinfo object.
     *
     * @return void
     */
    public function testGetSysInfoSupported()
    {
        $this->assertTrue(PMA_getSysInfo()->supported());
    }
}
