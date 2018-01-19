<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold PMA\libraries\SysInfo class
 *
 * @package PMA
 */
namespace PMA\libraries;

/**
 * Basic sysinfo class not providing any real data.
 *
 * @package PhpMyAdmin-sysinfo
 */
class SysInfo
{
    public $os = PHP_OS;

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        return array('loadavg' => 0);
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        return array();
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return true on success
     */
    public function supported()
    {
        return true;
    }
}
