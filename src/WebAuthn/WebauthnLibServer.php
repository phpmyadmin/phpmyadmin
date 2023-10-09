<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use PhpMyAdmin\TwoFactor;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;
use Webmozart\Assert\Assert;

use function array_map;
use function base64_encode;
use function random_bytes;
use function rtrim;
use function sodium_base642bin;
use function sodium_bin2base64;
use function str_ends_with;

use const SODIUM_BASE64_VARIANT_ORIGINAL;
use const SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

final class WebauthnLibServer implements Server
{
    private const CHALLENGE_SIZE = 32;
    private const TIMEOUT = 60000;

    public function __construct(private TwoFactor $twoFactor)
    {
    }

    /** @inheritDoc */
    public function getCredentialCreationOptions(string $userName, string $userId, string $relyingPartyId): array
    {
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $relyingPartyId . ')', $relyingPartyId);

        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::create(
            $relyingPartyEntity,
            $userEntity,
            random_bytes(self::CHALLENGE_SIZE),
            $this->getPublicKeyCredentialParametersList(),
            AuthenticatorSelectionCriteria::create(
                AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM,
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            timeout: self::TIMEOUT,
        );

        $encodedChallenge = sodium_bin2base64(
            $publicKeyCredentialCreationOptions->challenge,
            SODIUM_BASE64_VARIANT_ORIGINAL,
        );
        Assert::stringNotEmpty($encodedChallenge);
        Assert::notNull($publicKeyCredentialCreationOptions->authenticatorSelection);

        return [
            'challenge' => $encodedChallenge,
            'rp' => [
                'name' => $publicKeyCredentialCreationOptions->rp->name,
                'id' => $publicKeyCredentialCreationOptions->rp->id,
            ],
            'user' => [
                'id' => sodium_bin2base64(
                    $publicKeyCredentialCreationOptions->user->id,
                    SODIUM_BASE64_VARIANT_ORIGINAL,
                ),
                'name' => $publicKeyCredentialCreationOptions->user->name,
                'displayName' => $publicKeyCredentialCreationOptions->user->displayName,
            ],
            'pubKeyCredParams' => array_map(static function (PublicKeyCredentialParameters $object): array {
                return $object->jsonSerialize();
            }, $publicKeyCredentialCreationOptions->pubKeyCredParams),
            'authenticatorSelection' => $publicKeyCredentialCreationOptions->authenticatorSelection->jsonSerialize(),
            'timeout' => $publicKeyCredentialCreationOptions->timeout,
            'attestation' => $publicKeyCredentialCreationOptions->attestation,
        ];
    }

    /** @inheritDoc */
    public function getCredentialRequestOptions(
        string $userName,
        string $userId,
        string $relyingPartyId,
        array $allowedCredentials,
    ): array {
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $relyingPartyId . ')', $relyingPartyId);
        $credentialSources = $this->findAllForUserEntity($userEntity);
        $allowedCredentials = array_map(
            static fn (
                PublicKeyCredentialSource $credential,
            ): PublicKeyCredentialDescriptor => $credential->getPublicKeyCredentialDescriptor(),
            $credentialSources,
        );

        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::create(
            random_bytes(self::CHALLENGE_SIZE),
            $relyingPartyEntity->id,
            $allowedCredentials,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            self::TIMEOUT,
        );

        $requestOptions = [
            'challenge' => sodium_bin2base64(
                $publicKeyCredentialRequestOptions->challenge,
                SODIUM_BASE64_VARIANT_ORIGINAL,
            ),
            'rpId' => (string) $publicKeyCredentialRequestOptions->rpId,
            'userVerification' => (string) $publicKeyCredentialRequestOptions->userVerification,
            'timeout' => (int) $publicKeyCredentialRequestOptions->timeout,
        ];

        if ($publicKeyCredentialRequestOptions->allowCredentials !== []) {
            $requestOptions['allowCredentials'] = [];
            foreach ($publicKeyCredentialRequestOptions->allowCredentials as $credential) {
                $allowedCredential = [
                    'type' => $credential->type,
                    'id' => sodium_bin2base64($credential->id, SODIUM_BASE64_VARIANT_ORIGINAL),
                ];

                $requestOptions['allowCredentials'][] = $allowedCredential;
            }
        }

        return $requestOptions;
    }

    /** @inheritDoc */
    public function parseAndValidateAssertionResponse(
        string $assertionResponseJson,
        array $allowedCredentials,
        string $challenge,
        ServerRequestInterface $request,
    ): void {
        Assert::string($this->twoFactor->config['settings']['userHandle']);
        $userHandle = sodium_base642bin(
            $this->twoFactor->config['settings']['userHandle'],
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );

        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
        $attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager);
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader);
        $publicKeyCredential = $publicKeyCredentialLoader->load($assertionResponseJson);
        $authenticatorAssertionResponse = $publicKeyCredential->response;
        Assert::isInstanceOf($authenticatorAssertionResponse, AuthenticatorAssertionResponse::class);

        $credentialSource = $this->findOneByCredentialId($publicKeyCredential->rawId);
        Assert::notNull($credentialSource);

        $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
            null,
            null,
            ExtensionOutputCheckerHandler::create(),
            $this->getAlgorithmManager(),
        );
        $authenticatorAssertionResponseValidator->check(
            $credentialSource,
            $authenticatorAssertionResponse,
            PublicKeyCredentialRequestOptions::createFromString($assertionResponseJson),
            $request->getUri()->getHost(),
            $userHandle,
        );
    }

    /** @inheritDoc */
    public function parseAndValidateAttestationResponse(
        string $attestationResponse,
        string $credentialCreationOptions,
        ServerRequestInterface $request,
    ): array {
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
        $attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager);
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader);
        $publicKeyCredential = $publicKeyCredentialLoader->load($attestationResponse);
        $authenticatorAttestationResponse = $publicKeyCredential->response;
        Assert::isInstanceOf($authenticatorAttestationResponse, AuthenticatorAttestationResponse::class);

        $authenticatorAttestationResponseValidator = AuthenticatorAttestationResponseValidator::create(
            $attestationStatementSupportManager,
            null,
            null,
            ExtensionOutputCheckerHandler::create(),
        );
        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            $authenticatorAttestationResponse,
            PublicKeyCredentialCreationOptions::createFromString($credentialCreationOptions),
            $request->getUri()->getHost(),
        );

        return $publicKeyCredentialSource->jsonSerialize();
    }

    private function findOneByCredentialId(string $publicKeyCredentialId): PublicKeyCredentialSource|null
    {
        $encodedId = base64_encode($publicKeyCredentialId);
        $data = $this->read();

        return isset($data[$encodedId]) ? PublicKeyCredentialSource::createFromArray($data[$encodedId]) : null;
    }

    /** @return PublicKeyCredentialSource[] */
    private function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $sources = [];
        foreach ($this->read() as $data) {
            $source = PublicKeyCredentialSource::createFromArray($data);
            if ($source->userHandle !== $publicKeyCredentialUserEntity->id) {
                continue;
            }

            $sources[] = $source;
        }

        return $sources;
    }

    /** @return mixed[][] */
    private function read(): array
    {
        /** @psalm-var list<mixed[]> $credentials */
        $credentials = $this->twoFactor->config['settings']['credentials'];
        foreach ($credentials as &$credential) {
            if (str_ends_with($credential['userHandle'], '=')) {
                $credential['userHandle'] = rtrim($credential['userHandle'], '=');
            }

            if (isset($credential['trustPath'])) {
                continue;
            }

            $credential['trustPath'] = ['type' => EmptyTrustPath::class];
        }

        return $credentials;
    }

    private function getAlgorithmManager(): Manager
    {
        return Manager::create()->add(
            ES256::create(),
            ES256K::create(),
            ES384::create(),
            ES512::create(),
            RS256::create(),
            RS384::create(),
            RS512::create(),
            PS256::create(),
            PS384::create(),
            PS512::create(),
            Ed256::create(),
            Ed512::create(),
        );
    }

    /** @return PublicKeyCredentialParameters[] */
    private function getPublicKeyCredentialParametersList(): array
    {
        $algorithmManager = $this->getAlgorithmManager();
        $publicKeyCredentialParametersList = [];
        foreach ($algorithmManager->all() as $algorithm) {
            $publicKeyCredentialParametersList[] = new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm::identifier(),
            );
        }

        return $publicKeyCredentialParametersList;
    }
}
