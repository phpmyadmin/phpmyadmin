<?php
/**
 * Config file management and generation
 *
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 * @package    phpMyAdmin-setup
 */

/**
 * Config file management and generation class
 *
 * @package    phpMyAdmin-setup
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
    private $persistKeys;

    /**
     * ConfigFile instance
     * @var ConfigFile
     */
    private static $_instance;

    /**
     * Private constructor, use {@link getInstance()}
     */
    private function __construct()
    {
        // load default config values
        $cfg = &$this->cfg;
        require './libraries/config.default.php';

        // load additionsl config information
        $cfg_db = &$this->cfgDb;
        $persist_keys = array();
        require './setup/lib/config_info.inc.php';

        // apply default values overrides
        if (count($cfg_db['_overrides'])) {
            foreach ($cfg_db['_overrides'] as $path => $value) {
                array_write($path, $cfg, $value);
            }
        }

        // checking key presence is much faster than searching so move values to keys
        $this->persistKeys = array_flip($persist_keys);
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
     * Sets config value
     *
     * @param string $path
     * @param mixed  $value
     * @param string $canonical_path
     */
    public function set($path, $value, $canonical_path = null)
    {
        if ($canonical_path === null) {
            $canonical_path = $this->getCanonicalPath($path);
        }
        // remove if the path isn't protected and it's empty or has a default value
        $default_value = $this->getDefault($canonical_path);
        if (!isset($this->persistKeys[$canonical_path])
            && (($value == $default_value) || (empty($value) && empty($default_value)))) {
            array_remove($path, $_SESSION['ConfigFile']);
        } else {
            array_write($path, $_SESSION['ConfigFile'], $value);
        }
    }

    /**
     * Returns config value or $default if it's not set
     *
     * @param  string $path
     * @param  mixed  $default
     * @return mixed
     */
    public function get($path, $default = null)
    {
        return array_read($path, $_SESSION['ConfigFile'], $default);
    }

    /**
     * Returns default config value or $default it it's not set ie. it doesn't
     * exist in config.default.php ($cfg) and config_info.inc.php
     * ($_cfg_db['_overrides'])
     *
     * @param  string $canonical_path
     * @param  mixed  $default
     * @return mixed
     */
    public function getDefault($canonical_path, $default = null)
    {
        return array_read($canonical_path, $this->cfg, $default);
    }

    /**
     * Returns config value, if it's not set uses the default one; returns
     * $default if the path isn't set and doesn't contain a default value
     *
     * @param  string $path
     * @param  mixed  $default
     * @return mixed
     */
    public function getValue($path, $default = null)
    {
        $v = array_read($path, $_SESSION['ConfigFile'], null);
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
     * @param  string $path
     * @param  mixed  $default
     * @return mixed
     */
    public function getDbEntry($path, $default = null)
    {
        return array_read($path, $this->cfgDb, $default);
    }

    /**
     * Returns server count
     *
     * @return int
     */
    public function getServerCount()
    {
      return isset($_SESSION['ConfigFile']['Servers'])
          ? count($_SESSION['ConfigFile']['Servers'])
          : 0;
    }

    /**
     * Returns DSN of given server
     *
     * @param integer $server
     * @return string
     */
    function getServerDSN($server)
    {
        if (!isset($_SESSION['ConfigFile']['Servers'][$server])) {
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
        if (!isset($_SESSION['ConfigFile']['Servers'][$id])) {
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
        if (!isset($_SESSION['ConfigFile']['Servers'][$server])) {
            return;
        }
        $last_server = $this->getServerCount();

        for ($i = $server; $i < $last_server; $i++) {
            $_SESSION['ConfigFile']['Servers'][$i] = $_SESSION['ConfigFile']['Servers'][$i+1];
        }
        unset($_SESSION['ConfigFile']['Servers'][$last_server]);

        if (isset($_SESSION['ConfigFile']['ServerDefault'])
            && $_SESSION['ConfigFile']['ServerDefault'] >= 0) {
            unset($_SESSION['ConfigFile']['ServerDefault']);
        }
    }

    /**
     * Returns config file path
     *
     * @return unknown
     */
    public function getFilePath()
    {
        return $this->getDbEntry('_config_file_path');
    }

    /**
     * Creates config file
     *
     * @return string
     */
    public function getConfigFile()
    {
        $crlf = (isset($_SESSION['eol']) && $_SESSION['eol'] == 'win') ? "\r\n" : "\n";
        $c = $_SESSION['ConfigFile'];

        // header
        $ret = '<?php' . $crlf
            . '/*' . $crlf
            . ' * Generated configuration file' . $crlf
            . ' * Generated by: phpMyAdmin '
                    . $_SESSION['PMA_Config']->get('PMA_VERSION')
                    . ' setup script by Piotr Przybylski <piotrprz@gmail.com>' . $crlf
            . ' * Date: ' . date(DATE_RFC1123) . $crlf
            . ' */' . $crlf . $crlf;

        // servers
        if ($this->getServerCount() > 0) {
            $ret .= "/* Servers configuration */$crlf\$i = 0;" . $crlf . $crlf;
            foreach ($c['Servers'] as $id => $server) {
                $ret .= '/* Server: ' . strtr($this->getServerName($id) . " [$id] ", '*/', '-') . "*/" . $crlf
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
            $ret .= "\$cfg['$k'] = " . var_export($v, true) . ';' . $crlf;
            if (isset($persistKeys[$k])) {
                unset($persistKeys[$k]);
            }
        }
        // keep 1d array keys which are present in $persist_keys (config_info.inc.php)
        foreach (array_keys($persistKeys) as $k) {
            if (strpos($k, '/') === false) {
                $k = preg_replace('/[^A-Za-z0-9_]/', '_', $k);
                $ret .= "\$cfg['$k'] = " . var_export($this->getDefault($k), true) . ';' . $crlf;
            }
        }
        $ret .= '?>';

        return $ret;
    }
}
?>
