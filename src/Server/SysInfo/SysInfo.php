<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use function in_array;
use function ucfirst;

use const PHP_OS;

/**
 * Library for extracting information about system memory and cpu.
 * Currently supports all Windows and Linux platforms
 *
 * This code is based on the OS Classes from the phpsysinfo project
 * (https://phpsysinfo.github.io/phpsysinfo/)
 */
class SysInfo
{
    private static Base|null $instance = null;

    /**
     * Returns OS type used for sysinfo class
     *
     * @param string $phpOs PHP_OS constant
     */
    public static function getOs(string $phpOs = PHP_OS): string
    {
        // look for common UNIX-like systems
        if (in_array($phpOs, ['FreeBSD', 'DragonFly'], true)) {
            return 'Linux';
        }

        return ucfirst($phpOs);
    }

    /**
     * Gets SysInfo class matching current OS
     */
    public static function get(): Base
    {
        return self::$instance ??= match (self::getOs()) {
            'Linux' => Linux::isSupported() ? new Linux() : new Base(),
            'WINNT' => WindowsNt::isSupported() ? new WindowsNt() : new Base(),
            'SunOS' => SunOs::isSupported() ? new SunOs() : new Base(),
            default => new Base(),
        };
    }
}
