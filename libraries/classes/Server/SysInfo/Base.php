<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use const PHP_OS;

/**
 * Basic SysInfo class not providing any real data.
 */
class Base
{
    /**
     * The OS name
     *
     * @var string
     */
    public $os = PHP_OS;

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

    /**
     * Checks whether class is supported in this environment
     */
    public static function isSupported(): bool
    {
        return true;
    }
}
