<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\WebAuthn;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\WebAuthn\WebauthnLibServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Webauthn\PublicKeyCredential;
use Webauthn\TrustPath\EmptyTrustPath;

use function class_exists;

#[CoversClass(WebauthnLibServer::class)]
final class WebauthnLibServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(PublicKeyCredential::class)) {
            return;
        }

        self::markTestSkipped('Package "web-auth/webauthn-lib" is required.');
    }

    public function testGetCredentialCreationOptions(): void
    {
        $server = new WebauthnLibServer(self::createStub(TwoFactor::class));
        $options = $server->getCredentialCreationOptions('user_name', 'user_id', 'test.localhost');
        self::assertArrayHasKey('challenge', $options);
        self::assertNotEmpty($options['challenge']);
        self::assertArrayHasKey('pubKeyCredParams', $options);
        self::assertNotEmpty($options['pubKeyCredParams']);
        self::assertArrayHasKey('attestation', $options);
        self::assertNotEmpty($options['attestation']);
        self::assertSame('phpMyAdmin (test.localhost)', $options['rp']['name']);
        self::assertSame('test.localhost', $options['rp']['id']);
        self::assertSame('user_name', $options['user']['name']);
        self::assertSame('user_name', $options['user']['displayName']);
        self::assertSame('dXNlcl9pZA', $options['user']['id']);
        self::assertArrayHasKey('authenticatorAttachment', $options['authenticatorSelection']);
        self::assertSame('cross-platform', $options['authenticatorSelection']['authenticatorAttachment']);
    }

    public function testGetCredentialRequestOptions(): void
    {
        $twoFactor = self::createStub(TwoFactor::class);
        $twoFactor->config = [
            'backend' => 'WebAuthn',
            'settings' => [
                'credentials' => [
                    // base64 of publicKeyCredentialId1
                    'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==' => [
                        // base64url for publicKeyCredentialId1
                        'publicKeyCredentialId' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ',
                        'type' => 'public-key',
                        'transports' => [],
                        'attestationType' => 'none',
                        'trustPath' => ['type' => EmptyTrustPath::class],
                        'aaguid' => '00000000-0000-0000-0000-000000000000',
                        'credentialPublicKey' => 'Y3JlZGVudGlhbFB1YmxpY0tleTE', // base64url for credentialPublicKey1
                        'userHandle' => 'dXNlckhhbmRsZTE=', // base64 for userHandle1
                        'counter' => 0,
                        'otherUI' => null,
                    ],
                ],
            ],
        ];

        $server = new WebauthnLibServer($twoFactor);
        $options = $server->getCredentialRequestOptions(
            'user_name',
            'userHandle1',
            'test.localhost',
            [['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==']],
        );
        self::assertNotEmpty($options['challenge']);
        self::assertSame('test.localhost', $options['rpId']);
        self::assertSame(
            [['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==']],
            $options['allowCredentials'],
        );
    }

    /** @see https://github.com/web-auth/webauthn-framework/blob/v3.3.12/tests/library/Functional/AssertionTest.php#L46 */
    #[RequiresPhpExtension('bcmath')]
    public function testParseAndValidateAssertionResponse(): void
    {
        $twoFactor = self::createStub(TwoFactor::class);
        $twoFactor->user = 'foo';
        $twoFactor->config = [
            'backend' => 'WebAuthn',
            'settings' => [
                'userHandle' => 'Zm9v',
                'credentials' => [
                    'eHouz/Zi7+BmByHjJ/tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp/B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB+w==' => [
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        'publicKeyCredentialId' => 'eHouz_Zi7-BmByHjJ_tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp_B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB-w',
                        'type' => 'public-key',
                        'transports' => [],
                        'attestationType' => 'none',
                        'aaguid' => '00000000-0000-0000-0000-000000000000',
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        'credentialPublicKey' => 'pQECAyYgASFYIJV56vRrFusoDf9hm3iDmllcxxXzzKyO9WruKw4kWx7zIlgg_nq63l8IMJcIdKDJcXRh9hoz0L-nVwP1Oxil3_oNQYs',
                        'userHandle' => 'Zm9v',
                        'counter' => 100,
                        'otherUI' => null,
                    ],
                ],
            ],
        ];

        $server = new WebauthnLibServer($twoFactor);

        $uriStub = self::createStub(UriInterface::class);
        $uriStub->method('getHost')->willReturn('localhost');
        $request = self::createStub(ServerRequest::class);
        $request->method('getUri')->willReturn($uriStub);

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $authenticatorResponse = '{"id":"eHouz_Zi7-BmByHjJ_tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp_B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB-w","type":"public-key","rawId":"eHouz/Zi7+BmByHjJ/tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp/B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB+w==","response":{"authenticatorData":"SZYN5YgOjGh0NBcPZHZgW4/krrmihjLHmVzzuoMdl2MBAAAAew==","clientDataJSON":"eyJjaGFsbGVuZ2UiOiJHMEpiTExuZGVmM2EwSXkzUzJzU1FBOHVPNFNPX3plNkZaTUF1UEk2LXhJIiwiY2xpZW50RXh0ZW5zaW9ucyI6e30sImhhc2hBbGdvcml0aG0iOiJTSEEtMjU2Iiwib3JpZ2luIjoiaHR0cHM6Ly9sb2NhbGhvc3Q6ODQ0MyIsInR5cGUiOiJ3ZWJhdXRobi5nZXQifQ==","signature":"MEUCIEY/vcNkbo/LdMTfLa24ZYLlMMVMRd8zXguHBvqud9AJAiEAwCwpZpvcMaqCrwv85w/8RGiZzE+gOM61ffxmgEDeyhM=","userHandle":null}}';
        $challenge = 'G0JbLLndef3a0Iy3S2sSQA8uO4SO/ze6FZMAuPI6+xI=';

        $allowedCredentials = [
            [
                'type' => 'public-key',
                'id' => 'eHouz_Zi7-BmByHjJ_tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp_B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB-w',
            ],
        ];

        $server->parseAndValidateAssertionResponse($authenticatorResponse, $allowedCredentials, $challenge, $request);

        /**
         * @psalm-suppress TypeDoesNotContainType
         * @phpstan-ignore-next-line
         */
        self::assertSame(
            [
                'eHouz/Zi7+BmByHjJ/tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp/B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB+w==' => [
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    'publicKeyCredentialId' => 'eHouz_Zi7-BmByHjJ_tx9h4a1WZsK4IzUmgGjkhyOodPGAyUqUp_B9yUkflXY3yHWsNtsrgCXQ3HjAIFUeZB-w',
                    'type' => 'public-key',
                    'transports' => [],
                    'attestationType' => 'none',
                    'trustPath' => ['type' => EmptyTrustPath::class],
                    'aaguid' => '00000000-0000-0000-0000-000000000000',
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    'credentialPublicKey' => 'pQECAyYgASFYIJV56vRrFusoDf9hm3iDmllcxxXzzKyO9WruKw4kWx7zIlgg_nq63l8IMJcIdKDJcXRh9hoz0L-nVwP1Oxil3_oNQYs',
                    'userHandle' => 'Zm9v',
                    'counter' => 123,
                    'backupEligible' => false,
                    'backupStatus' => false,
                ],
            ],
            $twoFactor->config['settings']['credentials'],
        );
    }

    /** @see https://github.com/web-auth/webauthn-framework/blob/v3.3.12/tests/library/Functional/NoneAttestationStatementTest.php#L45 */
    public function testParseAndValidateAttestationResponse(): void
    {
        $twoFactor = self::createStub(TwoFactor::class);
        $twoFactor->user = '';
        $twoFactor->config = ['backend' => 'WebAuthn', 'settings' => ['userHandle' => '', 'credentials' => []]];

        $uriStub = self::createStub(UriInterface::class);
        $uriStub->method('getHost')->willReturn('localhost');
        $request = self::createStub(ServerRequest::class);
        $request->method('getUri')->willReturn($uriStub);

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $options = '{"rp":{"name":"My Application"},"pubKeyCredParams":[{"type":"public-key","alg":-7}],"challenge":"9WqgpRIYvGMCUYiFT20o1U7hSD193k11zu4tKP7wRcrE26zs1zc4LHyPinvPGS86wu6bDvpwbt8Xp2bQ3VBRSQ==","attestation":"none","user":{"name":"test@foo.com","id":"MJr5sD0WitVwZM0eoSO6kWhyseT67vc3oQdk\/k1VdZQ=","displayName":"Test PublicKeyCredentialUserEntity"},"authenticatorSelection":{"requireResidentKey":false,"userVerification":"preferred"}}';
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $response = '{"id":"mMihuIx9LukswxBOMjMHDf6EAONOy7qdWhaQQ7dOtViR2cVB_MNbZxURi2cvgSvKSILb3mISe9lPNG9sYgojuY5iNinYOg6hRVxmm0VssuNG2pm1-RIuTF9DUtEJZEEK","type":"public-key","rawId":"mMihuIx9LukswxBOMjMHDf6EAONOy7qdWhaQQ7dOtViR2cVB/MNbZxURi2cvgSvKSILb3mISe9lPNG9sYgojuY5iNinYOg6hRVxmm0VssuNG2pm1+RIuTF9DUtEJZEEK","response":{"clientDataJSON":"eyJjaGFsbGVuZ2UiOiI5V3FncFJJWXZHTUNVWWlGVDIwbzFVN2hTRDE5M2sxMXp1NHRLUDd3UmNyRTI2enMxemM0TEh5UGludlBHUzg2d3U2YkR2cHdidDhYcDJiUTNWQlJTUSIsImNsaWVudEV4dGVuc2lvbnMiOnt9LCJoYXNoQWxnb3JpdGhtIjoiU0hBLTI1NiIsIm9yaWdpbiI6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMiLCJ0eXBlIjoid2ViYXV0aG4uY3JlYXRlIn0=","attestationObject":"o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YVjkSZYN5YgOjGh0NBcPZHZgW4/krrmihjLHmVzzuoMdl2NBAAAAAAAAAAAAAAAAAAAAAAAAAAAAYJjIobiMfS7pLMMQTjIzBw3+hADjTsu6nVoWkEO3TrVYkdnFQfzDW2cVEYtnL4ErykiC295iEnvZTzRvbGIKI7mOYjYp2DoOoUVcZptFbLLjRtqZtfkSLkxfQ1LRCWRBCqUBAgMmIAEhWCAcPxwKyHADVjTgTsat4R/Jax6PWte50A8ZasMm4w6RxCJYILt0FCiGwC6rBrh3ySNy0yiUjZpNGAhW+aM9YYyYnUTJ"}}';

        $server = new WebauthnLibServer($twoFactor);
        $credential = $server->parseAndValidateAttestationResponse($response, $options, $request);

        self::assertSame(
            [
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'publicKeyCredentialId' => 'mMihuIx9LukswxBOMjMHDf6EAONOy7qdWhaQQ7dOtViR2cVB_MNbZxURi2cvgSvKSILb3mISe9lPNG9sYgojuY5iNinYOg6hRVxmm0VssuNG2pm1-RIuTF9DUtEJZEEK',
                'type' => 'public-key',
                'transports' => [],
                'attestationType' => 'none',
                'trustPath' => ['type' => EmptyTrustPath::class],
                'aaguid' => '00000000-0000-0000-0000-000000000000',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'credentialPublicKey' => 'pQECAyYgASFYIBw_HArIcANWNOBOxq3hH8lrHo9a17nQDxlqwybjDpHEIlggu3QUKIbALqsGuHfJI3LTKJSNmk0YCFb5oz1hjJidRMk',
                'userHandle' => 'MJr5sD0WitVwZM0eoSO6kWhyseT67vc3oQdk_k1VdZQ',
                'counter' => 0,
                'backupEligible' => false,
                'backupStatus' => false,
                'uvInitialized' => false,
            ],
            $credential,
        );
    }
}
