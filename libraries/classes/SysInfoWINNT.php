<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold PhpMyAdmin\SysInfoWINNT class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use COM;
use PhpMyAdmin\SysInfoBase;
use function is_string;

/**
 * Windows NT based SysInfo class
 *
 * @package PhpMyAdmin
 */
class SysInfoWINNT extends SysInfoBase
{
    private $_wmi;
    public $os = 'WINNT';

    /**
     * Constructor to access to wmi database.
     */
    public function __construct()
    {
        if (! class_exists('COM')) {
            $this->_wmi = null;
        } else {
            // initialize the wmi object
            $objLocator = new COM('WbemScripting.SWbemLocator');
            $this->_wmi = $objLocator->ConnectServer();
        }
    }

    /**
     * Gets load information
     *
     * @return array with load data
     */
    public function loadavg()
    {
        $sum = 0;
        $buffer = $this->_getWMI('Win32_Processor', ['LoadPercentage']);

        foreach ($buffer as $load) {
            $value = $load['LoadPercentage'];
            $sum += $value;
        }

        return ['loadavg' => $sum / count($buffer)];
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return bool true on success
     */
    public function supported()
    {
        return $this->_wmi !== null;
    }

    /**
     * Reads data from WMI
     *
     * @param string $strClass Class to read
     * @param array  $strValue Values to read
     *
     * @return array with results
     */
    private function _getWMI($strClass, array $strValue = [])
    {
        $arrData = [];

        $objWEBM = $this->_wmi->Get($strClass);
        $arrProp = $objWEBM->Properties_;
        $arrWEBMCol = $objWEBM->Instances_();
        foreach ($arrWEBMCol as $objItem) {
            $arrInstance = [];
            foreach ($arrProp as $propItem) {
                $name = $propItem->Name;
                if (empty($strValue) || in_array($name, $strValue)) {
                    $value = $objItem->$name;
                    if (is_string($value)) {
                        $arrInstance[$name] = trim($value);
                    } else {
                        $arrInstance[$name] = $value;
                    }
                }
            }
            $arrData[] = $arrInstance;
        }

        return $arrData;
    }

    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    public function memory()
    {
        $buffer = $this->_getWMI(
            "Win32_OperatingSystem",
            [
                'TotalVisibleMemorySize',
                'FreePhysicalMemory',
            ]
        );
        $mem = [];
        $mem['MemTotal'] = $buffer[0]['TotalVisibleMemorySize'];
        $mem['MemFree'] = $buffer[0]['FreePhysicalMemory'];
        $mem['MemUsed'] = $mem['MemTotal'] - $mem['MemFree'];

        $buffer = $this->_getWMI('Win32_PageFileUsage');

        $mem['SwapTotal'] = 0;
        $mem['SwapUsed'] = 0;
        $mem['SwapPeak'] = 0;

        foreach ($buffer as $swapdevice) {
            $mem['SwapTotal'] += $swapdevice['AllocatedBaseSize'] * 1024;
            $mem['SwapUsed'] += $swapdevice['CurrentUsage'] * 1024;
            $mem['SwapPeak'] += $swapdevice['PeakUsage'] * 1024;
        }

        return $mem;
    }
}
