<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Config file management
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;

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
     * @var array|null
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
    private $_persistKeys = [];

    /**
     * Changes keys while updating config in {@link updateWithGlobalConfig()}
     * or reading by {@link getConfig()} or {@link getConfigArray()}
     * @var array
     */
    private $_cfgUpdateReadMapping = [];

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
     * @param array|null $baseConfig base configuration read from
     *                               {@link PhpMyAdmin\Config::$base_config},
     *                               use only when not in PMA Setup
     */
    public function __construct($baseConfig = null)
    {
        // load default config values
        $cfg = &$this->_defaultCfg;
        include ROOT_PATH . 'libraries/config.default.php';

        // load additional config information
        $this->_cfgDb = include ROOT_PATH . 'libraries/config.values.php';

        // apply default values overrides
        if (count($this->_cfgDb['_overrides'])) {
            foreach ($this->_cfgDb['_overrides'] as $path => $value) {
                Core::arrayWrite($path, $cfg, $value);
            }
        }

        $this->_baseCfg = $baseConfig;
        $this->_isInSetup = $baseConfig === null;
        $this->_id = 'ConfigFile' . $GLOBALS['server'];
        if (! isset($_SESSION[$this->_id])) {
            $_SESSION[$this->_id] = [];
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
        $_SESSION[$this->_id] = [];
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
     * @param string $path          Path
     * @param mixed  $value         Value
     * @param string $canonicalPath Canonical path
     *
     * @return void
     */
    public function set($path, $value, $canonicalPath = null)
    {
        if ($canonicalPath === null) {
            $canonicalPath = $this->getCanonicalPath($path);
        }
        // apply key whitelist
        if ($this->_setFilter !== null
            && ! isset($this->_setFilter[$canonicalPath])
        ) {
            return;
        }
        // if the path isn't protected it may be removed
        if (isset($this->_persistKeys[$canonicalPath])) {
            Core::arrayWrite($path, $_SESSION[$this->_id], $value);
            return;
        }

        $defaultValue = $this->getDefault($canonicalPath);
        $removePath = $value === $defaultValue;
        if ($this->_isInSetup) {
            // remove if it has a default value or is empty
            $removePath = $removePath
                || (empty($value) && empty($defaultValue));
        } else {
            // get original config values not overwritten by user
            // preferences to allow for overwriting options set in
            // config.inc.php with default values
            $instanceDefaultValue = Core::arrayRead(
                $canonicalPath,
                $this->_baseCfg
            );
            // remove if it has a default value and base config (config.inc.php)
            // uses default value
            $removePath = $removePath
                && ($instanceDefaultValue === $defaultValue);
        }
        if ($removePath) {
            Core::arrayRemove($path, $_SESSION[$this->_id]);
            return;
        }

        Core::arrayWrite($path, $_SESSION[$this->_id], $value);
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
        if (is_array($value) && ! isset($value[0])) {
            $prefix .= $key . '/';
            array_walk($value, [$this, '_flattenArray'], $prefix);
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
        $this->_flattenArrayResult = [];
        array_walk($this->_defaultCfg, [$this, '_flattenArray'], '');
        $flatConfig = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;
        return $flatConfig;
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
        $this->_flattenArrayResult = [];
        array_walk($cfg, [$this, '_flattenArray'], '');
        $flatConfig = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;

        // save values map for translating a few user preferences paths,
        // should be complemented by code reading from generated config
        // to perform inverse mapping
        foreach ($flatConfig as $path => $value) {
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
        return Core::arrayRead($path, $_SESSION[$this->_id], $default);
    }

    /**
     * Returns default config value or $default it it's not set ie. it doesn't
     * exist in config.default.php ($cfg) and config.values.php
     * ($_cfg_db['_overrides'])
     *
     * @param string $canonicalPath Canonical path
     * @param mixed  $default       Default value
     *
     * @return mixed
     */
    public function getDefault($canonicalPath, $default = null)
    {
        return Core::arrayRead($canonicalPath, $this->_defaultCfg, $default);
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
        $v = Core::arrayRead($path, $_SESSION[$this->_id], null);
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
     * Returns config database entry for $path
     *
     * @param string $path    path of the variable in config db
     * @param mixed  $default default value
     *
     * @return mixed
     */
    public function getDbEntry($path, $default = null)
    {
        return Core::arrayRead($path, $this->_cfgDb, $default);
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
        if (! isset($_SESSION[$this->_id]['Servers'][$server])) {
            return '';
        }

        $path = 'Servers/' . $server;
        $dsn = 'mysqli://';
        if ($this->getValue("$path/auth_type") == 'config') {
            $dsn .= $this->getValue("$path/user");
            if (! empty($this->getValue("$path/password"))) {
                $dsn .= ':***';
            }
            $dsn .= '@';
        }
        if ($this->getValue("$path/host") != 'localhost') {
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
        if (! isset($_SESSION[$this->_id]['Servers'][$id])) {
            return '';
        }
        $verbose = $this->get("Servers/$id/verbose");
        if (! empty($verbose)) {
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
        if (! isset($_SESSION[$this->_id]['Servers'][$server])) {
            return;
        }
        $lastServer = $this->getServerCount();

        for ($i = $server; $i < $lastServer; $i++) {
            $_SESSION[$this->_id]['Servers'][$i]
                = $_SESSION[$this->_id]['Servers'][$i + 1];
        }
        unset($_SESSION[$this->_id]['Servers'][$lastServer]);

        if (isset($_SESSION[$this->_id]['ServerDefault'])
            && $_SESSION[$this->_id]['ServerDefault'] == $lastServer
        ) {
            unset($_SESSION[$this->_id]['ServerDefault']);
        }
    }

    /**
     * Returns configuration array (full, multidimensional format)
     *
     * @return array
     */
    public function getConfig()
    {
        $c = $_SESSION[$this->_id];
        foreach ($this->_cfgUpdateReadMapping as $mapTo => $mapFrom) {
            // if the key $c exists in $map_to
            if (Core::arrayRead($mapTo, $c) !== null) {
                Core::arrayWrite($mapTo, $c, Core::arrayRead($mapFrom, $c));
                Core::arrayRemove($mapFrom, $c);
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
        $this->_flattenArrayResult = [];
        array_walk($_SESSION[$this->_id], [$this, '_flattenArray'], '');
        $c = $this->_flattenArrayResult;
        $this->_flattenArrayResult = null;

        $persistKeys = array_diff(
            array_keys($this->_persistKeys),
            array_keys($c)
        );
        foreach ($persistKeys as $k) {
            $c[$k] = $this->getDefault($this->getCanonicalPath($k));
        }

        foreach ($this->_cfgUpdateReadMapping as $mapTo => $mapFrom) {
            if (! isset($c[$mapFrom])) {
                continue;
            }
            $c[$mapTo] = $c[$mapFrom];
            unset($c[$mapFrom]);
        }
        return $c;
    }
}
