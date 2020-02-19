<?php
/**
 * tests for sysinfo library
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server\SysInfo;

use PhpMyAdmin\Server\SysInfo\SysInfo;
use PhpMyAdmin\Server\SysInfo\Base;
use PHPUnit\Framework\TestCase;

/**
 * tests for sysinfo library
 */
class SysInfoTest extends TestCase
{
    /**
     * Test for OS detection
     *
     * @param string $os       OS name as returned by PHP_OS
     * @param string $expected Expected detected OS name
     *
     * @dataProvider sysInfoOsProvider
     */
    public function testGetSysInfoOs($os, $expected): void
    {
        $this->assertEquals(
            $expected,
            SysInfo::getOs($os)
        );
    }

    /**
     * Data provider for OS detection tests.
     *
     * @return array with test data
     */
    public function sysInfoOsProvider()
    {
        return [
            [
                'FreeBSD',
                'Linux',
            ],
            [
                'Linux',
                'Linux',
            ],
            [
                'Winnt',
                'Winnt',
            ],
            [
                'SunOS',
                'SunOS',
            ],
        ];
    }

    /**
     * Test for getting sysinfo object.
     *
     * @return void
     */
    public function testGetSysInfo()
    {
        $this->assertInstanceOf(Base::class, SysInfo::get());
    }

    /**
     * Test for getting supported sysinfo object.
     *
     * @return void
     */
    public function testGetSysInfoSupported()
    {
        $this->assertTrue(SysInfo::get()->supported());
    }
}
