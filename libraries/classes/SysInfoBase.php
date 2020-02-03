<?php
/**
 * Hold PhpMyAdmin\SysInfoBase class
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use const PHP_OS;

/**
 * Basic sysinfo class not providing any real data.
 */
class SysInfoBase
{
    public $os = PHP_OS;

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        return ['loadavg' => 0];
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        return [];
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return bool true on success
     */
    public function supported()
    {
        return true;
    }
}
