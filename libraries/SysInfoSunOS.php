<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold PMA\libraries\SysInfoSunOS class
 *
 * @package PMA
 */
namespace PMA\libraries;

/**
 * SunOS based SysInfo class
 *
 * @package PhpMyAdmin-sysinfo
 */
class SysInfoSunOS extends \PMA\libraries\SysInfo
{
    public $os = 'SunOS';

    /**
     * Read value from kstat
     *
     * @param string $key Key to read
     *
     * @return string with value
     */
    private function _kstat($key)
    {
        if ($m = shell_exec('kstat -p d ' . $key)) {
            list(, $value) = preg_split("/\t/", trim($m), 2);

            return $value;
        } else {
            return '';
        }
    }

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        $load1 = $this->_kstat('unix:0:system_misc:avenrun_1min');

        return array('loadavg' => $load1);
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return true on success
     */
    public function supported()
    {
        return @is_readable('/proc/meminfo');
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        $pagesize = $this->_kstat('unix:0:seg_cache:slab_size');
        $mem = array();
        $mem['MemTotal']
            = $this->_kstat('unix:0:system_pages:pagestotal') * $pagesize;
        $mem['MemUsed']
            = $this->_kstat('unix:0:system_pages:pageslocked') * $pagesize;
        $mem['MemFree']
            = $this->_kstat('unix:0:system_pages:pagesfree') * $pagesize;
        $mem['SwapTotal'] = $this->_kstat('unix:0:vminfo:swap_avail') / 1024;
        $mem['SwapUsed'] = $this->_kstat('unix:0:vminfo:swap_alloc') / 1024;
        $mem['SwapFree'] = $this->_kstat('unix:0:vminfo:swap_free') / 1024;

        return $mem;
    }
}