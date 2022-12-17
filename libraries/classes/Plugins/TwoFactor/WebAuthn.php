<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\TwoFactor;
use Throwable;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;
use Webmozart\Assert\Assert;

use function __;
use function array_map;
use function base64_encode;
use function is_array;
use function json_encode;

class WebAuthn extends TwoFactorPlugin
{
    /** @var string */
    public static $id = 'WebAuthn';

    /** @var PublicKeyCredentialRpEntity */
    private $relyingPartyEntity;

    /** @var PublicKeyCredentialSourceRepository */
    private $publicKeyCredentialSourceRepository;

    /** @var Server */
    private $server;

    public function __construct(TwoFactor $twofactor)
    {
        parent::__construct($twofactor);
        if (
            ! isset($this->twofactor->config['settings']['WebAuthn'])
            || ! is_array($this->twofactor->config['settings']['WebAuthn'])
        ) {
            $this->twofactor->config['settings']['WebAuthn'] = [];
        }

        $this->relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $this->getAppId(false) . ')');
        $this->publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $this->server = new Server($this->relyingPartyEntity, $this->publicKeyCredentialSourceRepository);
    }

    public function check(): bool
    {
        $this->provided = false;
        $request = $GLOBALS['request'];
        $authenticatorResponse = $request->getParsedBodyParam('webauthn_authentication_response');
        if ($authenticatorResponse === null) {
            return false;
        }

        $this->provided = true;
        $name = $this->twofactor->user;
        $userEntity = new PublicKeyCredentialUserEntity($name, $name, $name);

        /** @var mixed $credentialRequestOptions */
        $credentialRequestOptions = $_SESSION['WebAuthnCredentialRequestOptions'] ?? null;
        unset($_SESSION['WebAuthnCredentialRequestOptions']);

        try {
            Assert::stringNotEmpty($authenticatorResponse);
            Assert::isInstanceOf($credentialRequestOptions, PublicKeyCredentialRequestOptions::class);
            $publicKeyCredentialSource = $this->server->loadAndCheckAssertionResponse(
                $authenticatorResponse,
                $credentialRequestOptions,
                $userEntity,
                $request
            );
        } catch (Throwable $exception) {
            $this->message = $exception->getMessage();

            return false;
        }

        return true;
    }

    public function render(): string
    {
        $name = $this->twofactor->user;
        $userEntity = new PublicKeyCredentialUserEntity($name, $name, $name);
        $credentialSources = $this->publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);

        $allowedCredentials = array_map(
            static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                return $credential->getPublicKeyCredentialDescriptor();
            },
            $credentialSources
        );

        $publicKeyCredentialRequestOptions = $this->server->generatePublicKeyCredentialRequestOptions(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            $allowedCredentials
        );
        $requestJson = json_encode($publicKeyCredentialRequestOptions);
        $_SESSION['WebAuthnCredentialRequestOptions'] = $publicKeyCredentialRequestOptions;

        $this->loadScripts();

        return $this->template->render('login/twofactor/webauthn', ['request' => $requestJson]);
    }

    public function setup(): string
    {
        $name = $this->twofactor->user;
        $userEntity = new PublicKeyCredentialUserEntity($name, $name, $name);
        $publicKeyCredentialCreationOptions = $this->server->generatePublicKeyCredentialCreationOptions($userEntity);

        $optionsJson = json_encode($publicKeyCredentialCreationOptions);
        $_SESSION['WebAuthnCredentialCreationOptions'] = $publicKeyCredentialCreationOptions;

        $this->loadScripts();

        return $this->template->render('login/twofactor/webauthn_configure', ['options' => $optionsJson]);
    }

    public function configure(): bool
    {
        $this->provided = false;
        $request = $GLOBALS['request'];
        $authenticatorResponse = $request->getParsedBodyParam('webauthn_registration_response');
        if ($authenticatorResponse === null) {
            return false;
        }

        $this->provided = true;

        /** @var mixed $credentialCreationOptions */
        $credentialCreationOptions = $_SESSION['WebAuthnCredentialCreationOptions'] ?? null;
        unset($_SESSION['WebAuthnCredentialCreationOptions']);

        try {
            Assert::stringNotEmpty($authenticatorResponse);
            Assert::isInstanceOf($credentialCreationOptions, PublicKeyCredentialCreationOptions::class);
            $publicKeyCredentialSource = $this->server->loadAndCheckAttestationResponse(
                $authenticatorResponse,
                $credentialCreationOptions,
                $request
            );
            $this->publicKeyCredentialSourceRepository->saveCredentialSource($publicKeyCredentialSource);
        } catch (Throwable $exception) {
            $this->message = $exception->getMessage();

            return false;
        }

        return true;
    }

    public static function getName(): string
    {
        return __('Hardware Security Key (FIDO2/WebAuthn)');
    }

    public static function getDescription(): string
    {
        return __(
            'Provides authentication using hardware security tokens supporting FIDO2/WebAuthn, such as a YubiKey.'
        );
    }

    private function loadScripts(): void
    {
        $response = ResponseRenderer::getInstance();
        $scripts = $response->getHeader()->getScripts();
        $scripts->addFile('webauthn.js');
    }

    private function createPublicKeyCredentialSourceRepository(): PublicKeyCredentialSourceRepository
    {
        return new class ($this->twofactor) implements PublicKeyCredentialSourceRepository {
            /** @var TwoFactor */
            private $twoFactor;

            public function __construct(TwoFactor $twoFactor)
            {
                $this->twoFactor = $twoFactor;
            }

            public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
            {
                $data = $this->read();
                if (isset($data[base64_encode($publicKeyCredentialId)])) {
                    return PublicKeyCredentialSource::createFromArray($data[base64_encode($publicKeyCredentialId)]);
                }

                return null;
            }

            /**
             * @return PublicKeyCredentialSource[]
             */
            public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
            {
                $sources = [];
                foreach ($this->read() as $data) {
                    $source = PublicKeyCredentialSource::createFromArray($data);
                    if ($source->getUserHandle() !== $publicKeyCredentialUserEntity->getId()) {
                        continue;
                    }

                    $sources[] = $source;
                }

                return $sources;
            }

            public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
            {
                $data = $this->read();
                $id = $publicKeyCredentialSource->getPublicKeyCredentialId();
                $data[base64_encode($id)] = $publicKeyCredentialSource;
                $this->write($data);
            }

            /**
             * @return mixed[][]
             */
            private function read(): array
            {
                return $this->twoFactor->config['settings']['WebAuthn'];
            }

            /**
             * @param mixed[] $data
             */
            private function write(array $data): void
            {
                $this->twoFactor->config['settings']['WebAuthn'] = $data;
            }
        };
    }
}
