<?php

declare(strict_types=1);

namespace PhpMyAdmin\WebAuthn;

use Cose\Algorithm\ManagerFactory;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use PhpMyAdmin\TwoFactor;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
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
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;
use Webauthn\TrustPath\EmptyTrustPath;
use Webmozart\Assert\Assert;

use function array_map;
use function base64_encode;
use function json_decode;
use function json_encode;
use function random_bytes;
use function rtrim;
use function sodium_base642bin;
use function sodium_bin2base64;
use function str_ends_with;

use const JSON_THROW_ON_ERROR;
use const SODIUM_BASE64_VARIANT_ORIGINAL;
use const SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

final class WebauthnLibServer implements Server
{
    private const TIMEOUT = 60000;
    private const CHALLENGE_SIZE = 32;

    private ManagerFactory $coseAlgorithmManagerFactory;

    /** @var string[] */
    private array $selectedAlgorithms;

    public function __construct(private TwoFactor $twofactor)
    {
        $this->coseAlgorithmManagerFactory = new ManagerFactory();
        $this->coseAlgorithmManagerFactory->add('RS1', new RSA\RS1());
        $this->coseAlgorithmManagerFactory->add('RS256', new RSA\RS256());
        $this->coseAlgorithmManagerFactory->add('RS384', new RSA\RS384());
        $this->coseAlgorithmManagerFactory->add('RS512', new RSA\RS512());
        $this->coseAlgorithmManagerFactory->add('PS256', new RSA\PS256());
        $this->coseAlgorithmManagerFactory->add('PS384', new RSA\PS384());
        $this->coseAlgorithmManagerFactory->add('PS512', new RSA\PS512());
        $this->coseAlgorithmManagerFactory->add('ES256', new ECDSA\ES256());
        $this->coseAlgorithmManagerFactory->add('ES256K', new ECDSA\ES256K());
        $this->coseAlgorithmManagerFactory->add('ES384', new ECDSA\ES384());
        $this->coseAlgorithmManagerFactory->add('ES512', new ECDSA\ES512());
        $this->coseAlgorithmManagerFactory->add('Ed25519', new EdDSA\Ed25519());

        $this->selectedAlgorithms = ['RS256', 'RS512', 'PS256', 'PS512', 'ES256', 'ES512', 'Ed25519'];
    }

    /** @inheritDoc */
    public function getCredentialCreationOptions(string $userName, string $userId, string $relyingPartyId): array
    {
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $relyingPartyId . ')', $relyingPartyId);

        $coseAlgorithmManager = $this->coseAlgorithmManagerFactory->generate(...$this->selectedAlgorithms);
        $publicKeyCredentialParametersList = [];
        foreach ($coseAlgorithmManager->all() as $algorithm) {
            $publicKeyCredentialParametersList[] = new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm::identifier(),
            );
        }

        $criteria = AuthenticatorSelectionCriteria::createFromArray([
            'authenticatorAttachment' => 'cross-platform',
            'userVerification' => 'discouraged',
        ]);
        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::create(
            $relyingPartyEntity,
            $userEntity,
            random_bytes(self::CHALLENGE_SIZE),
            $publicKeyCredentialParametersList,
            $criteria,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            [],
            self::TIMEOUT,
            new AuthenticationExtensions(),
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
        $creationOptions['challenge'] = sodium_bin2base64(
            sodium_base642bin($creationOptions['challenge'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            SODIUM_BASE64_VARIANT_ORIGINAL,
        );
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
        $userEntity = new PublicKeyCredentialUserEntity($userName, $userId, $userName);
        $relyingPartyEntity = new PublicKeyCredentialRpEntity('phpMyAdmin (' . $relyingPartyId . ')', $relyingPartyId);
        $publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $credentialSources = $publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
        $allowedCredentials = array_map(
            static fn (
                PublicKeyCredentialSource $credential,
            ): PublicKeyCredentialDescriptor => $credential->getPublicKeyCredentialDescriptor(),
            $credentialSources,
        );

        $challenge = random_bytes(self::CHALLENGE_SIZE);
        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::create(
            $challenge,
            $relyingPartyEntity->id,
            $allowedCredentials,
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
            self::TIMEOUT,
            new AuthenticationExtensions(),
        );

        /**
         * @psalm-var array{
         *   challenge: string,
         *   allowCredentials?: list<array{id: non-empty-string, type: non-empty-string}>
         * } $requestOptions
         */
        $requestOptions = $this->normalize($publicKeyCredentialRequestOptions);
        $requestOptions['challenge'] = sodium_bin2base64(
            sodium_base642bin($requestOptions['challenge'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            SODIUM_BASE64_VARIANT_ORIGINAL,
        );
        if (isset($requestOptions['allowCredentials'])) {
            foreach ($requestOptions['allowCredentials'] as $key => $credential) {
                $requestOptions['allowCredentials'][$key]['id'] = sodium_bin2base64(
                    sodium_base642bin($credential['id'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                    SODIUM_BASE64_VARIANT_ORIGINAL,
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
        $userHandle = sodium_base642bin(
            $this->twofactor->config['settings']['userHandle'],
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );
        $userEntity = new PublicKeyCredentialUserEntity($this->twofactor->user, $userHandle, $this->twofactor->user);
        $host = $request->getUri()->getHost();
        $publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $requestOptions = PublicKeyCredentialRequestOptions::createFromArray([
            'challenge' => $challenge,
            'allowCredentials' => $allowedCredentials,
            'rpId' => $host,
            'timeout' => 60000,
        ]);
        Assert::isInstanceOf($requestOptions, PublicKeyCredentialRequestOptions::class);

        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager);
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader);

        $assertionResponseDecoded = json_decode($assertionResponseJson, true, flags: JSON_THROW_ON_ERROR);
        $assertionResponseDecoded['response']['authenticatorData'] = sodium_bin2base64(
            sodium_base642bin(
                $assertionResponseDecoded['response']['authenticatorData'],
                SODIUM_BASE64_VARIANT_ORIGINAL,
            ),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );
        $assertionResponseDecoded['response']['clientDataJSON'] = sodium_bin2base64(
            sodium_base642bin(
                $assertionResponseDecoded['response']['clientDataJSON'],
                SODIUM_BASE64_VARIANT_ORIGINAL,
            ),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );

        $publicKeyCredential = $publicKeyCredentialLoader->load(
            json_encode($assertionResponseDecoded, JSON_THROW_ON_ERROR),
        );
        $authenticatorResponse = $publicKeyCredential->response;
        Assert::isInstanceOf(
            $authenticatorResponse,
            AuthenticatorAssertionResponse::class,
            'Not an authenticator assertion response',
        );

        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            $publicKeyCredentialSourceRepository,
            new IgnoreTokenBindingHandler(),
            new ExtensionOutputCheckerHandler(),
            $this->coseAlgorithmManagerFactory->generate(...$this->selectedAlgorithms),
        );

        $authenticatorAssertionResponseValidator->check(
            $publicKeyCredential->rawId,
            $authenticatorResponse,
            $requestOptions,
            $request,
            $userEntity->id,
        );
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
        Assert::keyExists($creationOptions, 'user');
        Assert::isArray($creationOptions['user']);
        Assert::keyExists($creationOptions['user'], 'id');
        $host = $request->getUri()->getHost();
        $publicKeyCredentialSourceRepository = $this->createPublicKeyCredentialSourceRepository();
        $creationOptionsArray = [
            'rp' => ['name' => 'phpMyAdmin (' . $host . ')', 'id' => $host],
            'pubKeyCredParams' => [
                ['alg' => -257, 'type' => 'public-key'], // RS256
                ['alg' => -259, 'type' => 'public-key'], // RS512
                ['alg' => -37, 'type' => 'public-key'], // PS256
                ['alg' => -39, 'type' => 'public-key'], // PS512
                ['alg' => -7, 'type' => 'public-key'], // ES256
                ['alg' => -36, 'type' => 'public-key'], // ES512
                ['alg' => -8, 'type' => 'public-key'], // EdDSA
            ],
            'challenge' => $creationOptions['challenge'],
            'attestation' => 'none',
            'user' => [
                'name' => $this->twofactor->user,
                'id' => $creationOptions['user']['id'],
                'displayName' => $this->twofactor->user,
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'cross-platform',
                'userVerification' => 'discouraged',
            ],
            'timeout' => 60000,
        ];
        $credentialCreationOptions = PublicKeyCredentialCreationOptions::createFromArray($creationOptionsArray);
        Assert::isInstanceOf($credentialCreationOptions, PublicKeyCredentialCreationOptions::class);

        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationObjectLoader = AttestationObjectLoader::create($attestationStatementSupportManager);
        $publicKeyCredentialLoader = PublicKeyCredentialLoader::create($attestationObjectLoader);

        $attestationResponseDecoded = json_decode($attestationResponse, true, flags: JSON_THROW_ON_ERROR);
        $attestationResponseDecoded['response']['clientDataJSON'] = sodium_bin2base64(
            sodium_base642bin(
                $attestationResponseDecoded['response']['clientDataJSON'],
                SODIUM_BASE64_VARIANT_ORIGINAL,
            ),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );

        $publicKeyCredential = $publicKeyCredentialLoader->load(
            json_encode($attestationResponseDecoded, JSON_THROW_ON_ERROR),
        );
        $authenticatorResponse = $publicKeyCredential->response;
        Assert::isInstanceOf(
            $authenticatorResponse,
            AuthenticatorAttestationResponse::class,
            'Not an authenticator attestation response',
        );

        $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $publicKeyCredentialSourceRepository,
            new IgnoreTokenBindingHandler(),
            new ExtensionOutputCheckerHandler(),
        );

        $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
            $authenticatorResponse,
            $credentialCreationOptions,
            $request,
        );

        return $this->normalize($publicKeyCredentialSource);
    }

    /** @infection-ignore-all */
    private function createPublicKeyCredentialSourceRepository(): PublicKeyCredentialSourceRepository
    {
        return new class ($this->twofactor) implements PublicKeyCredentialSourceRepository {
            public function __construct(private TwoFactor $twoFactor)
            {
            }

            public function findOneByCredentialId(string $publicKeyCredentialId): PublicKeyCredentialSource|null
            {
                $data = $this->read();
                if (isset($data[base64_encode($publicKeyCredentialId)])) {
                    return PublicKeyCredentialSource::createFromArray($data[base64_encode($publicKeyCredentialId)]);
                }

                return null;
            }

            /** @return PublicKeyCredentialSource[] */
            public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
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

            public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
            {
                $data = $this->read();
                $id = $publicKeyCredentialSource->publicKeyCredentialId;
                $encoded = json_encode($publicKeyCredentialSource, JSON_THROW_ON_ERROR);
                $normalized = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
                Assert::isArray($normalized);
                $data[base64_encode($id)] = $normalized;
                $this->write($data);
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

            /** @param mixed[] $data */
            private function write(array $data): void
            {
                $this->twoFactor->config['settings']['credentials'] = $data;
            }
        };
    }

    /** @return mixed[] */
    private function normalize(object $object): array
    {
        $encoded = json_encode($object, JSON_THROW_ON_ERROR);
        $normalized = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
        Assert::isArray($normalized);

        return $normalized;
    }
}
