<?php
/**
 * Two authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use BaconQrCode\Renderer\ImageRenderer;
use CodeLts\U2F\U2FServer\U2FServer;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\TwoFactor\Application;
use PhpMyAdmin\Plugins\TwoFactor\Invalid;
use PhpMyAdmin\Plugins\TwoFactor\Key;
use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PragmaRX\Google2FAQRCode\Google2FA;
use XMLWriter;

use function array_merge;
use function class_exists;
use function extension_loaded;
use function in_array;
use function is_array;
use function is_bool;
use function is_string;
use function ucfirst;

/**
 * Two factor authentication wrapper class
 */
class TwoFactor
{
    /**
     * @var mixed[]
     * @psalm-var array{backend: string, settings: mixed[], type?: 'session'|'db'}
     */
    public array $config;

    protected bool $writable;

    protected TwoFactorPlugin $backend;

    /** @var string[] */
    protected array $available;

    private UserPreferences $userPreferences;

    /**
     * Creates new TwoFactor object
     *
     * @param string $user User name
     */
    public function __construct(public string $user)
    {
        $dbi = DatabaseInterface::getInstance();

        $this->userPreferences = new UserPreferences($dbi, new Relation($dbi), new Template());
        $this->available = $this->getAvailableBackends();
        $this->config = $this->readConfig();
        $this->writable = $this->config['type'] === 'db';
        $this->backend = $this->getBackendForCurrentUser();
    }

    /**
     * Reads the configuration
     *
     * @psalm-return array{backend: string, settings: mixed[], type: 'session'|'db'}
     */
    public function readConfig(): array
    {
        $result = [];
        $config = $this->userPreferences->load();
        if (isset($config['config_data']['2fa']) && is_array($config['config_data']['2fa'])) {
            $result = $config['config_data']['2fa'];
        }

        $backend = '';
        if (isset($result['backend']) && is_string($result['backend'])) {
            $backend = $result['backend'];
        }

        $settings = [];
        if (isset($result['settings']) && is_array($result['settings'])) {
            $settings = $result['settings'];
        }

        return ['backend' => $backend, 'settings' => $settings, 'type' => $config['type']];
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function getBackend(): TwoFactorPlugin
    {
        return $this->backend;
    }

    /** @return string[] */
    public function getAvailable(): array
    {
        return $this->available;
    }

    public function showSubmit(): bool
    {
        return $this->backend::$showSubmit;
    }

    /**
     * Returns list of available backends
     *
     * @return string[]
     */
    public function getAvailableBackends(): array
    {
        $result = [];
        if (Config::getInstance()->settings['DBG']['simple2fa']) {
            $result[] = 'simple';
        }

        if (
            class_exists(Google2FA::class)
            && class_exists(ImageRenderer::class)
            && (class_exists(XMLWriter::class) || extension_loaded('imagick'))
        ) {
            $result[] = 'application';
        }

        $result[] = 'WebAuthn';

        if (class_exists(U2FServer::class)) {
            $result[] = 'key';
        }

        return $result;
    }

    /**
     * Returns list of missing dependencies
     *
     * @return array<int, array{class: string, dep: string}>
     */
    public function getMissingDeps(): array
    {
        $result = [];
        if (! class_exists(Google2FA::class)) {
            $result[] = ['class' => Application::getName(), 'dep' => 'pragmarx/google2fa-qrcode'];
        }

        if (! class_exists(ImageRenderer::class)) {
            $result[] = ['class' => Application::getName(), 'dep' => 'bacon/bacon-qr-code'];
        }

        if (! class_exists(U2FServer::class)) {
            $result[] = ['class' => Key::getName(), 'dep' => 'code-lts/u2f-php-server'];
        }

        return $result;
    }

    /**
     * Returns class name for given name
     *
     * @param string $name Backend name
     *
     * @psalm-return class-string<TwoFactorPlugin>
     */
    public function getBackendClass(string $name): string
    {
        $result = TwoFactorPlugin::class;
        if (in_array($name, $this->available, true)) {
            /** @psalm-var class-string<TwoFactorPlugin> $result */
            $result = 'PhpMyAdmin\\Plugins\\TwoFactor\\' . ucfirst($name);
        } elseif ($name !== '') {
            $result = Invalid::class;
        }

        return $result;
    }

    /**
     * Returns backend for current user
     */
    public function getBackendForCurrentUser(): TwoFactorPlugin
    {
        $name = $this->getBackendClass($this->config['backend']);

        return new $name($this);
    }

    /**
     * Checks authentication, returns true on success
     *
     * @param bool $skipSession Skip session cache
     */
    public function check(ServerRequest $request, bool $skipSession = false): bool
    {
        if ($skipSession) {
            return $this->backend->check($request);
        }

        if (! isset($_SESSION['two_factor_check']) || ! is_bool($_SESSION['two_factor_check'])) {
            $_SESSION['two_factor_check'] = $this->backend->check($request);
        }

        return $_SESSION['two_factor_check'];
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render(ServerRequest $request): string
    {
        return $this->backend->getError() . $this->backend->render($request);
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     */
    public function setup(ServerRequest $request): string
    {
        return $this->backend->getError() . $this->backend->setup($request);
    }

    /**
     * Saves current configuration.
     *
     * @return true|Message
     */
    public function save(): bool|Message
    {
        return $this->userPreferences->persistOption('2fa', $this->config, null);
    }

    /**
     * Changes two-factor authentication settings
     *
     * The object might stay in partially changed setup
     * if configuration fails.
     *
     * @param string $name Backend name
     */
    public function configure(ServerRequest $request, string $name): bool
    {
        $this->config = ['backend' => $name, 'settings' => []];
        if ($name === '') {
            $cls = $this->getBackendClass($name);
            $this->backend = new $cls($this);
        } else {
            if (! in_array($name, $this->available, true)) {
                return false;
            }

            $cls = $this->getBackendClass($name);
            $this->backend = new $cls($this);
            if (! $this->backend->configure($request)) {
                return false;
            }
        }

        $result = $this->save();
        if ($result !== true) {
            echo $result->getDisplay();
        }

        return true;
    }

    /**
     * Returns array with all available backends
     *
     * @return array<int, array{id: mixed, name: mixed, description: mixed}>
     */
    public function getAllBackends(): array
    {
        $all = array_merge([''], $this->available);
        $backends = [];
        foreach ($all as $name) {
            $cls = $this->getBackendClass($name);
            $backends[] = ['id' => $cls::$id, 'name' => $cls::getName(), 'description' => $cls::getDescription()];
        }

        return $backends;
    }
}
