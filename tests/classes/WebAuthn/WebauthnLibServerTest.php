<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\WebAuthn;

use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\WebAuthn\WebauthnLibServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Webauthn\Server as WebauthnServer;
use Webauthn\TrustPath\EmptyTrustPath;

use function base64_encode;
use function class_exists;

#[CoversClass(WebauthnLibServer::class)]
class WebauthnLibServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(WebauthnServer::class)) {
            return;
        }

        self::markTestSkipped('Package "web-auth/webauthn-lib" is required.');
    }

    public function testGetCredentialCreationOptions(): void
    {
        $server = new WebauthnLibServer(self::createStub(TwoFactor::class));
        $options = $server->getCredentialCreationOptions('user_name', 'user_id', 'test.localhost');
        self::assertNotEmpty($options['challenge']);
        self::assertNotEmpty($options['pubKeyCredParams']);
        self::assertNotEmpty($options['attestation']);
        self::assertSame('phpMyAdmin (test.localhost)', $options['rp']['name']);
        self::assertSame('test.localhost', $options['rp']['id']);
        self::assertSame('user_name', $options['user']['name']);
        self::assertSame('user_name', $options['user']['displayName']);
        self::assertSame(base64_encode('user_id'), $options['user']['id']);
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
        $options = $server->getCredentialRequestOptions('user_name', 'userHandle1', 'test.localhost', []);
        self::assertNotEmpty($options['challenge']);
        self::assertSame('test.localhost', $options['rpId']);
        self::assertSame(
            [['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==']],
            $options['allowCredentials'],
        );
    }
}
