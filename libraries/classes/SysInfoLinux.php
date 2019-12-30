<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold PhpMyAdmin\SysInfoLinux class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SysInfo;
use PhpMyAdmin\SysInfoBase;

/**
 * Linux based SysInfo class
 *
 * @package PhpMyAdmin
 */
class SysInfoLinux extends SysInfoBase
{
    public $os = 'Linux';

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        $buf = file_get_contents('/proc/stat');
        if ($buf === false) {
            $buf = '';
        }
        $pos = mb_strpos($buf, "\n");
        if ($pos === false) {
            $pos = 0;
        }
        $nums = preg_split(
            "/\s+/",
            mb_substr(
                $buf,
                0,
                $pos
            )
        );

        return [
            'busy' => (int) $nums[1] + (int) $nums[2] + (int) $nums[3],
            'idle' => (int) $nums[4],
        ];
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return bool true on success
     */
    public function supported()
    {
        return @is_readable('/proc/meminfo') && @is_readable('/proc/stat');
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        preg_match_all(
            SysInfo::MEMORY_REGEXP,
            file_get_contents('/proc/meminfo'),
            $matches
        );

        $mem = array_combine($matches[1], $matches[2]);

        $defaults = [
            'MemTotal'   => 0,
            'MemFree'    => 0,
            'Cached'     => 0,
            'Buffers'    => 0,
            'SwapTotal'  => 0,
            'SwapFree'   => 0,
            'SwapCached' => 0,
        ];

        $mem = array_merge($defaults, $mem);

        foreach ($mem as $idx => $value) {
            $mem[$idx] = intval($value);
        }

        $mem['MemUsed'] = $mem['MemTotal']
            - $mem['MemFree'] - $mem['Cached'] - $mem['Buffers'];

        $mem['SwapUsed'] = $mem['SwapTotal']
            - $mem['SwapFree'] - $mem['SwapCached'];

        return $mem;
    }
}
