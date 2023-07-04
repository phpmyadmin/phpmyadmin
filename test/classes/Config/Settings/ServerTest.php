<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Server;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Server::class)]
class ServerTest extends TestCase
{
    #[DataProvider('valuesForHostProvider')]
    public function testHost(mixed $actual, string $expected): void
    {
        $server = new Server(['host' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->host);
        $this->assertArrayHasKey('host', $serverArray);
        $this->assertSame($expected, $serverArray['host']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForHostProvider(): iterable
    {
        yield 'null value' => [null, 'localhost'];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForPortProvider')]
    public function testPort(mixed $actual, string $expected): void
    {
        $server = new Server(['port' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->port);
        $this->assertArrayHasKey('port', $serverArray);
        $this->assertSame($expected, $serverArray['port']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForPortProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForSocketProvider')]
    public function testSocket(mixed $actual, string $expected): void
    {
        $server = new Server(['socket' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->socket);
        $this->assertArrayHasKey('socket', $serverArray);
        $this->assertSame($expected, $serverArray['socket']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSocketProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSsl(mixed $actual, bool $expected): void
    {
        $server = new Server(['ssl' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->ssl);
        $this->assertArrayHasKey('ssl', $serverArray);
        $this->assertSame($expected, $serverArray['ssl']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultFalseProvider(): iterable
    {
        yield 'null value' => [null, false];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testSslKey(mixed $actual, string|null $expected): void
    {
        $server = new Server(['ssl_key' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->sslKey);
        $this->assertArrayHasKey('ssl_key', $serverArray);
        $this->assertSame($expected, $serverArray['ssl_key']);
    }

    /** @return iterable<string, array{mixed, string|null}> */
    public static function valuesForSslOptionsProvider(): iterable
    {
        yield 'null value' => [null, null];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testSslCert(mixed $actual, string|null $expected): void
    {
        $server = new Server(['ssl_cert' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->sslCert);
        $this->assertArrayHasKey('ssl_cert', $serverArray);
        $this->assertSame($expected, $serverArray['ssl_cert']);
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testSslCa(mixed $actual, string|null $expected): void
    {
        $server = new Server(['ssl_ca' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->sslCa);
        $this->assertArrayHasKey('ssl_ca', $serverArray);
        $this->assertSame($expected, $serverArray['ssl_ca']);
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testSslCaPath(mixed $actual, string|null $expected): void
    {
        $server = new Server(['ssl_ca_path' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->sslCaPath);
        $this->assertArrayHasKey('ssl_ca_path', $serverArray);
        $this->assertSame($expected, $serverArray['ssl_ca_path']);
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testSslCiphers(mixed $actual, string|null $expected): void
    {
        $server = new Server(['ssl_ciphers' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->sslCiphers);
        $this->assertArrayHasKey('ssl_ciphers', $serverArray);
        $this->assertSame($expected, $serverArray['ssl_ciphers']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testSslVerify(mixed $actual, bool $expected): void
    {
        $server = new Server(['ssl_verify' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->sslVerify);
        $this->assertArrayHasKey('ssl_verify', $serverArray);
        $this->assertSame($expected, $serverArray['ssl_verify']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultTrueProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testCompress(mixed $actual, bool $expected): void
    {
        $server = new Server(['compress' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->compress);
        $this->assertArrayHasKey('compress', $serverArray);
        $this->assertSame($expected, $serverArray['compress']);
    }

    #[DataProvider('valuesForControlHostProvider')]
    public function testControlHost(mixed $actual, string $expected): void
    {
        $server = new Server(['controlhost' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlHost);
        $this->assertArrayHasKey('controlhost', $serverArray);
        $this->assertSame($expected, $serverArray['controlhost']);
    }

    #[DataProvider('valuesForControlHostProvider')]
    public function testControlHost2(mixed $actual, string $expected): void
    {
        $server = new Server(['control_host' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlHost);
        $this->assertArrayHasKey('controlhost', $serverArray);
        $this->assertSame($expected, $serverArray['controlhost']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForControlHostProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForControlPortProvider')]
    public function testControlPort(mixed $actual, string $expected): void
    {
        $server = new Server(['controlport' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlPort);
        $this->assertArrayHasKey('controlport', $serverArray);
        $this->assertSame($expected, $serverArray['controlport']);
    }

    #[DataProvider('valuesForControlPortProvider')]
    public function testControlPort2(mixed $actual, string $expected): void
    {
        $server = new Server(['control_port' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlPort);
        $this->assertArrayHasKey('controlport', $serverArray);
        $this->assertSame($expected, $serverArray['controlport']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForControlPortProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForControlUserProvider')]
    public function testControlUser(mixed $actual, string $expected): void
    {
        $server = new Server(['controluser' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlUser);
        $this->assertArrayHasKey('controluser', $serverArray);
        $this->assertSame($expected, $serverArray['controluser']);
    }

    #[DataProvider('valuesForControlUserProvider')]
    public function testControlUser2(mixed $actual, string $expected): void
    {
        $server = new Server(['control_user' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlUser);
        $this->assertArrayHasKey('controluser', $serverArray);
        $this->assertSame($expected, $serverArray['controluser']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForControlUserProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForControlPassProvider')]
    public function testControlPass(mixed $actual, string $expected): void
    {
        $server = new Server(['controlpass' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlPass);
        $this->assertArrayHasKey('controlpass', $serverArray);
        $this->assertSame($expected, $serverArray['controlpass']);
    }

    #[DataProvider('valuesForControlPassProvider')]
    public function testControlPass2(mixed $actual, string $expected): void
    {
        $server = new Server(['control_pass' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlPass);
        $this->assertArrayHasKey('controlpass', $serverArray);
        $this->assertSame($expected, $serverArray['controlpass']);
    }

    #[DataProvider('valuesForControlPassProvider')]
    public function testControlPass3(mixed $actual, string $expected): void
    {
        $server = new Server(['control_password' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlPass);
        $this->assertArrayHasKey('controlpass', $serverArray);
        $this->assertSame($expected, $serverArray['controlpass']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForControlPassProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForControlSocketProvider')]
    public function testControlSocket(mixed $actual, string|null $expected): void
    {
        $server = new Server(['control_socket' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSocket);
        $this->assertArrayHasKey('control_socket', $serverArray);
        $this->assertSame($expected, $serverArray['control_socket']);
    }

    /** @return iterable<string, array{mixed, string|null}> */
    public static function valuesForControlSocketProvider(): iterable
    {
        yield 'null value' => [null, null];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForControlSslProvider')]
    public function testControlSsl(mixed $actual, bool|null $expected): void
    {
        $server = new Server(['control_ssl' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSsl);
        $this->assertArrayHasKey('control_ssl', $serverArray);
        $this->assertSame($expected, $serverArray['control_ssl']);
    }

    /** @return iterable<string, array{mixed, bool|null}> */
    public static function valuesForControlSslProvider(): iterable
    {
        yield 'null value' => [null, null];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testControlSslKey(mixed $actual, string|null $expected): void
    {
        $server = new Server(['control_ssl_key' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSslKey);
        $this->assertArrayHasKey('control_ssl_key', $serverArray);
        $this->assertSame($expected, $serverArray['control_ssl_key']);
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testControlSslCert(mixed $actual, string|null $expected): void
    {
        $server = new Server(['control_ssl_cert' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSslCert);
        $this->assertArrayHasKey('control_ssl_cert', $serverArray);
        $this->assertSame($expected, $serverArray['control_ssl_cert']);
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testControlSslCa(mixed $actual, string|null $expected): void
    {
        $server = new Server(['control_ssl_ca' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSslCa);
        $this->assertArrayHasKey('control_ssl_ca', $serverArray);
        $this->assertSame($expected, $serverArray['control_ssl_ca']);
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testControlSslCaPath(mixed $actual, string|null $expected): void
    {
        $server = new Server(['control_ssl_ca_path' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSslCaPath);
        $this->assertArrayHasKey('control_ssl_ca_path', $serverArray);
        $this->assertSame($expected, $serverArray['control_ssl_ca_path']);
    }

    #[DataProvider('valuesForSslOptionsProvider')]
    public function testControlSslCiphers(mixed $actual, string|null $expected): void
    {
        $server = new Server(['control_ssl_ciphers' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSslCiphers);
        $this->assertArrayHasKey('control_ssl_ciphers', $serverArray);
        $this->assertSame($expected, $serverArray['control_ssl_ciphers']);
    }

    #[DataProvider('valuesForControlSslVerifyProvider')]
    public function testControlSslVerify(mixed $actual, bool|null $expected): void
    {
        $server = new Server(['control_ssl_verify' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlSslVerify);
        $this->assertArrayHasKey('control_ssl_verify', $serverArray);
        $this->assertSame($expected, $serverArray['control_ssl_verify']);
    }

    /** @return iterable<string, array{mixed, bool|null}> */
    public static function valuesForControlSslVerifyProvider(): iterable
    {
        yield 'null value' => [null, null];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }

    #[DataProvider('valuesForControlCompressProvider')]
    public function testControlCompress(mixed $actual, bool|null $expected): void
    {
        $server = new Server(['control_compress' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlCompress);
        $this->assertArrayHasKey('control_compress', $serverArray);
        $this->assertSame($expected, $serverArray['control_compress']);
    }

    /** @return iterable<string, array{mixed, bool|null}> */
    public static function valuesForControlCompressProvider(): iterable
    {
        yield 'null value' => [null, null];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    #[DataProvider('valuesForControlHideConnectionErrorsProvider')]
    public function testControlHideConnectionErrors(mixed $actual, bool|null $expected): void
    {
        $server = new Server(['control_hide_connection_errors' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->controlHideConnectionErrors);
        $this->assertArrayHasKey('control_hide_connection_errors', $serverArray);
        $this->assertSame($expected, $serverArray['control_hide_connection_errors']);
    }

    /** @return iterable<string, array{mixed, bool|null}> */
    public static function valuesForControlHideConnectionErrorsProvider(): iterable
    {
        yield 'null value' => [null, null];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    #[DataProvider('valuesForAuthTypeProvider')]
    public function testAuthType(mixed $actual, string $expected): void
    {
        $server = new Server(['auth_type' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->authType);
        $this->assertArrayHasKey('auth_type', $serverArray);
        $this->assertSame($expected, $serverArray['auth_type']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForAuthTypeProvider(): iterable
    {
        yield 'null value' => [null, 'cookie'];
        yield 'valid value' => ['config', 'config'];
        yield 'valid value 2' => ['http', 'http'];
        yield 'valid value 3' => ['signon', 'signon'];
        yield 'valid value 4' => ['cookie', 'cookie'];
        yield 'invalid value' => ['invalid', 'cookie'];
    }

    #[DataProvider('valuesForAuthHttpRealmProvider')]
    public function testAuthHttpRealm(mixed $actual, string $expected): void
    {
        $server = new Server(['auth_http_realm' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->authHttpRealm);
        $this->assertArrayHasKey('auth_http_realm', $serverArray);
        $this->assertSame($expected, $serverArray['auth_http_realm']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForAuthHttpRealmProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForUserProvider')]
    public function testUser(mixed $actual, string $expected): void
    {
        $server = new Server(['user' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->user);
        $this->assertArrayHasKey('user', $serverArray);
        $this->assertSame($expected, $serverArray['user']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForUserProvider(): iterable
    {
        yield 'null value' => [null, 'root'];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForPasswordProvider')]
    public function testPassword(mixed $actual, string $expected): void
    {
        $server = new Server(['password' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->password);
        $this->assertArrayHasKey('password', $serverArray);
        $this->assertSame($expected, $serverArray['password']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForPasswordProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForSignonSessionProvider')]
    public function testSignonSession(mixed $actual, string $expected): void
    {
        $server = new Server(['SignonSession' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->signonSession);
        $this->assertArrayHasKey('SignonSession', $serverArray);
        $this->assertSame($expected, $serverArray['SignonSession']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSignonSessionProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @param array<string, int|string|bool> $expected */
    #[DataProvider('valuesForSignonCookieParamsProvider')]
    public function testSignonCookieParams(mixed $actual, array $expected): void
    {
        $server = new Server(['SignonCookieParams' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->signonCookieParams);
        $this->assertArrayHasKey('SignonCookieParams', $serverArray);
        $this->assertSame($expected, $serverArray['SignonCookieParams']);
    }

    /** @return iterable<string, array{mixed, array<string, int|string|bool>}> */
    public static function valuesForSignonCookieParamsProvider(): iterable
    {
        yield 'null value' => [
            null,
            ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false],
        ];

        yield 'valid value' => [
            [
                'lifetime' => 0,
                'path' => 'test',
                'domain' => 'test',
                'secure' => false,
                'httponly' => false,
                'samesite' => 'Lax',
            ],
            [
                'lifetime' => 0,
                'path' => 'test',
                'domain' => 'test',
                'secure' => false,
                'httponly' => false,
                'samesite' => 'Lax',
            ],
        ];

        yield 'valid value 2' => [
            ['lifetime' => 1, 'secure' => true, 'httponly' => true, 'samesite' => 'Strict'],
            [
                'lifetime' => 1,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ],
        ];

        yield 'valid value 3' => [
            [],
            ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false],
        ];

        yield 'valid value with type coercion' => [
            ['lifetime' => '1', 'path' => 1234, 'domain' => 1234, 'secure' => 1, 'httponly' => 1],
            ['lifetime' => 1, 'path' => '1234', 'domain' => '1234', 'secure' => true, 'httponly' => true],
        ];

        yield 'invalid value' => [
            'invalid',
            ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false],
        ];

        yield 'invalid value 2' => [
            ['invalid' => 'invalid', 'lifetime' => -1, 'samesite' => 'invalid'],
            ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false],
        ];
    }

    #[DataProvider('valuesForSignonScriptProvider')]
    public function testSignonScript(mixed $actual, string $expected): void
    {
        $server = new Server(['SignonScript' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->signonScript);
        $this->assertArrayHasKey('SignonScript', $serverArray);
        $this->assertSame($expected, $serverArray['SignonScript']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSignonScriptProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForSignonURLProvider')]
    public function testSignonURL(mixed $actual, string $expected): void
    {
        $server = new Server(['SignonURL' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->signonUrl);
        $this->assertArrayHasKey('SignonURL', $serverArray);
        $this->assertSame($expected, $serverArray['SignonURL']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSignonURLProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForLogoutURLProvider')]
    public function testLogoutURL(mixed $actual, string $expected): void
    {
        $server = new Server(['LogoutURL' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->logoutUrl);
        $this->assertArrayHasKey('LogoutURL', $serverArray);
        $this->assertSame($expected, $serverArray['LogoutURL']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForLogoutURLProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @param string|string[] $expected */
    #[DataProvider('valuesForOnlyDbProvider')]
    public function testOnlyDb(mixed $actual, string|array $expected): void
    {
        $server = new Server(['only_db' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->onlyDb);
        $this->assertArrayHasKey('only_db', $serverArray);
        $this->assertSame($expected, $serverArray['only_db']);
    }

    /** @return iterable<string, array{mixed, string|string[]}> */
    public static function valuesForOnlyDbProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => [['test1', 'test2', 1234], ['test1', 'test2', '1234']];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
        yield 'invalid value' => [[], ''];
    }

    #[DataProvider('valuesForHideDbProvider')]
    public function testHideDb(mixed $actual, string $expected): void
    {
        $server = new Server(['hide_db' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->hideDb);
        $this->assertArrayHasKey('hide_db', $serverArray);
        $this->assertSame($expected, $serverArray['hide_db']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForHideDbProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForVerboseProvider')]
    public function testVerbose(mixed $actual, string $expected): void
    {
        $server = new Server(['verbose' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->verbose);
        $this->assertArrayHasKey('verbose', $serverArray);
        $this->assertSame($expected, $serverArray['verbose']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForVerboseProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForPmaDbProvider')]
    public function testPmaDb(mixed $actual, string $expected): void
    {
        $server = new Server(['pmadb' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->pmaDb);
        $this->assertArrayHasKey('pmadb', $serverArray);
        $this->assertSame($expected, $serverArray['pmadb']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForPmaDbProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testBookmarkTable(mixed $actual, string|false $expected): void
    {
        $server = new Server(['bookmarktable' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->bookmarkTable);
        $this->assertArrayHasKey('bookmarktable', $serverArray);
        $this->assertSame($expected, $serverArray['bookmarktable']);
    }

    /** @return iterable<string, array{mixed, string|false}> */
    public static function valuesForConfigStorageTablesProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value 3' => ['', ''];
        yield 'valid value with type coercion' => [true, '1'];
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testRelation(mixed $actual, string|false $expected): void
    {
        $server = new Server(['relation' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->relation);
        $this->assertArrayHasKey('relation', $serverArray);
        $this->assertSame($expected, $serverArray['relation']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testTableInfo(mixed $actual, string|false $expected): void
    {
        $server = new Server(['table_info' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->tableInfo);
        $this->assertArrayHasKey('table_info', $serverArray);
        $this->assertSame($expected, $serverArray['table_info']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testPdfPages(mixed $actual, string|false $expected): void
    {
        $server = new Server(['pdf_pages' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->pdfPages);
        $this->assertArrayHasKey('pdf_pages', $serverArray);
        $this->assertSame($expected, $serverArray['pdf_pages']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testColumnInfo(mixed $actual, string|false $expected): void
    {
        $server = new Server(['column_info' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->columnInfo);
        $this->assertArrayHasKey('column_info', $serverArray);
        $this->assertSame($expected, $serverArray['column_info']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testHistory(mixed $actual, string|false $expected): void
    {
        $server = new Server(['history' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->history);
        $this->assertArrayHasKey('history', $serverArray);
        $this->assertSame($expected, $serverArray['history']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testRecent(mixed $actual, string|false $expected): void
    {
        $server = new Server(['recent' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->recent);
        $this->assertArrayHasKey('recent', $serverArray);
        $this->assertSame($expected, $serverArray['recent']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testFavorite(mixed $actual, string|false $expected): void
    {
        $server = new Server(['favorite' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->favorite);
        $this->assertArrayHasKey('favorite', $serverArray);
        $this->assertSame($expected, $serverArray['favorite']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testTableUiPrefs(mixed $actual, string|false $expected): void
    {
        $server = new Server(['table_uiprefs' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->tableUiPrefs);
        $this->assertArrayHasKey('table_uiprefs', $serverArray);
        $this->assertSame($expected, $serverArray['table_uiprefs']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testTracking(mixed $actual, string|false $expected): void
    {
        $server = new Server(['tracking' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->tracking);
        $this->assertArrayHasKey('tracking', $serverArray);
        $this->assertSame($expected, $serverArray['tracking']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testUserConfig(mixed $actual, string|false $expected): void
    {
        $server = new Server(['userconfig' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->userConfig);
        $this->assertArrayHasKey('userconfig', $serverArray);
        $this->assertSame($expected, $serverArray['userconfig']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testUsers(mixed $actual, string|false $expected): void
    {
        $server = new Server(['users' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->users);
        $this->assertArrayHasKey('users', $serverArray);
        $this->assertSame($expected, $serverArray['users']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testUserGroups(mixed $actual, string|false $expected): void
    {
        $server = new Server(['usergroups' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->userGroups);
        $this->assertArrayHasKey('usergroups', $serverArray);
        $this->assertSame($expected, $serverArray['usergroups']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testNavigationHiding(mixed $actual, string|false $expected): void
    {
        $server = new Server(['navigationhiding' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->navigationHiding);
        $this->assertArrayHasKey('navigationhiding', $serverArray);
        $this->assertSame($expected, $serverArray['navigationhiding']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testSavedSearches(mixed $actual, string|false $expected): void
    {
        $server = new Server(['savedsearches' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->savedSearches);
        $this->assertArrayHasKey('savedsearches', $serverArray);
        $this->assertSame($expected, $serverArray['savedsearches']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testCentralColumns(mixed $actual, string|false $expected): void
    {
        $server = new Server(['central_columns' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->centralColumns);
        $this->assertArrayHasKey('central_columns', $serverArray);
        $this->assertSame($expected, $serverArray['central_columns']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testDesignerSettings(mixed $actual, string|false $expected): void
    {
        $server = new Server(['designer_settings' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->designerSettings);
        $this->assertArrayHasKey('designer_settings', $serverArray);
        $this->assertSame($expected, $serverArray['designer_settings']);
    }

    #[DataProvider('valuesForConfigStorageTablesProvider')]
    public function testExportTemplates(mixed $actual, string|false $expected): void
    {
        $server = new Server(['export_templates' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->exportTemplates);
        $this->assertArrayHasKey('export_templates', $serverArray);
        $this->assertSame($expected, $serverArray['export_templates']);
    }

    #[DataProvider('valuesForMaxTableUiPrefsProvider')]
    public function testMaxTableUiPrefs(mixed $actual, int $expected): void
    {
        $server = new Server(['MaxTableUiprefs' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->maxTableUiPrefs);
        $this->assertArrayHasKey('MaxTableUiprefs', $serverArray);
        $this->assertSame($expected, $serverArray['MaxTableUiprefs']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxTableUiPrefsProvider(): iterable
    {
        yield 'null value' => [null, 100];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [-1, 100];
        yield 'invalid value 2' => [0, 100];
    }

    #[DataProvider('valuesForSessionTimeZoneProvider')]
    public function testSessionTimeZone(mixed $actual, string $expected): void
    {
        $server = new Server(['SessionTimeZone' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->sessionTimeZone);
        $this->assertArrayHasKey('SessionTimeZone', $serverArray);
        $this->assertSame($expected, $serverArray['SessionTimeZone']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSessionTimeZoneProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testAllowRoot(mixed $actual, bool $expected): void
    {
        $server = new Server(['AllowRoot' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->allowRoot);
        $this->assertArrayHasKey('AllowRoot', $serverArray);
        $this->assertSame($expected, $serverArray['AllowRoot']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testAllowNoPassword(mixed $actual, bool $expected): void
    {
        $server = new Server(['AllowNoPassword' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->allowNoPassword);
        $this->assertArrayHasKey('AllowNoPassword', $serverArray);
        $this->assertSame($expected, $serverArray['AllowNoPassword']);
    }

    /** @param array<string, string|string[]> $expected */
    #[DataProvider('valuesForAllowDenyProvider')]
    public function testAllowDeny(mixed $actual, array $expected): void
    {
        $server = new Server(['AllowDeny' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->allowDeny);
        $this->assertArrayHasKey('AllowDeny', $serverArray);
        $this->assertSame($expected, $serverArray['AllowDeny']);
    }

    /** @return iterable<string, array{mixed, array<string, string|string[]>}> */
    public static function valuesForAllowDenyProvider(): iterable
    {
        yield 'null value' => [null, ['order' => '', 'rules' => []]];
        yield 'valid value' => [
            ['order' => '', 'rules' => ['allow root from 192.168.5.50', 'allow % from 192.168.6.10']],
            ['order' => '', 'rules' => ['allow root from 192.168.5.50', 'allow % from 192.168.6.10']],
        ];

        yield 'valid value 2' => [['order' => 'deny,allow'], ['order' => 'deny,allow', 'rules' => []]];
        yield 'valid value 3' => [['order' => 'allow,deny'], ['order' => 'allow,deny', 'rules' => []]];
        yield 'valid value 4' => [['order' => 'explicit'], ['order' => 'explicit', 'rules' => []]];
        yield 'valid value 5' => [[], ['order' => '', 'rules' => []]];
        yield 'valid value with type coercion' => [['rules' => [1234]], ['order' => '', 'rules' => ['1234']]];
        yield 'invalid value' => ['invalid', ['order' => '', 'rules' => []]];
        yield 'invalid value 2' => [
            ['invalid' => 'invalid', 'order' => 'invalid', 'rules' => 'invalid'],
            ['order' => '', 'rules' => []],
        ];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testDisableIS(mixed $actual, bool $expected): void
    {
        $server = new Server(['DisableIS' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->disableIS);
        $this->assertArrayHasKey('DisableIS', $serverArray);
        $this->assertSame($expected, $serverArray['DisableIS']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testTrackingVersionAutoCreate(mixed $actual, bool $expected): void
    {
        $server = new Server(['tracking_version_auto_create' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->trackingVersionAutoCreate);
        $this->assertArrayHasKey('tracking_version_auto_create', $serverArray);
        $this->assertSame($expected, $serverArray['tracking_version_auto_create']);
    }

    #[DataProvider('valuesForTrackingDefaultStatementsProvider')]
    public function testTrackingDefaultStatements(mixed $actual, string $expected): void
    {
        $server = new Server(['tracking_default_statements' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->trackingDefaultStatements);
        $this->assertArrayHasKey('tracking_default_statements', $serverArray);
        $this->assertSame($expected, $serverArray['tracking_default_statements']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForTrackingDefaultStatementsProvider(): iterable
    {
        yield 'null value' => [
            null,
            'CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,CREATE INDEX,DROP INDEX,INSERT,UPDATE,DELETE,'
            . 'TRUNCATE,REPLACE,CREATE VIEW,ALTER VIEW,DROP VIEW,CREATE DATABASE,ALTER DATABASE,DROP DATABASE',
        ];

        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testTrackingAddDropView(mixed $actual, bool $expected): void
    {
        $server = new Server(['tracking_add_drop_view' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->trackingAddDropView);
        $this->assertArrayHasKey('tracking_add_drop_view', $serverArray);
        $this->assertSame($expected, $serverArray['tracking_add_drop_view']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testTrackingAddDropTable(mixed $actual, bool $expected): void
    {
        $server = new Server(['tracking_add_drop_table' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->trackingAddDropTable);
        $this->assertArrayHasKey('tracking_add_drop_table', $serverArray);
        $this->assertSame($expected, $serverArray['tracking_add_drop_table']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testTrackingAddDropDatabase(mixed $actual, bool $expected): void
    {
        $server = new Server(['tracking_add_drop_database' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->trackingAddDropDatabase);
        $this->assertArrayHasKey('tracking_add_drop_database', $serverArray);
        $this->assertSame($expected, $serverArray['tracking_add_drop_database']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testHideConnectionErrors(mixed $actual, bool $expected): void
    {
        $server = new Server(['hide_connection_errors' => $actual]);
        $serverArray = $server->asArray();
        $this->assertSame($expected, $server->hideConnectionErrors);
        $this->assertArrayHasKey('hide_connection_errors', $serverArray);
        $this->assertSame($expected, $serverArray['hide_connection_errors']);
    }
}
