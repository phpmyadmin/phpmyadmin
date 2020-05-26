<?php
/**
 * Two authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Plugins\TwoFactor\Application;
use PhpMyAdmin\Plugins\TwoFactor\Invalid;
use PhpMyAdmin\Plugins\TwoFactor\Key;
use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PragmaRX\Google2FAQRCode\Google2FA;
use Samyoul\U2F\U2FServer\U2FServer;
use function array_merge;
use function class_exists;
use function in_array;
use function ucfirst;

/**
 * Two factor authentication wrapper class
 */
class TwoFactor
{
    /** @var string */
    public $user;

    /** @var array */
    public $config;

    /** @var bool */
    protected $_writable;

    /** @var TwoFactorPlugin */
    protected $_backend;

    /** @var array */
    protected $_available;

    /** @var UserPreferences */
    private $userPreferences;

    /**
     * Creates new TwoFactor object
     *
     * @param string $user User name
     */
    public function __construct($user)
    {
        global $dbi;

        $dbi->initRelationParamsCache();

        $this->userPreferences = new UserPreferences();
        $this->user = $user;
        $this->_available = $this->getAvailableBackends();
        $this->config = $this->readConfig();
        $this->_writable = ($this->config['type'] == 'db');
        $this->_backend = $this->getBackendForCurrentUser();
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

    public function isWritable(): bool
    {
        return $this->_writable;
    }

    public function getBackend(): TwoFactorPlugin
    {
        return $this->_backend;
    }

    /**
     * @return array
     */
    public function getAvailable(): array
    {
        return $this->_available;
    }

    public function showSubmit(): bool
    {
        $backend = $this->_backend;

        return $backend::$showSubmit;
    }

    /**
     * Returns list of available backends
     *
     * @return array
     */
    public function getAvailableBackends()
    {
        $result = [];
        if ($GLOBALS['cfg']['DBG']['simple2fa']) {
            $result[] = 'simple';
        }
        if (class_exists(Google2FA::class)) {
            $result[] = 'application';
        }
        if (class_exists(U2FServer::class)) {
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
        if (! class_exists(Google2FA::class)) {
            $result[] = [
                'class' => Application::getName(),
                'dep' => 'pragmarx/google2fa-qrcode',
            ];
        }
        if (! class_exists('BaconQrCode\Renderer\Image\Png')) {
            $result[] = [
                'class' => Application::getName(),
                'dep' => 'bacon/bacon-qr-code',
            ];
        }
        if (! class_exists(U2FServer::class)) {
            $result[] = [
                'class' => Key::getName(),
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
        $result = TwoFactorPlugin::class;
        if (in_array($name, $this->_available)) {
            $result = 'PhpMyAdmin\\Plugins\\TwoFactor\\' . ucfirst($name);
        } elseif (! empty($name)) {
            $result = Invalid::class;
        }

        return $result;
    }

    /**
     * Returns backend for current user
     *
     * @return TwoFactorPlugin
     */
    public function getBackendForCurrentUser()
    {
        $name = $this->getBackendClass($this->config['backend']);

        return new $name($this);
    }

    /**
     * Checks authentication, returns true on success
     *
     * @param bool $skip_session Skip session cache
     *
     * @return bool
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
     * @return true|Message
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
     * @return bool
     */
    public function configure($name)
    {
        $this->config = ['backend' => $name];
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
        $all = array_merge([''], $this->_available);
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
