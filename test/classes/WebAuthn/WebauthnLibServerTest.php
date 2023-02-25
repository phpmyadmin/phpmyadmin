<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\WebAuthn;

use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\WebAuthn\WebauthnLibServer;
use PHPUnit\Framework\TestCase;
use Webauthn\Server as WebauthnServer;

use function base64_encode;
use function class_exists;

/** @covers \PhpMyAdmin\WebAuthn\WebauthnLibServer */
class WebauthnLibServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(WebauthnServer::class)) {
            return;
        }

        $this->markTestSkipped('Package "web-auth/webauthn-lib" is required.');
    }

    public function testGetCredentialCreationOptions(): void
    {
        $server = new WebauthnLibServer($this->createStub(TwoFactor::class));
        $options = $server->getCredentialCreationOptions('user_name', 'user_id', 'test.localhost');
        $this->assertNotEmpty($options['challenge']);
        $this->assertNotEmpty($options['pubKeyCredParams']);
        $this->assertNotEmpty($options['attestation']);
        $this->assertSame('phpMyAdmin (test.localhost)', $options['rp']['name']);
        $this->assertSame('test.localhost', $options['rp']['id']);
        $this->assertSame('user_name', $options['user']['name']);
        $this->assertSame('user_name', $options['user']['displayName']);
        $this->assertSame(base64_encode('user_id'), $options['user']['id']);
        $this->assertArrayHasKey('authenticatorAttachment', $options['authenticatorSelection']);
        $this->assertSame('cross-platform', $options['authenticatorSelection']['authenticatorAttachment']);
    }

    public function testGetCredentialRequestOptions(): void
    {
        $twoFactor = $this->createStub(TwoFactor::class);
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
                        'trustPath' => ['type' => 'Webauthn\\TrustPath\\EmptyTrustPath'],
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
        $this->assertNotEmpty($options['challenge']);
        $this->assertSame('test.localhost', $options['rpId']);
        $this->assertEquals(
            [['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==']],
            $options['allowCredentials'],
        );
    }
}
