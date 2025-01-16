<?php
/**
 * Second authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use chillerlan\Authenticator\Authenticator;
use chillerlan\Authenticator\AuthenticatorOptions;
use chillerlan\Authenticator\Authenticators\HOTP;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\TwoFactor;

use function __;
use function class_exists;

/**
 * HOTP and TOTP based two-factor authentication
 *
 * Also known as Google, Authy, or OTP
 */
class Application extends TwoFactorPlugin
{
    public static string $id = 'application';

    protected Authenticator $authenticator;

    public function __construct(TwoFactor $twofactor)
    {
        parent::__construct($twofactor);

        // init variables/set default values
        $this->twofactor->config['settings']['secret'] ??= '';
        $this->twofactor->config['settings']['backup_counter'] ??= 0;
        // invoke the main TOTP authenticator instance
        $this->authenticator = new Authenticator($this->getAuthenticatorOptions());
    }

    /**
     * Prepares an options instance for both, TOTP and HOTP authenticators
     */
    protected function getAuthenticatorOptions(): AuthenticatorOptions
    {
        // any of these settings can be made optional via $this->twofactor->config['settings']
        return new AuthenticatorOptions([
            'secret_length' => 32,
            'adjacent' => 1,
            'time_offset' => 0,
        ]);
    }

    /**
     * Creates an HOTP code for the given counter value
     */
    protected function getBackupCode(int $counter): string
    {
        $options = $this->getAuthenticatorOptions();
        // using 8 digits here to signify the difference to a TOTP code
        $options->digits = 8;

        return (new HOTP($options))
            ->setSecret($this->twofactor->config['settings']['secret'])
            ->code($counter);
    }

    /**
     * Creates the URI for use with a mobile authenticator via QR Code
     */
    protected function getUri(): string
    {
        $issuer = 'phpMyAdmin (' . $this->getAppId(false) . ')';

        return $this->authenticator
            ->setSecret($this->twofactor->config['settings']['secret'])
            ->getUri(label: $this->twofactor->user, issuer: $issuer, omitSettings: false);
    }

    /**
     * Creates a QR Code for the given string
     */
    protected function getQRCode(string $data): string
    {
        $options = new QROptions([
            'versionMin' => 7,
            'quietzoneSize' => 2,
            'outputType' => QROutputInterface::CUSTOM, // removed in php-qrcode v6
            'outputInterface' => PmaQrCodeSVG::class,
            'cssClass' => 'pma-2fa-qrcode',
        ]);

        return (new QRCode($options))
            ->addByteSegment($data)
            ->render();
    }

    /**
     * Returns the authenticator instance (for use in tests)
     */
    public function getAuthenticator(): Authenticator
    {
        return $this->authenticator;
    }

    /**
     * Checks authentication, returns true on success
     */
    public function check(ServerRequest $request): bool
    {
        $this->provided = false;
        if (! isset($_POST['2fa_code'])) {
            return false;
        }

        $this->provided = true;

        return $this->authenticator
            ->setSecret($this->twofactor->config['settings']['secret'])
            ->verify($_POST['2fa_code']);
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render(ServerRequest $request): string
    {
        return $this->template->render('login/twofactor/application');
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     */
    public function setup(ServerRequest $request): string
    {
        $uri = $this->getUri();
        $qrcode = null;

        if (class_exists(QRCode::class)) {
            $qrcode = $this->getQRCode($uri);
        }

        return $this->template->render('login/twofactor/application_configure', [
            'uri' => $uri,
            'qrcode' => $qrcode,
            'secret' => $this->twofactor->config['settings']['secret'],
            'backup' => $this->getBackupCode($this->twofactor->config['settings']['backup_counter']),
        ]);
    }

    /**
     * Performs backend configuration
     */
    public function configure(ServerRequest $request): bool
    {
        $_SESSION['2fa_application_key'] ??= $this->authenticator->createSecret();

        $this->twofactor->config['settings']['secret'] = $_SESSION['2fa_application_key'];

        $result = $this->check($request);
        if ($result) {
            unset($_SESSION['2fa_application_key']);
        }

        return $result;
    }

    /**
     * Get user visible name
     */
    public static function getName(): string
    {
        return __('Authentication Application (2FA)');
    }

    /**
     * Get user visible description
     */
    public static function getDescription(): string
    {
        return __(
            'Provides authentication using HOTP and TOTP applications such as FreeOTP, Google Authenticator or Authy.',
        );
    }
}
