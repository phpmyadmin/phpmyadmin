<?php
/**
 * Config file management and generation
 *
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @package    phpMyAdmin
 */

/**
 * Config file management and generation class.
 * Stores its data in $_SESSION
 *
 * @package    phpMyAdmin
 */
class ConfigFile
{
    /**
     * Stores default PMA config from config.default.php
     * @var array
     */
    private $cfg;

    /**
     * Stores allowed values for non-standard fields
     * @var array
     */
    private $cfgDb;

    /**
     * Keys which will be always written to config file
     * @var array
     */
    private $persistKeys = array();

    /**
     * Key filter for {@link set()}
     * @var array|null
     */
    private $setFilter;

    /**
     * Instance id (key in $_SESSION array, separate for each server - ConfigFile{server id})
     * @var string
     */
    private $id;

    /**
     * Result for {@link _flattenArray()}
     * @var array
     */
    private $_flattenArrayResult;

    /**
     * ConfigFile instance
     * @var ConfigFile
     */
    private static $_instance;

    /**
     * Private constructor, use {@link getInstance()}
     *
     * @uses PMA_array_write()
     */
    private function __construct()
    {
        // load default config values
        $cfg = &$this->cfg;
        require './libraries/config.default.php';
        $cfg['fontsize'] = '82%';

        // load additional config information
        $cfg_db = &$this->cfgDb;
        require './libraries/config.values.php';

        // apply default values overrides
        if (count($cfg_db['_overrides'])) {
            foreach ($cfg_db['_overrides'] as $path => $value) {
                PMA_array_write($path, $cfg, $value);
            }
        }

        $this->id = 'ConfigFile' . $GLOBALS['server'];
        if (!isset($_SESSION[$this->id])) {
            $_SESSION[$this->id] = array();
        }
    }

    /**
     * Returns class instance
     *
     * @return ConfigFile
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new ConfigFile();
        }
        return self::$_instance;
    }

    /**
     * Sets names of config options which will be placed in config file even if they are set
     * to their default values (use only full paths)
     *
     * @param array $keys
     */
    public function setPersistKeys($keys)
    {
        // checking key presence is much faster than searching so move values to keys
        $this->persistKeys = array_flip($keys);
    }

    /**
     * By default ConfigFile allows setting of all configuration keys, use this method
     * to set up a filter on {@link set()} method
     *
     * @param array|null $keys array of allowed keys or null to remove filter
     */
    public function setAllowedKeys($keys)
    {
        if ($keys === null) {
            $this->setFilter = null;
            return;
        }
        // checking key presence is much faster than searching so move values to keys
        $this->setFilter = array_flip($keys);
    }

    /**
     * Resets configuration data
     */
    public function resetConfigData()
    {
        $_SESSION[$this->id] = array();
    }

    /**
     * Sets configuration data (overrides old data)
     *
     * @param array $cfg
     */
    public function setConfigData(array $cfg)
    {
        $_SESSION[$this->id] = $cfg;
    }

    /**
     * Sets config value
     *
     * @uses PMA_array_remove()
     * @uses PMA_array_write()
     * @param string $path
     * @param mixed  $value
     * @param string $canonical_path
     */
    public function set($path, $value, $canonical_path = null)
    {
        if ($canonical_path === null) {
            $canonical_path = $this->getCanonicalPath($path);
        }
        // apply key whitelist
        if ($this->setFilter !== null && !isset($this->setFilter[$canonical_path])) {
            return;
        }
        // remove if the path isn't protected and it's empty or has a default value
        $default_value = $this->getDefault($canonical_path);
        if (!isset($this->persistKeys[$canonical_path])
                && (($value == $default_value) || (empty($value) && empty($default_value)))) {
            PMA_array_remove($path, $_SESSION[$this->id]);
        } else {
            PMA_array_write($path, $_SESSION[$this->id], $value);
        }
    }

    /**
     * Flattens multidimensional array, changes indices to paths (eg. 'key/subkey').
     * Used as array_walk() callback.
     *
     * @param mixed $value
     * @param mixed $key
     * @param mixed $prefix
     */
    private function _flattenArray($value, $key, $prefix)
    {
        // no recursion for numeric arrays
        if (is_array($value) && !isset($value[0])) {
            $prefix .= $key . '/';
            array_walk($value, array($this, '_flattenArray'), $prefix);
        } else {
            $this->_flattenArrayResult[$prefix . $key] = $value;
        }
    }

    /**
     * Returns default config in a flattened array
     *
     * @return array
     */
    public function getFlatDefaultConfig()
    {
        $this->_flattenArrayResult = array();
        array_walk($this->cfg, array($this, '_flattenArray'), '');
        $flat_cfg = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;
        return $flat_cfg;
    }

    /**
     * Updates config with values read from PMA_Config class
     * (config will contain differences to defaults from config.defaults.php).
     *
     * @param PMA_Config $PMA_Config
     */
    public function updateWithGlobalConfig(PMA_Config $PMA_Config)
    {
        // load config array and flatten it
        $this->_flattenArrayResult = array();
        array_walk($PMA_Config->settings, array($this, '_flattenArray'), '');
        $flat_cfg = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;

        // save values
        foreach ($flat_cfg as $path => $value) {
            $this->set($path, $value, $path);
        }
    }

    /**
     * Returns config value or $default if it's not set
     *
     * @uses PMA_array_read()
     * @param  string $path
     * @param  mixed  $default
     * @return mixed
     */
    public function get($path, $default = null)
    {
        return PMA_array_read($path, $_SESSION[$this->id], $default);
    }

    /**
     * Returns default config value or $default it it's not set ie. it doesn't
     * exist in config.default.php ($cfg) and config.values.php
     * ($_cfg_db['_overrides'])
     *
     * @uses PMA_array_read()
     * @param  string $canonical_path
     * @param  mixed  $default
     * @return mixed
     */
    public function getDefault($canonical_path, $default = null)
    {
        return PMA_array_read($canonical_path, $this->cfg, $default);
    }

    /**
     * Returns config value, if it's not set uses the default one; returns
     * $default if the path isn't set and doesn't contain a default value
     *
     * @uses PMA_array_read()
     * @param  string $path
     * @param  mixed  $default
     * @return mixed
     */
    public function getValue($path, $default = null)
    {
        $v = PMA_array_read($path, $_SESSION[$this->id], null);
        if ($v !== null) {
            return $v;
        }
        $path = $this->getCanonicalPath($path);
        return $this->getDefault($path, $default);
    }

    /**
     * Returns canonical path
     *
     * @param string $path
     * @return string
     */
    public function getCanonicalPath($path) {
        return preg_replace('#^Servers/([\d]+)/#', 'Servers/1/', $path);
    }

    /**
     * Returns config database entry for $path ($cfg_db in config_info.php)
     *
     * @uses PMA_array_read()
     * @param  string $path
     * @param  mixed  $default
     * @return mixed
     */
    public function getDbEntry($path, $default = null)
    {
        return PMA_array_read($path, $this->cfgDb, $default);
    }

    /**
     * Returns server count
     *
     * @return int
     */
    public function getServerCount()
    {
      return isset($_SESSION[$this->id]['Servers'])
          ? count($_SESSION[$this->id]['Servers'])
          : 0;
    }

    /**
     * Returns server list
     *
     * @return array|null
     */
    public function getServers()
    {
      return isset($_SESSION[$this->id]['Servers'])
          ? $_SESSION[$this->id]['Servers']
          : null;
    }

    /**
     * Returns DSN of given server
     *
     * @param integer $server
     * @return string
     */
    function getServerDSN($server)
    {
        if (!isset($_SESSION[$this->id]['Servers'][$server])) {
            return '';
        }

        $path = 'Servers/' . $server;
        $dsn = $this->getValue("$path/extension") . '://';
        if ($this->getValue("$path/auth_type") == 'config') {
            $dsn .= $this->getValue("$path/user");
            if (!$this->getValue("$path/nopassword")) {
                $dsn .= ':***';
            }
            $dsn .= '@';
        }
        if ($this->getValue("$path/connect_type") == 'tcp') {
            $dsn .= $this->getValue("$path/host");
            $port = $this->getValue("$path/port");
            if ($port) {
                $dsn .= ':' . $port;
            }
        } else {
            $dsn .= $this->getValue("$path/socket");
        }
        return $dsn;
    }

    /**
     * Returns server name
     *
     * @param int $id
     * @return string
     */
    public function getServerName($id)
    {
        if (!isset($_SESSION[$this->id]['Servers'][$id])) {
            return '';
        }
        $verbose = $this->get("Servers/$id/verbose");
        if (!empty($verbose)) {
            return $verbose;
        }
        $host = $this->get("Servers/$id/host");
        return empty($host) ? 'localhost' : $host;
    }

    /**
     * Removes server
     *
     * @param int $server
     */
    public function removeServer($server)
    {
        if (!isset($_SESSION[$this->id]['Servers'][$server])) {
            return;
        }
        $last_server = $this->getServerCount();

        for ($i = $server; $i < $last_server; $i++) {
            $_SESSION[$this->id]['Servers'][$i] = $_SESSION[$this->id]['Servers'][$i+1];
        }
        unset($_SESSION[$this->id]['Servers'][$last_server]);

        if (isset($_SESSION[$this->id]['ServerDefault'])
            && $_SESSION[$this->id]['ServerDefault'] >= 0) {
            unset($_SESSION[$this->id]['ServerDefault']);
        }
    }

    /**
     * Returns config file path, relative to phpMyAdmin's root path
     *
     * @return unknown
     */
    public function getFilePath()
    {
        // Load paths
        if (!defined('SETUP_CONFIG_FILE')) {
            require_once './libraries/vendor_config.php';
        }

        return SETUP_CONFIG_FILE;
    }

    /**
     * Returns configuration array (flat format)
     *
     * @return array
     */
    public function getConfigArray()
    {
        $this->_flattenArrayResult = array();
        array_walk($_SESSION[$this->id], array($this, '_flattenArray'), '');
        $c = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;

        $persistKeys = array_diff(array_keys($this->persistKeys), array_keys($c));
        foreach ($persistKeys as $k) {
            $c[$k] = $this->getDefault($k);
        }
        return $c;
    }

    /**
     * Creates config file
     *
     * @return string
     */
    public function getConfigFile()
    {
        $crlf = (isset($_SESSION['eol']) && $_SESSION['eol'] == 'win') ? "\r\n" : "\n";
        $c = $_SESSION[$this->id];

        // header
        $ret = '<?php' . $crlf
            . '/*' . $crlf
            . ' * Generated configuration file' . $crlf
            . ' * Generated by: phpMyAdmin '
                    . $GLOBALS['PMA_Config']->get('PMA_VERSION')
                    . ' setup script by Piotr Przybylski <piotrprz@gmail.com>' . $crlf
            . ' * Date: ' . date(DATE_RFC1123) . $crlf
            . ' */' . $crlf . $crlf;

        // servers
        if ($this->getServerCount() > 0) {
            $ret .= "/* Servers configuration */$crlf\$i = 0;" . $crlf . $crlf;
            foreach ($c['Servers'] as $id => $server) {
                $ret .= '/* Server: ' . strtr($this->getServerName($id), '*/', '-') . " [$id] */" . $crlf
                    . '$i++;' . $crlf;
                foreach ($server as $k => $v) {
                    $k = preg_replace('/[^A-Za-z0-9_]/', '_', $k);
                    $ret .= "\$cfg['Servers'][\$i]['$k'] = "
                        . var_export($v, true) . ';' . $crlf;
                }
                $ret .= $crlf;
            }
            $ret .= '/* End of servers configuration */' . $crlf . $crlf;
        }
        unset($c['Servers']);

        // other settings
        $persistKeys = $this->persistKeys;
        foreach ($c as $k => $v) {
            $k = preg_replace('/[^A-Za-z0-9_]/', '_', $k);
            $ret .= $this->_getVarExport($k, $v, $crlf);
            if (isset($persistKeys[$k])) {
                unset($persistKeys[$k]);
            }
        }
        // keep 1d array keys which are present in $persist_keys (config.values.php)
        foreach (array_keys($persistKeys) as $k) {
            if (strpos($k, '/') === false) {
                $k = preg_replace('/[^A-Za-z0-9_]/', '_', $k);
                $ret .= $this->_getVarExport($k, $this->getDefault($k), $crlf);
            }
        }
        $ret .= '?>';

        return $ret;
    }

    /**
     * Returns exported configuration variable
     *
     * @param string $var_name
     * @param mixed  $var_value
     * @param string $crlf
     * @return string
     */
    private function _getVarExport($var_name, $var_value, $crlf)
    {
        if (!is_array($var_value) || empty($var_value)) {
            return "\$cfg['$var_name'] = " . var_export($var_value, true) . ';' . $crlf;
        }
        $numeric_keys = true;
        foreach (array_keys($var_value) as $k) {
            if (!is_numeric($k)) {
                $numeric_keys = false;
                break;
            }
        }
        if ($numeric_keys) {
            for ($i = 0; $i < count($var_value); $i++) {
                if (!isset($var_value[$i])) {
                    $numeric_keys = false;
                    break;
                }
            }
        }
        $ret = '';
        if ($numeric_keys) {
            $retv = array();
            foreach ($var_value as $v) {
                $retv[] = var_export($v, true);
            }
            $ret = "\$cfg['$var_name'] = array(";
            $ret_end = ');' . $crlf;
            if ($var_name == 'UserprefsDisallow') {
                $ret = "\$cfg['$var_name'] = array_merge(\$this->settings['UserprefsDisallow'],\n\tarray(";
                $ret_end = ')' . $ret_end;
            }
            if (count($retv) <= 4) {
                // up to 4 values - one line
                $ret .= implode(', ', $retv);
            } else {
                // more than 4 values - value per line
                $imax = count($retv)-1;
                for ($i = 0; $i <= $imax; $i++) {
                    $ret .= ($i < $imax ? ($i > 0 ? ',' : '') : '') . $crlf . '    ' . $retv[$i];
                }
            }
            $ret .= $ret_end;
        } else {
            // string keys: $cfg[key][subkey] = value
            foreach ($var_value as $k => $v) {
                $k = preg_replace('/[^A-Za-z0-9_]/', '_', $k);
                $ret .= "\$cfg['$var_name']['$k'] = " . var_export($v, true) . ';' . $crlf;
            }
        }
        return $ret;
    }
}
?>