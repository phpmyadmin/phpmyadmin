<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use Cose\Algorithms;
use PhpMyAdmin\Crypto\Base64;
use PhpMyAdmin\TwoFactor;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorDataLoader;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_map;
use function hash_equals;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function random_bytes;
use function rtrim;
use function str_ends_with;

use const JSON_THROW_ON_ERROR;

final class WebauthnLibServer implements Server
{
    private const TIMEOUT = 60000;
    private const CHALLENGE_SIZE = 32;

    public function __construct(private TwoFactor $twofactor)
    {
    }

    /** @inheritDoc */
    public function getCredentialCreationOptions(string $userName, string $userId, string $relyingPartyId): array
    {
        $publicKeyCredentialCreationOptions = $this->getPublicKeyCredentialCreationOptions(
            $userName,
            $userId,
            $relyingPartyId,
            random_bytes(self::CHALLENGE_SIZE),
        );

        /** @psalm-var array{
         *   challenge: non-empty-string,
         *   rp: array{name: non-empty-string, id: non-empty-string},
         *   user: array{id: non-empty-string, name: non-empty-string, displayName: non-empty-string},
         *   pubKeyCredParams: list<array{alg: int, type: 'public-key'}>,
         *   authenticatorSelection: array<string, string>,
         *   timeout: positive-int,
         *   attestation: non-empty-string
         * } $creationOptions */
        $creationOptions = $this->normalize($publicKeyCredentialCreationOptions);
        $creationOptions['challenge'] = Base64::encode(Base64::decodeUrlSafeNoPadding($creationOptions['challenge']));
        Assert::stringNotEmpty($creationOptions['challenge']);

        return $creationOptions;
    }

    /** @inheritDoc */
    public function getCredentialRequestOptions(
        string $userName,
        string $userId,
        string $relyingPartyId,
        array $allowedCredentials,
    ): array {
        $publicKeyCredentialRequestOptions = $this->getPublicKeyCredentialRequestOptions(
            $userName,
            $userId,
            $relyingPartyId,
            random_bytes(self::CHALLENGE_SIZE),
        );

        /**
         * @psalm-var array{
         *   challenge: string,
         *   allowCredentials?: list<array{id: non-empty-string, type: non-empty-string}>
         * } $requestOptions
         */
        $requestOptions = $this->normalize($publicKeyCredentialRequestOptions);
        $requestOptions['challenge'] = Base64::encode(Base64::decodeUrlSafeNoPadding($requestOptions['challenge']));
        if (isset($requestOptions['allowCredentials'])) {
            foreach ($requestOptions['allowCredentials'] as $key => $credential) {
                $requestOptions['allowCredentials'][$key]['id'] = Base64::encode(
                    Base64::decodeUrlSafeNoPadding($credential['id']),
                );
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
        Assert::string($this->twofactor->config['settings']['userHandle']);
        $userHandle = Base64::decodeUrlSafeNoPadding($this->twofactor->config['settings']['userHandle']);
        $host = $request->getUri()->getHost();

        $publicKeyCredentialRequestOptions = $this->getPublicKeyCredentialRequestOptions(
            $this->twofactor->user,
            $userHandle,
            $host,
            Base64::decode($challenge),
        );

        $publicKeyCredential = $this->getPublicKeyCredential($assertionResponseJson);
        $authenticatorResponse = $publicKeyCredential->response;
        Assert::isInstanceOf(
            $authenticatorResponse,
            AuthenticatorAssertionResponse::class,
            'Not an authenticator assertion response',
        );

        $publicKeyCredentialSource = $this->findCredentialByCredentialId($publicKeyCredential->rawId);
        Assert::notNull($publicKeyCredentialSource);

        $csmFactory = new CeremonyStepManagerFactory();
        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            ceremonyStepManager: $csmFactory->requestCeremony(),
        );

        $credential = $authenticatorAssertionResponseValidator->check(
            $publicKeyCredentialSource,
            $authenticatorResponse,
            $publicKeyCredentialRequestOptions,
            $host,
            $userHandle,
        );

        $this->saveCredentialSource($credential);
    }

    /** @inheritDoc */
    public function parseAndValidateAttestationResponse(
        string $attestationResponse,
        string $credentialCreationOptions,
        ServerRequestInterface $request,
    ): array {
        $creationOptions = json_decode($credentialCreationOptions, true, flags: JSON_THROW_ON_ERROR);
        Assert::isArray($creationOptions);
        Assert::keyExists($creationOptions, 'challenge');
        Assert::stringNotEmpty($creationOptions['challenge']);
        Assert::keyExists($creationOptions, 'user');
        Assert::isArray($creationOptions['user']);
        Assert::keyExists($creationOptions['user'], 'id');
        Assert::stringNotEmpty($creationOptions['user']['id']);
        $host = $request->getUri()->getHost();

        $publicKeyCredentialCreationOptions = $this->getPublicKeyCredentialCreationOptions(
            $this->twofactor->user,
            Base64::decode($creationOptions['user']['id']),
            $host,
            Base64::decode($creationOptions['challenge']),
        );

        $publicKeyCredential = $this->getPublicKeyCredential($attestationResponse);
        $authenticatorResponse = $publicKeyCredential->response;
        Assert::isInstanceOf(
            $authenticatorResponse,
            AuthenticatorAttestationResponse::class,
            'Not an authenticator attestation response',
        );

        $csmFactory = new CeremonyStepManagerFactory();
        $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
            ceremonyStepManager: $csmFactory->creationCeremony(),
        );

        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            $authenticatorResponse,
            $publicKeyCredentialCreationOptions,
            $host,
        );

        return $this->normalize($publicKeyCredentialSource);
    }

    private function findCredentialByCredentialId(string $publicKeyCredentialId): PublicKeyCredentialSource|null
    {
        $credentials = $this->readCredentialsFromConfig();
        $credentialId = Base64::encode($publicKeyCredentialId);
        if (isset($credentials[$credentialId])) {
            return $this->getPublicKeyCredentialSource($credentials[$credentialId]);
        }

        return null;
    }

    /** @return PublicKeyCredentialSource[] */
    private function findCredentialsForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $sources = [];
        foreach ($this->readCredentialsFromConfig() as $data) {
            $source = $this->getPublicKeyCredentialSource($data);
            if ($source->userHandle !== $publicKeyCredentialUserEntity->id) {
                continue;
            }

            $sources[] = $source;
        }

        return $sources;
    }

    private function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $data = $this->readCredentialsFromConfig();
        $id = $publicKeyCredentialSource->publicKeyCredentialId;
        $encoded = json_encode($publicKeyCredentialSource, JSON_THROW_ON_ERROR);
        $normalized = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
        Assert::isArray($normalized);
        $data[Base64::encode($id)] = $normalized;
        $this->writeCredentialsToConfig($data);
    }

    /** @return mixed[][] */
    private function readCredentialsFromConfig(): array
    {
        /** @psalm-var list<mixed[]> $credentials */
        $credentials = $this->twofactor->config['settings']['credentials'];
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

    /** @param mixed[] $data */
    private function writeCredentialsToConfig(array $data): void
    {
        $this->twofactor->config['settings']['credentials'] = $data;
    }

    /** @return mixed[] */
    private function normalize(object $object): array
    {
        $encoded = json_encode($object, JSON_THROW_ON_ERROR);
        $normalized = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
        Assert::isArray($normalized);

        return $normalized;
    }

    private function getPublicKeyCredentialCreationOptions(
        string $userName,
        string $userId,
        string $relyingPartyId,
        string $challenge,
    ): PublicKeyCredentialCreationOptions {
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $relyingPartyId . ')', $relyingPartyId);

        /**
         * The authenticators must use one of the algorithms in this list, respecting the order of preference on this
         * list. Algorithms ES256 and RS256 are required by the specification.
         */
        $publicKeyCredentialParameters = [
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES256K),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES256),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_RS256),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_PS256),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ED256),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_RS512),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_PS512),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES512),
            PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_EDDSA),
        ];

        $authenticatorSelection = AuthenticatorSelectionCriteria::create(
            AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_CROSS_PLATFORM,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
        );

        return PublicKeyCredentialCreationOptions::create(
            $relyingPartyEntity,
            $userEntity,
            $challenge,
            $publicKeyCredentialParameters,
            $authenticatorSelection,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            timeout: self::TIMEOUT,
        );
    }

    private function getPublicKeyCredentialRequestOptions(
        string $userName,
        string $userId,
        string $relyingPartyId,
        string $challenge,
    ): PublicKeyCredentialRequestOptions {
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $credentialSources = $this->findCredentialsForUserEntity($userEntity);

        $allowedPublicKeyCredentials = array_map(
            static fn (
                PublicKeyCredentialSource $credential,
            ): PublicKeyCredentialDescriptor => $credential->getPublicKeyCredentialDescriptor(),
            $credentialSources,
        );

        return PublicKeyCredentialRequestOptions::create(
            $challenge,
            $relyingPartyId,
            $allowedPublicKeyCredentials,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            self::TIMEOUT,
        );
    }

    private function getPublicKeyCredential(string $json): PublicKeyCredential
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        Assert::isArray($data);

        Assert::keyExists($data, 'id');
        Assert::keyExists($data, 'rawId');
        Assert::stringNotEmpty($data['id']);
        Assert::stringNotEmpty($data['rawId']);
        $id = Base64::decodeUrlSafeNoPadding($data['id']);
        $rawId = Base64::decode($data['rawId']);
        Assert::true(hash_equals($id, $rawId));

        $type = 'public-key';
        Assert::keyExists($data, 'type');
        Assert::same($data['type'], $type);

        Assert::keyExists($data, 'response');
        Assert::isArray($data['response']);
        $response = $data['response'];

        Assert::keyExists($response, 'clientDataJSON');
        Assert::stringNotEmpty($response['clientDataJSON']);
        $clientDataJSON = Base64::encodeUrlSafeNoPadding(Base64::decode($response['clientDataJSON']));
        $clientData = CollectedClientData::createFormJson($clientDataJSON);

        if (array_key_exists('attestationObject', $response)) {
            Assert::stringNotEmpty($response['attestationObject']);
            $attestationStatementSupportManager = new AttestationStatementSupportManager();
            $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
            $attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager);
            $attestationObject = $attestationObjectLoader->load($response['attestationObject']);

            return PublicKeyCredential::create(
                null,
                $type,
                $rawId,
                AuthenticatorAttestationResponse::create($clientData, $attestationObject),
            );
        }

        if (array_key_exists('signature', $response)) {
            Assert::stringNotEmpty($response['signature']);
            $signature = Base64::decode($response['signature']);

            Assert::keyExists($response, 'authenticatorData');
            Assert::stringNotEmpty($response['authenticatorData']);
            $authDataLoader = AuthenticatorDataLoader::create();
            $authenticatorData = $authDataLoader->load(Base64::decode($response['authenticatorData']));

            return PublicKeyCredential::create(
                null,
                $type,
                $rawId,
                AuthenticatorAssertionResponse::create($clientData, $authenticatorData, $signature),
            );
        }

        throw new WebAuthnException('Unable to create the response object.');
    }

    /** @param array<mixed> $credential */
    private function getPublicKeyCredentialSource(array $credential): PublicKeyCredentialSource
    {
        Assert::keyExists($credential, 'publicKeyCredentialId');
        Assert::string($credential['publicKeyCredentialId']);
        Assert::keyExists($credential, 'type');
        Assert::string($credential['type']);
        Assert::keyExists($credential, 'transports');
        Assert::isArray($credential['transports']);
        Assert::allString($credential['transports']);
        Assert::keyExists($credential, 'attestationType');
        Assert::string($credential['attestationType']);
        Assert::keyExists($credential, 'aaguid');
        Assert::string($credential['aaguid']);
        Assert::true(mb_strlen($credential['aaguid'], '8bit') === 36);
        Assert::keyExists($credential, 'credentialPublicKey');
        Assert::string($credential['credentialPublicKey']);
        Assert::keyExists($credential, 'userHandle');
        Assert::string($credential['userHandle']);
        Assert::keyExists($credential, 'counter');
        Assert::integer($credential['counter']);

        return PublicKeyCredentialSource::create(
            Base64::decodeUrlSafeNoPadding($credential['publicKeyCredentialId']),
            $credential['type'],
            $credential['transports'],
            $credential['attestationType'],
            EmptyTrustPath::create(),
            Uuid::fromString($credential['aaguid']),
            Base64::decodeUrlSafeNoPadding($credential['credentialPublicKey']),
            Base64::decodeUrlSafeNoPadding($credential['userHandle']),
            $credential['counter'],
        );
    }
}
