<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use com;
use Throwable;

use function array_merge;
use function class_exists;
use function intdiv;
use function intval;

/**
 * Windows NT based SysInfo class
 */
class WindowsNt extends Base
{
    /** @var object|null */
    private $wmiService = null;

    /**
     * The OS name
     *
     * @var string
     */
    public $os = 'WINNT';

    /**
     * Constructor to access to wmi database.
     */
    public function __construct()
    {
        if (! class_exists('com')) {
            return;
        }

        /**
         * @see https://www.php.net/manual/en/class.com.php
         * @see https://docs.microsoft.com/en-us/windows/win32/wmisdk/swbemlocator
         * @see https://docs.microsoft.com/en-us/windows/win32/wmisdk/swbemservices
         *
         * @psalm-suppress MixedAssignment, UndefinedMagicMethod, MixedMethodCall
         * @phpstan-ignore-next-line
         */
        $this->wmiService = (new com('WbemScripting.SWbemLocator'))->ConnectServer();
    }

    /**
     * Gets load information
     *
     * @return array<string, int> with load data
     */
    public function loadavg(): array
    {
        return ['loadavg' => $this->getLoadPercentage()];
    }

    /**
     * Checks whether class is supported in this environment
     */
    public static function isSupported(): bool
    {
        return class_exists('com');
    }

    /**
     * Gets information about memory usage
     *
     * @return array<string, int> with memory usage data
     * @psalm-return array{
     *     MemTotal: int,
     *     MemFree: int,
     *     MemUsed: int,
     *     SwapTotal: int,
     *     SwapUsed: int,
     *     SwapPeak: int,
     *     SwapFree: int
     * }
     */
    public function memory(): array
    {
        return array_merge($this->getSystemMemory(), $this->getPageFileUsage());
    }

    /**
     * @return array<string, int>
     * @psalm-return array{MemTotal: int, MemFree: int, MemUsed: int}
     */
    private function getSystemMemory(): array
    {
        if ($this->wmiService === null) {
            return ['MemTotal' => 0, 'MemFree' => 0, 'MemUsed' => 0];
        }

        /**
         * @see https://docs.microsoft.com/en-us/windows/win32/wmisdk/swbemobject-instances-
         * @see https://docs.microsoft.com/en-us/windows/win32/cimwin32prov/win32-operatingsystem
         *
         * @var object[] $instances
         * @psalm-suppress MixedMethodCall
         * @phpstan-ignore-next-line
         */
        $instances = $this->wmiService->Get('Win32_OperatingSystem')->Instances_();
        $totalMemory = 0;
        $freeMemory = 0;
        foreach ($instances as $instance) {
            // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $totalMemory += (int) $instance->TotalVisibleMemorySize; /* @phpstan-ignore-line */
            $freeMemory += (int) $instance->FreePhysicalMemory; /* @phpstan-ignore-line */
            // phpcs:enable
        }

        return ['MemTotal' => $totalMemory, 'MemFree' => $freeMemory, 'MemUsed' => $totalMemory - $freeMemory];
    }

    /**
     * @return array<string, int>
     * @psalm-return array{SwapTotal: int, SwapUsed: int, SwapPeak: int, SwapFree: int}
     */
    private function getPageFileUsage(): array
    {
        if ($this->wmiService === null) {
            return ['SwapTotal' => 0, 'SwapUsed' => 0, 'SwapPeak' => 0, 'SwapFree' => 0];
        }

        /**
         * @see https://docs.microsoft.com/en-us/windows/win32/wmisdk/swbemobject-instances-
         * @see https://docs.microsoft.com/en-us/windows/win32/cimwin32prov/win32-pagefileusage
         *
         * @var object[] $instances
         * @psalm-suppress MixedMethodCall
         * @phpstan-ignore-next-line
         */
        $instances = $this->wmiService->Get('Win32_PageFileUsage')->Instances_();
        $total = 0;
        $used = 0;
        $peak = 0;
        foreach ($instances as $instance) {
            // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $total += intval($instance->AllocatedBaseSize) * 1024; /* @phpstan-ignore-line */
            $used += intval($instance->CurrentUsage) * 1024; /* @phpstan-ignore-line */
            $peak += intval($instance->PeakUsage) * 1024; /* @phpstan-ignore-line */
            // phpcs:enable
        }

        return ['SwapTotal' => $total, 'SwapUsed' => $used, 'SwapPeak' => $peak, 'SwapFree' => $total - $used];
    }

    private function getLoadPercentage(): int
    {
        if ($this->wmiService === null) {
            return 0;
        }

        /**
         * @see https://docs.microsoft.com/en-us/windows/win32/wmisdk/swbemobject-instances-
         * @see https://docs.microsoft.com/en-us/windows/win32/cimwin32prov/win32-processor
         *
         * @var object[] $instances
         * @psalm-suppress MixedMethodCall
         * @phpstan-ignore-next-line
         */
        $instances = $this->wmiService->Get('Win32_Processor')->Instances_();
        $i = 0;
        $sum = 0;
        foreach ($instances as $instance) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            $sum += (int) $instance->LoadPercentage; /* @phpstan-ignore-line */
            // Can't use count($instances).
            $i++;
        }

        try {
            return intdiv($sum, $i);
        } catch (Throwable $throwable) {
            return 0;
        }
    }
}
