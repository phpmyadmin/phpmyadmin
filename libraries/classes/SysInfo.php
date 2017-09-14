<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about system memory and cpu.
 * Currently supports all Windows and Linux platforms
 *
 * This code is based on the OS Classes from the phpsysinfo project
 * (https://phpsysinfo.github.io/phpsysinfo/)
 *
 * @package PhpMyAdmin-sysinfo
 */
namespace PhpMyAdmin;

use PhpMyAdmin\SysInfoBase;

/**
 * PhpMyAdmin\SysInfo class
 *
 * @package PhpMyAdmin
 */
class SysInfo
{
    const MEMORY_REGEXP = '/^(MemTotal|MemFree|Cached|Buffers|SwapCached|SwapTotal|SwapFree):\s+(.*)\s*kB/im';

    /**
     * Returns OS type used for sysinfo class
     *
     * @param string $php_os PHP_OS constant
     *
     * @return string
     */
    public static function getOs($php_os = PHP_OS)
    {
        // look for common UNIX-like systems
        $unix_like = array('FreeBSD', 'DragonFly');
        if (in_array($php_os, $unix_like)) {
            $php_os = 'Linux';
        }

        return ucfirst($php_os);
    }

    /**
     * Gets sysinfo class mathing current OS
     *
     * @return PhpMyAdmin\SysInfoBase|mixed sysinfo class
     */
    public static function get()
    {
        $php_os = self::getOs();
        $supported = array('Linux', 'WINNT', 'SunOS');

        if (in_array($php_os, $supported)) {
            $class_name = 'PhpMyAdmin\SysInfo' . $php_os;
            /** @var PhpMyAdmin\SysInfoBase $ret */
            $ret = new $class_name();
            if ($ret->supported()) {
                return $ret;
            }
        }

        return new SysInfoBase();
    }
}
