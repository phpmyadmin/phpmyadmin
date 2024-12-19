<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\TwoFactor;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\TwoFactor\WebAuthn;
use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\WebAuthn\Server;
use PhpMyAdmin\WebAuthn\WebAuthnException;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\UriInterface;
use ReflectionProperty;

use function array_column;
use function json_decode;

#[CoversClass(WebAuthn::class)]
#[CoversClass(TwoFactorPlugin::class)]
#[CoversClass(WebAuthnException::class)]
class WebAuthnTest extends AbstractTestCase
{
    public function testIdNameAndDescription(): void
    {
        self::assertSame('WebAuthn', WebAuthn::$id);
        self::assertSame('Hardware Security Key (WebAuthn/FIDO2)', WebAuthn::getName());
        self::assertSame(
            'Provides authentication using hardware security tokens supporting the WebAuthn/FIDO2 protocol,'
            . ' such as a YubiKey.',
            WebAuthn::getDescription(),
        );
    }

    #[BackupStaticProperties(true)]
    public function testRender(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);

        $GLOBALS['lang'] = 'en';
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $uri = self::createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('test.localhost');
        $request = self::createStub(ServerRequest::class);
        $request->method('getUri')->willReturn($uri);

        $twoFactor = self::createStub(TwoFactor::class);
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
        $server = self::createMock(Server::class);
        $server->expects(self::once())->method('getCredentialRequestOptions')->with(
            self::equalTo('test_user'),
            self::anything(),
            self::equalTo('test.localhost'),
            self::equalTo([['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ']]),
        )->willReturn($expectedRequestOptions);

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        $actual = $webAuthn->render($request);

        $optionsFromSession = $_SESSION['WebAuthnCredentialRequestOptions'] ?? null;
        self::assertIsString($optionsFromSession);
        self::assertJson($optionsFromSession);
        self::assertSame($expectedRequestOptions, json_decode($optionsFromSession, true));

        self::assertStringContainsString('id="webauthn_request_response"', $actual);
        self::assertStringContainsString('name="webauthn_request_response"', $actual);
        self::assertStringContainsString('value=""', $actual);
        self::assertStringContainsString('data-request-options="', $actual);
        self::assertSame('', $webAuthn->getError());

        $files = ResponseRenderer::getInstance()->getHeader()->getScripts()->getFiles();
        self::assertContains('webauthn.js', array_column($files, 'name'));
    }

    #[BackupStaticProperties(true)]
    public function testSetup(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);

        $GLOBALS['lang'] = 'en';
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $uri = self::createStub(UriInterface::class);
        $uri->method('getHost')->willReturn('test.localhost');
        $request = self::createStub(ServerRequest::class);
        $request->method('getUri')->willReturn($uri);

        $twoFactor = self::createStub(TwoFactor::class);
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
        $server = self::createMock(Server::class);
        $server->expects(self::once())->method('getCredentialCreationOptions')->with(
            self::equalTo('test_user'),
            self::anything(),
            self::equalTo('test.localhost'),
        )->willReturn($expectedCreationOptions);

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        $actual = $webAuthn->setup($request);

        $optionsFromSession = $_SESSION['WebAuthnCredentialCreationOptions'] ?? null;
        self::assertIsString($optionsFromSession);
        self::assertJson($optionsFromSession);
        self::assertSame($expectedCreationOptions, json_decode($optionsFromSession, true));

        self::assertStringContainsString('id="webauthn_creation_response"', $actual);
        self::assertStringContainsString('name="webauthn_creation_response"', $actual);
        self::assertStringContainsString('value=""', $actual);
        self::assertStringContainsString('data-creation-options="', $actual);
        self::assertSame('', $webAuthn->getError());

        $files = ResponseRenderer::getInstance()->getHeader()->getScripts()->getFiles();
        self::assertContains('webauthn.js', array_column($files, 'name'));
    }

    public function testConfigure(): void
    {
        $_SESSION = [];
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '']]);
        $webAuthn = new WebAuthn(self::createStub(TwoFactor::class));
        self::assertFalse($webAuthn->configure($request));
        self::assertSame('', $webAuthn->getError());
    }

    public function testConfigure2(): void
    {
        $_SESSION['WebAuthnCredentialCreationOptions'] = '';
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '{}']]);
        $webAuthn = new WebAuthn(self::createStub(TwoFactor::class));
        self::assertFalse($webAuthn->configure($request));
        self::assertStringContainsString('Two-factor authentication failed:', $webAuthn->getError());
    }

    public function testConfigure3(): void
    {
        $_SESSION['WebAuthnCredentialCreationOptions'] = '{}';
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '{}']]);

        $server = self::createMock(Server::class);
        $server->expects(self::once())->method('parseAndValidateAttestationResponse')
            ->willThrowException(new WebAuthnException());

        $webAuthn = new WebAuthn(self::createStub(TwoFactor::class));
        $webAuthn->setServer($server);
        self::assertFalse($webAuthn->configure($request));
        self::assertStringContainsString('Two-factor authentication failed.', $webAuthn->getError());
    }

    public function testConfigure4(): void
    {
        $_SESSION['WebAuthnCredentialCreationOptions'] = '{}';
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_creation_response', '', '{}']]);

        $twoFactor = self::createStub(TwoFactor::class);
        $twoFactor->config = ['backend' => '', 'settings' => []];

        // base64url of publicKeyCredentialId1
        $credential = ['publicKeyCredentialId' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ', 'userHandle' => 'userHandle'];
        $server = self::createMock(Server::class);
        $server->expects(self::once())->method('parseAndValidateAttestationResponse')->with(
            self::equalTo('{}'),
            self::equalTo('{}'),
            self::equalTo($request),
        )->willReturn($credential);

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        self::assertTrue($webAuthn->configure($request));
        /** @psalm-var array{backend: string, settings: mixed[]} $config */
        $config = $twoFactor->config;
        self::assertSame(
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
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '']]);
        $webAuthn = new WebAuthn(self::createStub(TwoFactor::class));
        self::assertFalse($webAuthn->check($request));
        self::assertSame('', $webAuthn->getError());
    }

    public function testCheck2(): void
    {
        $_SESSION['WebAuthnCredentialRequestOptions'] = '';
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '{}']]);
        $webAuthn = new WebAuthn(self::createStub(TwoFactor::class));
        self::assertFalse($webAuthn->check($request));
        self::assertStringContainsString('Two-factor authentication failed:', $webAuthn->getError());
    }

    public function testCheck3(): void
    {
        $_SESSION['WebAuthnCredentialRequestOptions'] = '{"challenge":"challenge"}';
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '{}']]);

        $server = self::createMock(Server::class);
        $server->expects(self::once())->method('parseAndValidateAssertionResponse')
            ->willThrowException(new WebAuthnException());

        $webAuthn = new WebAuthn(self::createStub(TwoFactor::class));
        $webAuthn->setServer($server);
        self::assertFalse($webAuthn->check($request));
        self::assertStringContainsString('Two-factor authentication failed.', $webAuthn->getError());
    }

    public function testCheck4(): void
    {
        $_SESSION['WebAuthnCredentialRequestOptions'] = '{"challenge":"challenge"}';
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['webauthn_request_response', '', '{}']]);

        $twoFactor = self::createStub(TwoFactor::class);
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

        $server = self::createMock(Server::class);
        $server->expects(self::once())->method('parseAndValidateAssertionResponse')->with(
            self::equalTo('{}'),
            self::equalTo([['type' => 'public-key', 'id' => 'cHVibGljS2V5Q3JlZGVudGlhbElkMQ']]),
            self::equalTo('challenge'),
            self::equalTo($request),
        );

        $webAuthn = new WebAuthn($twoFactor);
        $webAuthn->setServer($server);
        self::assertTrue($webAuthn->check($request));
    }
}
