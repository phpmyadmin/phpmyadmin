<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server\SysInfo;

use PhpMyAdmin\Server\SysInfo\Base;
use PhpMyAdmin\Server\SysInfo\SysInfo;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(SysInfo::class)]
class SysInfoTest extends AbstractTestCase
{
    /**
     * Test for OS detection
     *
     * @param string $os       OS name as returned by PHP_OS
     * @param string $expected Expected detected OS name
     */
    #[DataProvider('sysInfoOsProvider')]
    public function testGetSysInfoOs(string $os, string $expected): void
    {
        self::assertSame(
            $expected,
            SysInfo::getOs($os),
        );
    }

    /**
     * Data provider for OS detection tests.
     *
     * @return string[][]
     */
    public static function sysInfoOsProvider(): array
    {
        return [['FreeBSD', 'Linux'], ['Linux', 'Linux'], ['Winnt', 'Winnt'], ['SunOS', 'SunOS']];
    }

    /**
     * Test for getting sysinfo object.
     */
    public function testGetSysInfo(): void
    {
        $sysInfo = SysInfo::get();
        self::assertNotSame(Base::class, $sysInfo::class);
    }
}
