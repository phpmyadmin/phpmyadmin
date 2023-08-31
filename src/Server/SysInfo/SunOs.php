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
     * Read value from kstat
     *
     * @param string $key Key to read
     *
     * @return string with value
     */
    private function kstat(string $key): string
    {
        /** @psalm-suppress ForbiddenCode */
        $m = shell_exec('kstat -p d ' . $key);

        if ($m) {
            [, $value] = explode("\t", trim($m), 2);

            return $value;
        }

        return '';
    }

    /**
     * Gets load information
     *
     * @return array<string, int> with load data
     */
    public function loadavg(): array
    {
        return ['loadavg' => (int) $this->kstat('unix:0:system_misc:avenrun_1min')];
    }

    /**
     * Checks whether class is supported in this environment
     */
    public static function isSupported(): bool
    {
        return @is_readable('/proc/meminfo');
    }

    /**
     * Gets information about memory usage
     *
     * @return array<string, int> with memory usage data
     */
    public function memory(): array
    {
        $pagesize = (int) $this->kstat('unix:0:seg_cache:slab_size');
        $mem = [];
        $mem['MemTotal'] = (int) $this->kstat('unix:0:system_pages:pagestotal') * $pagesize;
        $mem['MemUsed'] = (int) $this->kstat('unix:0:system_pages:pageslocked') * $pagesize;
        $mem['MemFree'] = (int) $this->kstat('unix:0:system_pages:pagesfree') * $pagesize;
        $mem['SwapTotal'] = (int) ((int) $this->kstat('unix:0:vminfo:swap_avail') / 1024);
        $mem['SwapUsed'] = (int) ((int) $this->kstat('unix:0:vminfo:swap_alloc') / 1024);
        $mem['SwapFree'] = (int) ((int) $this->kstat('unix:0:vminfo:swap_free') / 1024);

        return $mem;
    }
}
