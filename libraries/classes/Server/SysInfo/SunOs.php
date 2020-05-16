<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use function explode;
use function is_readable;
use function shell_exec;
use function trim;

/**
 * SunOS based SysInfo class
 */
class SunOs extends Base
{
    /**
     * The OS name
     *
     * @var string
     */
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
        $m = shell_exec('kstat -p d ' . $key);

        if ($m) {
            list(, $value) = explode("\t", trim($m), 2);

            return $value;
        }

        return '';
    }

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        $load1 = $this->_kstat('unix:0:system_misc:avenrun_1min');

        return ['loadavg' => $load1];
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return bool true on success
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
        $pagesize = (int) $this->_kstat('unix:0:seg_cache:slab_size');
        $mem = [];
        $mem['MemTotal'] = (int) $this->_kstat('unix:0:system_pages:pagestotal') * $pagesize;
        $mem['MemUsed'] = (int) $this->_kstat('unix:0:system_pages:pageslocked') * $pagesize;
        $mem['MemFree'] = (int) $this->_kstat('unix:0:system_pages:pagesfree') * $pagesize;
        $mem['SwapTotal'] = (int) $this->_kstat('unix:0:vminfo:swap_avail') / 1024;
        $mem['SwapUsed'] = (int) $this->_kstat('unix:0:vminfo:swap_alloc') / 1024;
        $mem['SwapFree'] = (int) $this->_kstat('unix:0:vminfo:swap_free') / 1024;

        return $mem;
    }
}
