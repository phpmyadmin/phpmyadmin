<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

/**
 * Basic SysInfo class not providing any real data.
 */
class Base
{
    /**
     * Gets load information
     *
     * @return array<string, int> with load data
     */
    public function loadavg(): array
    {
        return ['loadavg' => 0];
    }

    /**
     * Gets information about memory usage
     *
     * @return array<string, int> with memory usage data
     */
    public function memory(): array
    {
        return [];
    }
}
