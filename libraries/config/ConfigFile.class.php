<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Config file management
 *
 * @package PhpMyAdmin
 */

/**
 * Config file management class.
 * Stores its data in $_SESSION
 *
 * @package PhpMyAdmin
 */
class ConfigFile
{
    /**
     * Stores default PMA config from config.default.php
     * @var array
     */
    private $_defaultCfg;

    /**
     * Stores allowed values for non-standard fields
     * @var array
     */
    private $_cfgDb;

    /**
     * Stores original PMA config, not modified by user preferences
     * @var PMA_Config
     */
    private $_baseCfg;

    /**
     * Whether we are currently working in PMA Setup context
     * @var bool
     */
    private $_isInSetup;

    /**
     * Keys which will be always written to config file
     * @var array
     */
    private $_persistKeys = array();

    /**
     * Changes keys while updating config in {@link updateWithGlobalConfig()}
     * or reading by {@link getConfig()} or {@link getConfigArray()}
     * @var array
     */
    private $_cfgUpdateReadMapping = array();

    /**
     * Key filter for {@link set()}
     * @var array|null
     */
    private $_setFilter;

    /**
     * Instance id (key in $_SESSION array, separate for each server -
     * ConfigFile{server id})
     * @var string
     */
    private $_id;

    /**
     * Result for {@link _flattenArray()}
     * @var array|null
     */
    private $_flattenArrayResult;

    /**
     * Constructor
     *
     * @param array $base_config base configuration read from
     *                           {@link PMA_Config::$base_config},
     *                           use only when not in PMA Setup
     */
    public function __construct(array $base_config = null)
    {
        // load default config values
        $cfg = &$this->_defaultCfg;
        include './libraries/config.default.php';
        $cfg['fontsize'] = '82%';

        // load additional config information
        $cfg_db = &$this->_cfgDb;
        include './libraries/config.values.php';

        // apply default values overrides
        if (count($cfg_db['_overrides'])) {
            foreach ($cfg_db['_overrides'] as $path => $value) {
                PMA_arrayWrite($path, $cfg, $value);
            }
        }

        $this->_baseCfg = $base_config;
        $this->_isInSetup = is_null($base_config);
        $this->_id = 'ConfigFile' . $GLOBALS['server'];
        if (!isset($_SESSION[$this->_id])) {
            $_SESSION[$this->_id] = array();
        }
    }

    /**
     * Sets names of config options which will be placed in config file even if
     * they are set to their default values (use only full paths)
     *
     * @param array $keys the names of the config options
     *
     * @return void
     */
    public function setPersistKeys(array $keys)
    {
        // checking key presence is much faster than searching so move values
        // to keys
        $this->_persistKeys = array_flip($keys);
    }

    /**
     * Returns flipped array set by {@link setPersistKeys()}
     *
     * @return array
     */
    public function getPersistKeysMap()
    {
        return $this->_persistKeys;
    }

    /**
     * By default ConfigFile allows setting of all configuration keys, use
     * this method to set up a filter on {@link set()} method
     *
     * @param array|null $keys array of allowed keys or null to remove filter
     *
     * @return void
     */
    public function setAllowedKeys($keys)
    {
        if ($keys === null) {
            $this->_setFilter = null;
            return;
        }
        // checking key presence is much faster than searching so move values
        // to keys
        $this->_setFilter = array_flip($keys);
    }

    /**
     * Sets path mapping for updating config in
     * {@link updateWithGlobalConfig()} or reading
     * by {@link getConfig()} or {@link getConfigArray()}
     *
     * @param array $mapping Contains the mapping of "Server/config options"
     *                       to "Server/1/config options"
     *
     * @return void
     */
    public function setCfgUpdateReadMapping(array $mapping)
    {
        $this->_cfgUpdateReadMapping = $mapping;
    }

    /**
     * Resets configuration data
     *
     * @return void
     */
    public function resetConfigData()
    {
        $_SESSION[$this->_id] = array();
    }

    /**
     * Sets configuration data (overrides old data)
     *
     * @param array $cfg Configuration options
     *
     * @return void
     */
    public function setConfigData(array $cfg)
    {
        $_SESSION[$this->_id] = $cfg;
    }

    /**
     * Sets config value
     *
     * @param string $path           Path
     * @param mixed  $value          Value
     * @param string $canonical_path Canonical path
     *
     * @return void
     */
    public function set($path, $value, $canonical_path = null)
    {
        if ($canonical_path === null) {
            $canonical_path = $this->getCanonicalPath($path);
        }
        // apply key whitelist
        if ($this->_setFilter !== null
            && ! isset($this->_setFilter[$canonical_path])
        ) {
            return;
        }
        // if the path isn't protected it may be removed
        if (isset($this->_persistKeys[$canonical_path])) {
            PMA_arrayWrite($path, $_SESSION[$this->_id], $value);
            return;
        }

        $default_value = $this->getDefault($canonical_path);
        $remove_path = $value === $default_value;
        if ($this->_isInSetup) {
            // remove if it has a default value or is empty
            $remove_path = $remove_path
                || (empty($value) && empty($default_value));
        } else {
            // get original config values not overwritten by user
            // preferences to allow for overwriting options set in
            // config.inc.php with default values
            $instance_default_value = PMA_arrayRead(
                $canonical_path,
                $this->_baseCfg
            );
            // remove if it has a default value and base config (config.inc.php)
            // uses default value
            $remove_path = $remove_path
                && ($instance_default_value === $default_value);
        }
        if ($remove_path) {
            PMA_arrayRemove($path, $_SESSION[$this->_id]);
            return;
        }

        PMA_arrayWrite($path, $_SESSION[$this->_id], $value);
    }

    /**
     * Flattens multidimensional array, changes indices to paths
     * (eg. 'key/subkey').
     * Used as array_walk() callback.
     *
     * @param mixed $value  Value
     * @param mixed $key    Key
     * @param mixed $prefix Prefix
     *
     * @return void
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
        array_walk($this->_defaultCfg, array($this, '_flattenArray'), '');
        $flat_cfg = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;
        return $flat_cfg;
    }

    /**
     * Updates config with values read from given array
     * (config will contain differences to defaults from config.defaults.php).
     *
     * @param array $cfg Configuration
     *
     * @return void
     */
    public function updateWithGlobalConfig(array $cfg)
    {
        // load config array and flatten it
        $this->_flattenArrayResult = array();
        array_walk($cfg, array($this, '_flattenArray'), '');
        $flat_cfg = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;

        // save values map for translating a few user preferences paths,
        // should be complemented by code reading from generated config
        // to perform inverse mapping
        foreach ($flat_cfg as $path => $value) {
            if (isset($this->_cfgUpdateReadMapping[$path])) {
                $path = $this->_cfgUpdateReadMapping[$path];
            }
            $this->set($path, $value, $path);
        }
    }

    /**
     * Returns config value or $default if it's not set
     *
     * @param string $path    Path of config file
     * @param mixed  $default Default values
     *
     * @return mixed
     */
    public function get($path, $default = null)
    {
        return PMA_arrayRead($path, $_SESSION[$this->_id], $default);
    }

    /**
     * Returns default config value or $default it it's not set ie. it doesn't
     * exist in config.default.php ($cfg) and config.values.php
     * ($_cfg_db['_overrides'])
     *
     * @param string $canonical_path Canonical path
     * @param mixed  $default        Default value
     *
     * @return mixed
     */
    public function getDefault($canonical_path, $default = null)
    {
        return PMA_arrayRead($canonical_path, $this->_defaultCfg, $default);
    }

    /**
     * Returns config value, if it's not set uses the default one; returns
     * $default if the path isn't set and doesn't contain a default value
     *
     * @param string $path    Path
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function getValue($path, $default = null)
    {
        $v = PMA_arrayRead($path, $_SESSION[$this->_id], null);
        if ($v !== null) {
            return $v;
        }
        $path = $this->getCanonicalPath($path);
        return $this->getDefault($path, $default);
    }

    /**
     * Returns canonical path
     *
     * @param string $path Path
     *
     * @return string
     */
    public function getCanonicalPath($path)
    {
        return preg_replace('#^Servers/([\d]+)/#', 'Servers/1/', $path);
    }

    /**
     * Returns config database entry for $path ($cfg_db in config_info.php)
     *
     * @param string $path    path of the variable in config db
     * @param mixed  $default default value
     *
     * @return mixed
     */
    public function getDbEntry($path, $default = null)
    {
        return PMA_arrayRead($path, $this->_cfgDb, $default);
    }

    /**
     * Returns server count
     *
     * @return int
     */
    public function getServerCount()
    {
        return isset($_SESSION[$this->_id]['Servers'])
            ? count($_SESSION[$this->_id]['Servers'])
            : 0;
    }

    /**
     * Returns server list
     *
     * @return array|null
     */
    public function getServers()
    {
        return isset($_SESSION[$this->_id]['Servers'])
            ? $_SESSION[$this->_id]['Servers']
            : null;
    }

    /**
     * Returns DSN of given server
     *
     * @param integer $server server index
     *
     * @return string
     */
    public function getServerDSN($server)
    {
        if (!isset($_SESSION[$this->_id]['Servers'][$server])) {
            return '';
        }

        $path = 'Servers/' . $server;
        $dsn = 'mysqli://';
        if ($this->getValue("$path/auth_type") == 'config') {
            $dsn .= $this->getValue("$path/user");
            if (! $this->getValue("$path/nopassword")) {
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
     * @param int $id server index
     *
     * @return string
     */
    public function getServerName($id)
    {
        if (!isset($_SESSION[$this->_id]['Servers'][$id])) {
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
     * @param int $server server index
     *
     * @return void
     */
    public function removeServer($server)
    {
        if (!isset($_SESSION[$this->_id]['Servers'][$server])) {
            return;
        }
        $last_server = $this->getServerCount();

        for ($i = $server; $i < $last_server; $i++) {
            $_SESSION[$this->_id]['Servers'][$i]
                = $_SESSION[$this->_id]['Servers'][$i + 1];
        }
        unset($_SESSION[$this->_id]['Servers'][$last_server]);

        if (isset($_SESSION[$this->_id]['ServerDefault'])
            && $_SESSION[$this->_id]['ServerDefault'] == $last_server
        ) {
            unset($_SESSION[$this->_id]['ServerDefault']);
        }
    }

    /**
     * Returns config file path, relative to phpMyAdmin's root path
     *
     * @return string
     */
    public function getFilePath()
    {
        // Load paths
        if (!defined('SETUP_CONFIG_FILE')) {
            include_once './libraries/vendor_config.php';
        }

        return SETUP_CONFIG_FILE;
    }

    /**
     * Returns configuration array (full, multidimensional format)
     *
     * @return array
     */
    public function getConfig()
    {
        $c = $_SESSION[$this->_id];
        foreach ($this->_cfgUpdateReadMapping as $map_to => $map_from) {
            // if the key $c exists in $map_to
            if (PMA_arrayRead($map_to, $c) !== null) {
                PMA_arrayWrite($map_to, $c, PMA_arrayRead($map_from, $c));
                PMA_arrayRemove($map_from, $c);
            }
        }
        return $c;
    }

    /**
     * Returns configuration array (flat format)
     *
     * @return array
     */
    public function getConfigArray()
    {
        $this->_flattenArrayResult = array();
        array_walk($_SESSION[$this->_id], array($this, '_flattenArray'), '');
        $c = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;

        $persistKeys = array_diff(
            array_keys($this->_persistKeys),
            array_keys($c)
        );
        foreach ($persistKeys as $k) {
            $c[$k] = $this->getDefault($this->getCanonicalPath($k));
        }

        foreach ($this->_cfgUpdateReadMapping as $map_to => $map_from) {
            if (!isset($c[$map_from])) {
                continue;
            }
            $c[$map_to] = $c[$map_from];
            unset($c[$map_from]);
        }
        return $c;
    }
}
