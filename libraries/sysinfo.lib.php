<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about system memory and cpu. Currently supports all
 * Windows and Linux plattforms
 *
 * This code is based on the OS Classes from the phpsysinfo project (http://phpsysinfo.sourceforge.net/)
 *
 * @package PhpMyAdmin
 */

/**
 * Wrap the PHP_OS constant
 *
 * @return string
 */
function PMA_getSysInfoOs()
{
    $php_os = PHP_OS;

    // look for common UNIX-like systems
    $unix_like = array('FreeBSD');
    if (in_array($php_os, $unix_like)) {
        $php_os = 'Linux';
    }

    return ucfirst($php_os);
}

/**
 * @return array
 */
function PMA_getSysInfo()
{
    $php_os = PMA_getSysInfoOs();
    $supported = array('Linux', 'WINNT', 'SunOS');

    $sysinfo = array();

    if (in_array($php_os, $supported)) {
        return eval("return new PMA_sysinfo".$php_os."();");
    }

    return new PMA_Sysinfo_Default;
}


class PMA_sysinfoWinnt
{
    private $_wmi;

    public $os = 'WINNT';

    public function __construct() {
        // initialize the wmi object
        $objLocator = new COM('WbemScripting.SWbemLocator');
        $this->_wmi = $objLocator->ConnectServer();
    }

    function loadavg() {
        $loadavg = "";
        $sum = 0;
        $buffer = $this->_getWMI('Win32_Processor', array('LoadPercentage'));

        foreach ($buffer as $load) {
            $value = $load['LoadPercentage'];
            $loadavg .= $value.' ';
            $sum += $value;
        }

        return array('loadavg' => $sum / count($buffer));
    }

    private function _getWMI($strClass, $strValue = array()) {
        $arrData = array();
        $value = "";

        $objWEBM = $this->_wmi->Get($strClass);
        $arrProp = $objWEBM->Properties_;
        $arrWEBMCol = $objWEBM->Instances_();
        foreach ($arrWEBMCol as $objItem) {
            if (is_array($arrProp)) {
                reset($arrProp);
            }
            $arrInstance = array();
            foreach ($arrProp as $propItem) {
                if ( empty($strValue)) {
                    eval("\$value = \$objItem->".$propItem->Name.";");
                    $arrInstance[$propItem->Name] = trim($value);
                } else {
                    if (in_array($propItem->Name, $strValue)) {
                        eval("\$value = \$objItem->".$propItem->Name.";");
                        $arrInstance[$propItem->Name] = trim($value);
                    }
                }
            }
            $arrData[] = $arrInstance;
        }
        return $arrData;
    }


    function memory() {
        $buffer = $this->_getWMI("Win32_OperatingSystem", array('TotalVisibleMemorySize', 'FreePhysicalMemory'));
        $mem = Array();
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

class PMA_sysinfoLinux
{
    public $os = 'Linux';

    function loadavg() {
        $buf = file_get_contents('/proc/stat');
        $nums=preg_split("/\s+/", substr($buf, 0, strpos($buf, "\n")));
        return Array('busy' => $nums[1]+$nums[2]+$nums[3], 'idle' => intval($nums[4]));
    }

    function memory() {
        preg_match_all('/^(MemTotal|MemFree|Cached|Buffers|SwapCached|SwapTotal|SwapFree):\s+(.*)\s*kB/im', file_get_contents('/proc/meminfo'), $matches);

        $mem = array_combine( $matches[1], $matches[2] );
        $mem['MemUsed'] = $mem['MemTotal'] - $mem['MemFree'] - $mem['Cached'] - $mem['Buffers'];
        $mem['SwapUsed'] = $mem['SwapTotal'] - $mem['SwapFree'] - $mem['SwapCached'];

        foreach ($mem as $idx=>$value)
            $mem[$idx] = intval($value);

        return $mem;
    }
}

class PMA_sysinfoSunos
{
    public $os = 'SunOS';

    private function _kstat($key)
    {
        if ($m = shell_exec('kstat -p d '.$key)) {
            list($key, $value) = preg_split("/\t/", trim($m), 2);
            return $value;
        } else {
            return '';
        }
    }

    public function loadavg() {
        $load1 = $this->_kstat('unix:0:system_misc:avenrun_1min');

        return array('loadavg' => $load1);
    }

    public function memory() {
        preg_match_all('/^(MemTotal|MemFree|Cached|Buffers|SwapCached|SwapTotal|SwapFree):\s+(.*)\s*kB/im', file_get_contents('/proc/meminfo'), $matches);

        $pagesize = $this->_kstat('unix:0:seg_cache:slab_size');
        $mem['MemTotal'] = $this->_kstat('unix:0:system_pages:pagestotal') * $pagesize;
        $mem['MemUsed'] = $this->_kstat('unix:0:system_pages:pageslocked') * $pagesize;
        $mem['MemFree'] = $this->_kstat('unix:0:system_pages:pagesfree') * $pagesize;
        $mem['SwapTotal'] = $this->_kstat('unix:0:vminfo:swap_avail') / 1024;
        $mem['SwapUsed'] = $this->_kstat('unix:0:vminfo:swap_alloc') / 1024;
        $mem['SwapFree'] = $this->_kstat('unix:0:vminfo:swap_free') / 1024;

        return $mem;
    }
}

class PMA_sysinfoDefault
{
    public $os = PHP_OS;

    public function loadavg() {
        return array('loadavg' => 0);
    }

    public function memory() {
        return array();
    }
}
