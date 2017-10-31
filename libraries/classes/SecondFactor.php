<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\UserPreferences;

/**
 * Second factor authentication wrapper class
 */
class SecondFactor
{
    /**
     * @var string
     */
    protected $_user;

    /**
     * @var array
     */
    protected $_config;

    /**
     * @var boolean
     */
    protected $_writable;

    /**
     * @var PhpMyAdmin\Plugins\SecondFactorPlugin
     */
    protected $_backend;

    /**
     * @var array
     */
    protected $_available;

    /**
     * Creates new SecondFactor object
     *
     * @param string $user User name
     */
    public function __construct($user)
    {
        $this->_user = $user;
        $this->_available = $this->getAvailable();
        $this->_config = $this->readConfig();
        $this->_writable = ($this->_config['type'] == 'db');
        $this->_backend = $this->getBackend();
    }

    /**
     * Reads the configuration
     *
     * @return array
     */
    public function readConfig()
    {
        $result = [];
        $config = UserPreferences::load();
        if (isset($config['config_data']['2fa'])) {
            $result = $config['config_data']['2fa'];
        }
        $result['type'] = $config['type'];
        if (! isset($result['backend'])) {
            $result['backend'] = '';
        }
        if (! isset($result['settings'])) {
            $result['settings'] = [];
        }
        return $result;
    }

    /**
     * Get any property of this class
     *
     * @param string $property name of the property
     *
     * @return mixed|void if property exist, value of the relevant property
     */
    public function __get($property)
    {
        switch ($property) {
            case 'backend':
                return $this->_backend;
            case 'available':
                return $this->_available;
            case 'writable':
                return $this->_writable;
            case 'config':
                return $this->_config;
        }
    }

    /**
     * Returns list of available backends
     *
     * @return array
     */
    public function getAvailable()
    {
        $result = [];
        if ($GLOBALS['cfg']['DBG']['simple2fa']) {
            $result[] = 'simple';
        }
        if (class_exists('PragmaRX\Google2FA\Google2FA') && class_exists('BaconQrCode\Renderer\Image\Png')) {
            $result[] = 'application';
        }
        if (class_exists('Samyoul\U2F\U2FServer\U2FServer')) {
            $result[] = 'key';
        }
        return $result;
    }

    /**
     * Returns class name for given name
     *
     * @param string $name Backend name
     *
     * @return string
     */
    public function getBackendClass($name)
    {
        $result = 'PhpMyAdmin\\Plugins\\SecondFactorPlugin';
        if (in_array($name, $this->_available)) {
            $result = 'PhpMyAdmin\\Plugins\\SecondFactor\\' . ucfirst($name);
        }
        return $result;
    }

    /**
     * Returns backend for current user
     *
     * @return PhpMyAdmin\Plugins\SecondFactorPlugin
     */
    public function getBackend()
    {
        $name = $this->getBackendClass($this->_config['backend']);
        return new $name($this->_user, $this->_config['settings']);
    }

    /**
     * Checks authentication, returns true on success
     *
     * @param boolean $skip_session Skip session cache
     *
     * @return boolean
     */
    public function check($skip_session = false)
    {
        if ($skip_session) {
            return $this->_backend->check();
        }
        if (empty($_SESSION['second_factor_check'])) {
            $_SESSION['second_factor_check'] = $this->_backend->check();
        }
        return $_SESSION['second_factor_check'];
    }

    /**
     * Renders user interface to enter second factor
     *
     * @return string HTML code
     */
    public function render()
    {
        return $this->_backend->render();
    }

    /**
     * Renders user interface to configure second factor
     *
     * @return string HTML code
     */
    public function setup()
    {
        return $this->_backend->setup();
    }

    /**
     * Changes second factor settings
     *
     * The object might stay in partialy changed setup
     * if configuration fails.
     *
     * @param string $name Backend name
     *
     * @return boolean
     */
    public function configure($name)
    {
        $config = [
            'backend' => $name
        ];
        if ($name === '') {
            $cls = $this->getBackendClass($name);
            $this->_backend = new $cls($this->_user, []);
            $config['settings'] = [];
        } else {
            if (! in_array($name, $this->_available)) {
                return false;
            }
            $cls = $this->getBackendClass($name);
            $this->_backend = new $cls($this->_user, []);
            if (! $this->_backend->configure()) {
                return false;
            }
            $config['settings'] = $this->_backend->getConfig();
        }
        $result = UserPreferences::persistOption('2fa', $config, null);
        if ($result !== true) {
            $result->display();
        }
        $this->_config = $config['settings'];
        return true;
    }
}
