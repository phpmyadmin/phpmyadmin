<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\TwoFactor;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\TwoFactor\WebAuthn;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\WebAuthn\Server;
use PhpMyAdmin\WebAuthn\WebAuthnException;
use Psr\Http\Message\UriInterface;

use function array_column;
use function json_decode;

/**
 * @covers \PhpMyAdmin\Plugins\TwoFactor\WebAuthn
 * @covers \PhpMyAdmin\Plugins\TwoFactorPlugin
 * @covers \PhpMyAdmin\WebAuthn\WebAuthnException
 */
class WebAuthnTest extends AbstractTestCase
{
    public function testIdNameAndDescription(): void
    {
        $this->assertSame('WebAuthn', WebAuthn::$id);
        $this->assertSame('Hardware Security Key (WebAuthn/FIDO2)', WebAuthn::getName());
        $this->assertSame(
            'Provides authentication using hardware security tokens supporting the WebAuthn/FIDO2 protocol,'
            . ' such as a YubiKey.',
            WebAuthn::getDescription(),
        );
    }

    public function testRender(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $uri = $this->createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('test.localhost');
        $request = $this->createStub(ServerRequest::class);
        $request->method('getUri')->willReturn($uri);

        $twoFactor = $this->createStub(TwoFactor::class);
        $twoFactor->user = 'test_user';
        $twoFactor->config = [
            'backend' => 'WebAuthn',
            'settings' => [
                'credentials' => [
                    // base64 of publicKeyCredentialId1
                    'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==' => [
                        // base64url of publicKeyCredentialId1
                        'publicKeyCredentialId' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ',
                        'type' => 'public-key',
                    ],
                    // base64 of publicKeyCredentialId2
                    'cHVibGljS2V5Q3JlZGVudGlhbElkMg==' => ['publicKeyCredentialId' => '', 'type' => ''],
                ],
            ],
        ];

        $expectedRequestOptions = [
            'challenge' => 'challenge',
            'allowCredentials' => [['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ']],
            'timeout' => 60000,
        ];
        $server = $this->createMock(Server::class);
        $server->expects($this->once())->method('getCredentialRequestOptions')->with(
            $this->equalTo('test_user'),
            $this->anything(),
            $this->equalTo('test.localhost'),
            $this->equalTo([['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ']]),
        )->willReturn($expectedRequestOptions);

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        $webAuthn->serverRequest = $request;
        $actual = $webAuthn->render();

        $optionsFromSession = $_SESSION['WebAuthnCredentialRequestOptions'] ?? null;
        $this->assertIsString($optionsFromSession);
        $this->assertJson($optionsFromSession);
        $this->assertEquals($expectedRequestOptions, json_decode($optionsFromSession, true));

        $this->assertStringContainsString('id="webauthn_request_response"', $actual);
        $this->assertStringContainsString('name="webauthn_request_response"', $actual);
        $this->assertStringContainsString('value=""', $actual);
        $this->assertStringContainsString('data-request-options="', $actual);
        $this->assertSame('', $webAuthn->getError());

        $files = ResponseRenderer::getInstance()->getHeader()->getScripts()->getFiles();
        $this->assertContains('webauthn.js', array_column($files, 'name'));
    }

    public function testSetup(): void
    {
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $uri = $this->createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('test.localhost');
        $request = $this->createStub(ServerRequest::class);
        $request->method('getUri')->willReturn($uri);

        $twoFactor = $this->createStub(TwoFactor::class);
        $twoFactor->user = 'test_user';

        $expectedCreationOptions = [
            'challenge' => 'challenge',
            'rp' => ['name' => 'phpMyAdmin (test.localhost)', 'id' => 'test.localhost'],
            'user' => ['id' => 'user_id', 'name' => 'test_user', 'displayName' => 'test_user'],
            'pubKeyCredParams' => [['alg' => -8, 'type' => 'public-key']],
            'authenticatorSelection' => ['authenticatorAttachment' => 'cross-platform'],
            'timeout' => 60000,
            'attestation' => 'none',
        ];
        $server = $this->createMock(Server::class);
        $server->expects($this->once())->method('getCredentialCreationOptions')->with(
            $this->equalTo('test_user'),
            $this->anything(),
            $this->equalTo('test.localhost'),
        )->willReturn($expectedCreationOptions);

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        $webAuthn->serverRequest = $request;
        $actual = $webAuthn->setup();

        $optionsFromSession = $_SESSION['WebAuthnCredentialCreationOptions'] ?? null;
        $this->assertIsString($optionsFromSession);
        $this->assertJson($optionsFromSession);
        $this->assertEquals($expectedCreationOptions, json_decode($optionsFromSession, true));

        $this->assertStringContainsString('id="webauthn_creation_response"', $actual);
        $this->assertStringContainsString('name="webauthn_creation_response"', $actual);
        $this->assertStringContainsString('value=""', $actual);
        $this->assertStringContainsString('data-creation-options="', $actual);
        $this->assertSame('', $webAuthn->getError());

        $files = ResponseRenderer::getInstance()->getHeader()->getScripts()->getFiles();
        $this->assertContains('webauthn.js', array_column($files, 'name'));
    }

    public function testConfigure(): void
    {
        $_SESSION = [];
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '']]);
        $webAuthn = new WebAuthn($this->createStub(TwoFactor::class));
        $webAuthn->serverRequest = $request;
        $this->assertFalse($webAuthn->configure());
        $this->assertSame('', $webAuthn->getError());
    }

    public function testConfigure2(): void
    {
        $_SESSION['WebAuthnCredentialCreationOptions'] = '';
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '{}']]);
        $webAuthn = new WebAuthn($this->createStub(TwoFactor::class));
        $webAuthn->serverRequest = $request;
        $this->assertFalse($webAuthn->configure());
        $this->assertStringContainsString('Two-factor authentication failed:', $webAuthn->getError());
    }

    public function testConfigure3(): void
    {
        $_SESSION['WebAuthnCredentialCreationOptions'] = '{}';
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '{}']]);

        $server = $this->createMock(Server::class);
        $server->expects($this->once())->method('parseAndValidateAttestationResponse')
            ->willThrowException(new WebAuthnException());

        $webAuthn = new WebAuthn($this->createStub(TwoFactor::class));
        $webAuthn->setServer($server);
        $webAuthn->serverRequest = $request;
        $this->assertFalse($webAuthn->configure());
        $this->assertStringContainsString('Two-factor authentication failed.', $webAuthn->getError());
    }

    public function testConfigure4(): void
    {
        $_SESSION['WebAuthnCredentialCreationOptions'] = '{}';
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '{}']]);

        $twoFactor = $this->createStub(TwoFactor::class);
        $twoFactor->config = ['backend' => '', 'settings' => []];

        // base64url of publicKeyCredentialId1
        $credential = ['publicKeyCredentialId' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ', 'userHandle' => 'userHandle'];
        $server = $this->createMock(Server::class);
        $server->expects($this->once())->method('parseAndValidateAttestationResponse')->with(
            $this->equalTo('{}'),
            $this->equalTo('{}'),
            $this->equalTo($request),
        )->willReturn($credential);

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        $webAuthn->serverRequest = $request;
        $this->assertTrue($webAuthn->configure());
        /** @psalm-var array{backend: string, settings: mixed[]} $config */
        $config = $twoFactor->config;
        $this->assertSame(
            [
                'backend' => '',
                'settings' => [
                    'userHandle' => 'userHandle',
                    'credentials' => ['cHVibGljS2V5Q3JlZGVudGlhbElkMQ==' => $credential],
                ],
            ],
            $config,
        );
    }

    public function testCheck(): void
    {
        $_SESSION = [];
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '']]);
        $webAuthn = new WebAuthn($this->createStub(TwoFactor::class));
        $webAuthn->serverRequest = $request;
        $this->assertFalse($webAuthn->check());
        $this->assertSame('', $webAuthn->getError());
    }

    public function testCheck2(): void
    {
        $_SESSION['WebAuthnCredentialRequestOptions'] = '';
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '{}']]);
        $webAuthn = new WebAuthn($this->createStub(TwoFactor::class));
        $webAuthn->serverRequest = $request;
        $this->assertFalse($webAuthn->check());
        $this->assertStringContainsString('Two-factor authentication failed:', $webAuthn->getError());
    }

    public function testCheck3(): void
    {
        $_SESSION['WebAuthnCredentialRequestOptions'] = '{"challenge":"challenge"}';
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '{}']]);

        $server = $this->createMock(Server::class);
        $server->expects($this->once())->method('parseAndValidateAssertionResponse')
            ->willThrowException(new WebAuthnException());

        $webAuthn = new WebAuthn($this->createStub(TwoFactor::class));
        $webAuthn->setServer($server);
        $webAuthn->serverRequest = $request;
        $this->assertFalse($webAuthn->check());
        $this->assertStringContainsString('Two-factor authentication failed.', $webAuthn->getError());
    }

    public function testCheck4(): void
    {
        $_SESSION['WebAuthnCredentialRequestOptions'] = '{"challenge":"challenge"}';
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '{}']]);

        $twoFactor = $this->createStub(TwoFactor::class);
        $twoFactor->config = [
            'backend' => 'WebAuthn',
            'settings' => [
                'credentials' => [
                    // base64 of publicKeyCredentialId1
                    'cHVibGljS2V5Q3JlZGVudGlhbElkMQ==' => [
                        // base64url of publicKeyCredentialId1
                        'publicKeyCredentialId' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ',
                        'type' => 'public-key',
                    ],
                ],
            ],
        ];

        $server = $this->createMock(Server::class);
        $server->expects($this->once())->method('parseAndValidateAssertionResponse')->with(
            $this->equalTo('{}'),
            $this->equalTo([['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ']]),
            $this->equalTo('challenge'),
            $this->equalTo($request),
        );

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        $webAuthn->serverRequest = $request;
        $this->assertTrue($webAuthn->check());
    }
}
