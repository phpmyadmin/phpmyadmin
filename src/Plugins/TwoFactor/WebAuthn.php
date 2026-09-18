<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Crypto\Base64;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\WebAuthn\CustomServer;
use PhpMyAdmin\WebAuthn\Server;
use PhpMyAdmin\WebAuthn\WebauthnLibServer;
use SodiumException;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webmozart\Assert\Assert;

use function __;
use function class_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function random_bytes;

/**
 * Two-factor authentication plugin for the WebAuthn/FIDO2 protocol.
 */
class WebAuthn extends TwoFactorPlugin
{
    public static string $id = 'WebAuthn';

    private Server $server;

    public function __construct(TwoFactor $twofactor)
    {
        parent::__construct($twofactor);

        if (
            ! isset($this->twofactor->config['settings']['userHandle'])
            || ! is_string($this->twofactor->config['settings']['userHandle'])
        ) {
            $this->twofactor->config['settings']['userHandle'] = '';
        }

        if (
            ! isset($this->twofactor->config['settings']['credentials'])
            || ! is_array($this->twofactor->config['settings']['credentials'])
        ) {
            $this->twofactor->config['settings']['credentials'] = [];
        }

        $this->server = $this->createServer();
    }

    private function createServer(): Server
    {
        return class_exists(PublicKeyCredential::class) ? new WebauthnLibServer($this->twofactor) : new CustomServer();
    }

    public function setServer(Server $server): void
    {
        $this->server = $server;
    }

    public function render(ServerRequest $request): string
    {
        $userHandle = Base64::decodeUrlSafeNoPadding($this->getUserHandleFromSettings());
        $requestOptions = $this->server->getCredentialRequestOptions(
            $this->twofactor->user,
            $userHandle,
            $request->getUri()->getHost(),
            $this->getAllowedCredentials(),
        );
        $requestOptionsEncoded = json_encode($requestOptions);
        $_SESSION['WebAuthnCredentialRequestOptions'] = $requestOptionsEncoded;
        $this->loadScripts();

        return $this->template->render(
            'login/twofactor/webauthn_request',
            ['request_options' => $requestOptionsEncoded],
        );
    }

    public function check(ServerRequest $request): bool
    {
        $this->provided = false;
        $authenticatorResponse = $request->getParsedBodyParam('webauthn_request_response', '');
        if ($authenticatorResponse === '' || ! isset($_SESSION['WebAuthnCredentialRequestOptions'])) {
            return false;
        }

        $this->provided = true;

        /** @var mixed $credentialRequestOptions */
        $credentialRequestOptions = $_SESSION['WebAuthnCredentialRequestOptions'];
        unset($_SESSION['WebAuthnCredentialRequestOptions']);

        try {
            Assert::stringNotEmpty($authenticatorResponse);
            Assert::stringNotEmpty($credentialRequestOptions);
            $requestOptions = json_decode($credentialRequestOptions, true);
            Assert::isArray($requestOptions);
            Assert::keyExists($requestOptions, 'challenge');
            Assert::stringNotEmpty($requestOptions['challenge']);
            $this->server->parseAndValidateAssertionResponse(
                $authenticatorResponse,
                $this->getAllowedCredentials(),
                $requestOptions['challenge'],
                $request,
            );
        } catch (Throwable $exception) {
            $this->message = $exception->getMessage();

            return false;
        }

        return true;
    }

    public function setup(ServerRequest $request): string
    {
        $userId = Base64::encode(random_bytes(32));
        $host = $request->getUri()->getHost();
        $creationOptions = $this->server->getCredentialCreationOptions($this->twofactor->user, $userId, $host);
        $creationOptionsEncoded = json_encode($creationOptions);
        $_SESSION['WebAuthnCredentialCreationOptions'] = $creationOptionsEncoded;
        $this->loadScripts();

        return $this->template->render(
            'login/twofactor/webauthn_creation',
            ['creation_options' => $creationOptionsEncoded],
        );
    }

    public function configure(ServerRequest $request): bool
    {
        $this->provided = false;
        $authenticatorResponse = $request->getParsedBodyParam('webauthn_creation_response', '');
        if ($authenticatorResponse === '' || ! isset($_SESSION['WebAuthnCredentialCreationOptions'])) {
            return false;
        }

        $this->provided = true;

        /** @var mixed $credentialCreationOptions */
        $credentialCreationOptions = $_SESSION['WebAuthnCredentialCreationOptions'];
        unset($_SESSION['WebAuthnCredentialCreationOptions']);

        try {
            Assert::stringNotEmpty($authenticatorResponse);
            Assert::stringNotEmpty($credentialCreationOptions);
            $credential = $this->server->parseAndValidateAttestationResponse(
                $authenticatorResponse,
                $credentialCreationOptions,
                $request,
            );
            $this->saveCredential($credential);
        } catch (Throwable $exception) {
            $this->message = $exception->getMessage();

            return false;
        }

        return true;
    }

    public static function getName(): string
    {
        return __('Hardware Security Key (WebAuthn/FIDO2)');
    }

    public static function getDescription(): string
    {
        return __(
            'Provides authentication using hardware security tokens supporting the WebAuthn/FIDO2 protocol,'
            . ' such as a YubiKey.',
        );
    }

    private function loadScripts(): void
    {
        $response = ResponseRenderer::getInstance();
        $scripts = $response->getHeader()->getScripts();
        $scripts->addFile('webauthn.js');
    }

    /** @psalm-return list<array{id: non-empty-string, type: non-empty-string}> */
    private function getAllowedCredentials(): array
    {
        $allowedCredentials = [];
        /** @psalm-var array<array<string, mixed>> $credentials */
        $credentials = $this->twofactor->config['settings']['credentials'];
        foreach ($credentials as $credential) {
            if (
                ! is_string($credential['publicKeyCredentialId']) || $credential['publicKeyCredentialId'] === ''
                || ! is_string($credential['type']) || $credential['type'] === ''
            ) {
                continue;
            }

            $allowedCredentials[] = ['type' => $credential['type'], 'id' => $credential['publicKeyCredentialId']];
        }

        return $allowedCredentials;
    }

    /**
     * @psalm-param mixed[] $credential
     *
     * @throws SodiumException
     */
    private function saveCredential(array $credential): void
    {
        Assert::keyExists($credential, 'publicKeyCredentialId');
        Assert::stringNotEmpty($credential['publicKeyCredentialId']);
        Assert::keyExists($credential, 'userHandle');
        Assert::string($credential['userHandle']);
        Assert::isArray($this->twofactor->config['settings']['credentials']);
        $id = Base64::encode(Base64::decodeUrlSafeNoPadding($credential['publicKeyCredentialId']));
        $this->twofactor->config['settings']['credentials'][$id] = $credential;
        $this->twofactor->config['settings']['userHandle'] = $credential['userHandle'];
    }

    private function getUserHandleFromSettings(): string
    {
        Assert::string($this->twofactor->config['settings']['userHandle']);

        return $this->twofactor->config['settings']['userHandle'];
    }
}
