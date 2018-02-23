<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Two authentication factor handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\UserPreferences;

/**
 * Two factor authentication wrapper class
 */
class TwoFactor
{
    /**
     * @var string
     */
    public $user;

    /**
     * @var array
     */
    public $config;

    /**
     * @var boolean
     */
    protected $_writable;

    /**
     * @var PhpMyAdmin\Plugins\TwoFactorPlugin
     */
    protected $_backend;

    /**
     * @var array
     */
    protected $_available;

    /**
     * @var UserPreferences
     */
    private $userPreferences;

    /**
     * Creates new TwoFactor object
     *
     * @param string $user User name
     */
    public function __construct($user)
    {
        $this->userPreferences = new UserPreferences();
        $this->user = $user;
        $this->_available = $this->getAvailable();
        $this->config = $this->readConfig();
        $this->_writable = ($this->config['type'] == 'db');
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
        $config = $this->userPreferences->load();
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
            case 'showSubmit':
                $backend = $this->_backend;
                return $backend::$showSubmit;
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
     * Returns list of missing dependencies
     *
     * @return array
     */
    public function getMissingDeps()
    {
        $result = [];
        if (!class_exists('PragmaRX\Google2FA\Google2FA')) {
            $result[] = [
                'class' => \PhpMyAdmin\Plugins\TwoFactor\Application::getName(),
                'dep' => 'pragmarx/google2fa',
            ];
        }
        if (!class_exists('BaconQrCode\Renderer\Image\Png')) {
            $result[] = [
                'class' => \PhpMyAdmin\Plugins\TwoFactor\Application::getName(),
                'dep' => 'bacon/bacon-qr-code',
            ];
        }
        if (!class_exists('Samyoul\U2F\U2FServer\U2FServer')) {
            $result[] = [
                'class' => \PhpMyAdmin\Plugins\TwoFactor\Key::getName(),
                'dep' => 'samyoul/u2f-php-server',
            ];
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
        $result = 'PhpMyAdmin\\Plugins\\TwoFactorPlugin';
        if (in_array($name, $this->_available)) {
            $result = 'PhpMyAdmin\\Plugins\\TwoFactor\\' . ucfirst($name);
        } elseif (! empty($name)) {
            $result = 'PhpMyAdmin\\Plugins\\TwoFactor\\Invalid';
        }
        return $result;
    }

    /**
     * Returns backend for current user
     *
     * @return PhpMyAdmin\Plugins\TwoFactorPlugin
     */
    public function getBackend()
    {
        $name = $this->getBackendClass($this->config['backend']);
        return new $name($this);
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
        if (empty($_SESSION['two_factor_check'])) {
            $_SESSION['two_factor_check'] = $this->_backend->check();
        }
        return $_SESSION['two_factor_check'];
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        return $this->_backend->getError() . $this->_backend->render();
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     */
    public function setup()
    {
        return $this->_backend->getError() . $this->_backend->setup();
    }

    /**
     * Saves current configuration.
     *
     * @return true|PhpMyAdmin\Message
     */
    public function save()
    {
        return $this->userPreferences->persistOption('2fa', $this->config, null);
    }

    /**
     * Changes two-factor authentication settings
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
        $this->config = [
            'backend' => $name
        ];
        if ($name === '') {
            $cls = $this->getBackendClass($name);
            $this->config['settings'] = [];
            $this->_backend = new $cls($this);
        } else {
            if (! in_array($name, $this->_available)) {
                return false;
            }
            $cls = $this->getBackendClass($name);
            $this->config['settings'] = [];
            $this->_backend = new $cls($this);
            if (! $this->_backend->configure()) {
                return false;
            }
        }
        $result = $this->save();
        if ($result !== true) {
            $result->display();
        }
        return true;
    }

    /**
     * Returns array with all available backends
     *
     * @return array
     */
    public function getAllBackends()
    {
        $all = array_merge([''], $this->available);
        $backends = [];
        foreach ($all as $name) {
            $cls = $this->getBackendClass($name);
            $backends[] = [
                'id' => $cls::$id,
                'name' => $cls::getName(),
                'description' => $cls::getDescription(),
            ];
        }
        return $backends;
    }
}
