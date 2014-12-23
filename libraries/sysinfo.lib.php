<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about system memory and cpu.
 * Currently supports all Windows and Linux plattforms
 *
 * This code is based on the OS Classes from the phpsysinfo project
 * (http://phpsysinfo.sourceforge.net/)
 *
 * @package PhpMyAdmin-sysinfo
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

define(
    'MEMORY_REGEXP',
    '/^(MemTotal|MemFree|Cached|Buffers|SwapCached|SwapTotal|SwapFree):'
    . '\s+(.*)\s*kB/im'
);

/**
 * Returns OS type used for sysinfo class
 *
 * @param string $php_os PHP_OS constant
 *
 * @return string
 */
function PMA_getSysInfoOs($php_os = PHP_OS)
{

    // look for common UNIX-like systems
    $unix_like = array('FreeBSD', 'DragonFly');
    if (in_array($php_os, $unix_like)) {
        $php_os = 'Linux';
    }

    return ucfirst($php_os);
}

/**
 * Gets sysinfo class mathing current OS
 *
 * @return PMA_SysInfo|mixed sysinfo class
 */
function PMA_getSysInfo()
{
    $php_os = PMA_getSysInfoOs();
    $supported = array('Linux', 'WINNT', 'SunOS');

    if (in_array($php_os, $supported)) {
        $class_name = 'PMA_SysInfo' . $php_os;
        $ret = new $class_name();
        if ($ret->supported()) {
            return $ret;
        }
    }

    return new PMA_SysInfo();
}

/**
 * Basic sysinfo class not providing any real data.
 *
 * @package PhpMyAdmin-sysinfo
 */
class PMA_SysInfo
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

/**
 * Windows NT based SysInfo class
 *
 * @package PhpMyAdmin-sysinfo
 */
class PMA_SysInfoWinnt extends PMA_SysInfo
{
    private $_wmi;

    public $os = 'WINNT';

    /**
     * Constructor to access to wmi database.
     */
    public function __construct()
    {
        if (!class_exists('COM')) {
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
    function loadavg()
    {
        $loadavg = "";
        $sum = 0;
        $buffer = $this->_getWMI('Win32_Processor', array('LoadPercentage'));

        foreach ($buffer as $load) {
            $value = $load['LoadPercentage'];
            $loadavg .= $value . ' ';
            $sum += $value;
        }

        return array('loadavg' => $sum / count($buffer));
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return true on success
     */
    public function supported()
    {
        return !is_null($this->_wmi);
    }

    /**
     * Reads data from WMI
     *
     * @param string $strClass Class to read
     * @param array  $strValue Values to read
     *
     * @return array with results
     */
    private function _getWMI($strClass, $strValue = array())
    {
        $arrData = array();

        $objWEBM = $this->_wmi->Get($strClass);
        $arrProp = $objWEBM->Properties_;
        $arrWEBMCol = $objWEBM->Instances_();
        foreach ($arrWEBMCol as $objItem) {
            if (is_array($arrProp)) {
                reset($arrProp);
            }
            $arrInstance = array();
            foreach ($arrProp as $propItem) {
                $name = $propItem->Name;
                if ( empty($strValue) || in_array($name, $strValue)) {
                    $value = $objItem->$name;
                    $arrInstance[$name] = trim($value);
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
    function memory()
    {
        $buffer = $this->_getWMI(
            "Win32_OperatingSystem",
            array('TotalVisibleMemorySize', 'FreePhysicalMemory')
        );
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

/**
 * Linux based SysInfo class
 *
 * @package PhpMyAdmin-sysinfo
 */
class PMA_SysInfoLinux extends PMA_SysInfo
{
    public $os = 'Linux';

    /**
     * Gets load information
     *
     * @return array with load data
     */
    function loadavg()
    {
        $buf = file_get_contents('/proc/stat');
        $nums = preg_split(
            "/\s+/",
            /*overload*/mb_substr($buf, 0, /*overload*/mb_strpos($buf, "\n"))
        );
        return Array(
            'busy' => $nums[1] + $nums[2] + $nums[3],
            'idle' => intval($nums[4])
        );
    }

    /**
     * Checks whether class is supported in this environment
     *
     * @return true on success
     */
    public function supported()
    {
        return is_readable('/proc/meminfo') && is_readable('/proc/stat');
    }


    /**
     * Gets information about memory usage
     *
     * @return array with memory usage data
     */
    function memory()
    {
        preg_match_all(
            MEMORY_REGEXP,
            file_get_contents('/proc/meminfo'),
            $matches
        );

        $mem = array_combine($matches[1], $matches[2]);

        $defaults = array(
            'MemTotal' => 0,
            'MemFree' => 0,
            'Cached' => 0,
            'Buffers' => 0,
            'SwapTotal' => 0,
            'SwapFree' => 0,
            'SwapCached' => 0,
        );

        $mem = array_merge($defaults, $mem);

        $mem['MemUsed'] = $mem['MemTotal']
            - $mem['MemFree'] - $mem['Cached'] - $mem['Buffers'];

        $mem['SwapUsed'] = $mem['SwapTotal']
            - $mem['SwapFree'] - $mem['SwapCached'];

        foreach ($mem as $idx => $value) {
            $mem[$idx] = intval($value);
        }
        return $mem;
    }
}

/**
 * SunOS based SysInfo class
 *
 * @package PhpMyAdmin-sysinfo
 */
class PMA_SysInfoSunos extends PMA_SysInfo
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
            list($key, $value) = preg_split("/\t/", trim($m), 2);
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
        return is_readable('/proc/meminfo');
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
