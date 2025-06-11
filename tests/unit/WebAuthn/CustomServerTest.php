<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\WebAuthn;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\WebAuthn\CBORDecoder;
use PhpMyAdmin\WebAuthn\CustomServer;
use PhpMyAdmin\WebAuthn\DataStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

use function hex2bin;

#[CoversClass(CustomServer::class)]
#[CoversClass(CBORDecoder::class)]
#[CoversClass(DataStream::class)]
final class CustomServerTest extends TestCase
{
    public function testGetCredentialCreationOptions(): void
    {
        $server = new CustomServer();
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
        self::assertSame('user_id', $options['user']['id']);
        self::assertArrayHasKey('authenticatorAttachment', $options['authenticatorSelection']);
        self::assertSame('cross-platform', $options['authenticatorSelection']['authenticatorAttachment']);
    }

    public function testGetCredentialRequestOptions(): void
    {
        $server = new CustomServer();
        $options = $server->getCredentialRequestOptions(
            'user_name',
            'userHandle1',
            'test.localhost',
            [['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ']],
        );
        self::assertNotEmpty($options['challenge']);
        self::assertSame(
            [['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==']],
            $options['allowCredentials'],
        );
        self::assertSame(60000, $options['timeout']);
        self::assertSame('none', $options['attestation']);
        self::assertSame('discouraged', $options['userVerification']);
    }

    /** @see https://github.com/web-auth/webauthn-framework/blob/v3.3.12/tests/library/Functional/AssertionTest.php#L46 */
    #[DoesNotPerformAssertions]
    public function testParseAndValidateAssertionResponse(): void
    {
        $server = new CustomServer();

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
    }

    /** @see https://github.com/web-auth/webauthn-framework/blob/v3.3.12/tests/library/Functional/NoneAttestationStatementTest.php#L45 */
    public function testParseAndValidateAttestationResponse(): void
    {
        $uriStub = self::createStub(UriInterface::class);
        $uriStub->method('getHost')->willReturn('localhost');
        $request = self::createStub(ServerRequest::class);
        $request->method('getUri')->willReturn($uriStub);

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $options = '{"rp":{"name":"My Application"},"pubKeyCredParams":[{"type":"public-key","alg":-7}],"challenge":"9WqgpRIYvGMCUYiFT20o1U7hSD193k11zu4tKP7wRcrE26zs1zc4LHyPinvPGS86wu6bDvpwbt8Xp2bQ3VBRSQ==","attestation":"none","user":{"name":"test@foo.com","id":"MJr5sD0WitVwZM0eoSO6kWhyseT67vc3oQdk\/k1VdZQ=","displayName":"Test PublicKeyCredentialUserEntity"},"authenticatorSelection":{"requireResidentKey":false,"userVerification":"preferred"}}';
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $response = '{"id":"mMihuIx9LukswxBOMjMHDf6EAONOy7qdWhaQQ7dOtViR2cVB_MNbZxURi2cvgSvKSILb3mISe9lPNG9sYgojuY5iNinYOg6hRVxmm0VssuNG2pm1-RIuTF9DUtEJZEEK","type":"public-key","rawId":"mMihuIx9LukswxBOMjMHDf6EAONOy7qdWhaQQ7dOtViR2cVB/MNbZxURi2cvgSvKSILb3mISe9lPNG9sYgojuY5iNinYOg6hRVxmm0VssuNG2pm1+RIuTF9DUtEJZEEK","response":{"clientDataJSON":"eyJjaGFsbGVuZ2UiOiI5V3FncFJJWXZHTUNVWWlGVDIwbzFVN2hTRDE5M2sxMXp1NHRLUDd3UmNyRTI2enMxemM0TEh5UGludlBHUzg2d3U2YkR2cHdidDhYcDJiUTNWQlJTUSIsImNsaWVudEV4dGVuc2lvbnMiOnt9LCJoYXNoQWxnb3JpdGhtIjoiU0hBLTI1NiIsIm9yaWdpbiI6Imh0dHBzOi8vbG9jYWxob3N0Ojg0NDMiLCJ0eXBlIjoid2ViYXV0aG4uY3JlYXRlIn0=","attestationObject":"o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YVjkSZYN5YgOjGh0NBcPZHZgW4/krrmihjLHmVzzuoMdl2NBAAAAAAAAAAAAAAAAAAAAAAAAAAAAYJjIobiMfS7pLMMQTjIzBw3+hADjTsu6nVoWkEO3TrVYkdnFQfzDW2cVEYtnL4ErykiC295iEnvZTzRvbGIKI7mOYjYp2DoOoUVcZptFbLLjRtqZtfkSLkxfQ1LRCWRBCqUBAgMmIAEhWCAcPxwKyHADVjTgTsat4R/Jax6PWte50A8ZasMm4w6RxCJYILt0FCiGwC6rBrh3ySNy0yiUjZpNGAhW+aM9YYyYnUTJ"}}';

        $server = new CustomServer();
        $credential = $server->parseAndValidateAttestationResponse($response, $options, $request);

        self::assertSame(
            [
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'publicKeyCredentialId' => 'mMihuIx9LukswxBOMjMHDf6EAONOy7qdWhaQQ7dOtViR2cVB_MNbZxURi2cvgSvKSILb3mISe9lPNG9sYgojuY5iNinYOg6hRVxmm0VssuNG2pm1-RIuTF9DUtEJZEEK',
                'type' => 'public-key',
                'transports' => [],
                'attestationType' => 'none',
                'aaguid' => hex2bin('00000000000000000000000000000000'),
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'credentialPublicKey' => 'pQECAyYgASFYIBw_HArIcANWNOBOxq3hH8lrHo9a17nQDxlqwybjDpHEIlggu3QUKIbALqsGuHfJI3LTKJSNmk0YCFb5oz1hjJidRMk',
                'userHandle' => 'MJr5sD0WitVwZM0eoSO6kWhyseT67vc3oQdk_k1VdZQ',
                'counter' => 0,
            ],
            $credential,
        );
    }
}
