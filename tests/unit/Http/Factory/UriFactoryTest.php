<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use HttpSoft\Message\UriFactory as HttpSoftUriFactory;
use Laminas\Diactoros\UriFactory as LaminasUriFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpMyAdmin\Http\Factory\UriFactory;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\Psr7\Factory\UriFactory as SlimUriFactory;
use Slim\Psr7\Uri;

use function array_merge;
use function class_exists;

#[CoversClass(UriFactory::class)]
#[Medium]
final class UriFactoryTest extends TestCase
{
    /**
     * @psalm-param class-string<UriFactoryInterface> $provider
     * @psalm-param class-string<UriInterface> $expectedUri
     */
    #[DataProvider('providerForTestCreateUri')]
    public function testCreateUri(string $provider, string $expectedUri): void
    {
        $this->skipIfNotAvailable($provider);
        $uriFactory = new UriFactory(new $provider());
        $uri = $uriFactory->createUri('https://www.phpmyadmin.net/');
        self::assertInstanceOf($expectedUri, $uri);
    }

    /** @return iterable<string, array{class-string<UriFactoryInterface>, class-string<UriInterface>}> */
    public static function providerForTestCreateUri(): iterable
    {
        yield 'slim/psr7' => [SlimUriFactory::class, Uri::class];
        yield 'laminas/laminas-diactoros' => [LaminasUriFactory::class, \Laminas\Diactoros\Uri::class];
        yield 'nyholm/psr7' => [Psr17Factory::class, \Nyholm\Psr7\Uri::class];
        yield 'guzzlehttp/psr7' => [HttpFactory::class, \GuzzleHttp\Psr7\Uri::class];
        yield 'httpsoft/http-message' => [HttpSoftUriFactory::class, \HttpSoft\Message\Uri::class];
    }

    /** @psalm-param class-string<UriFactoryInterface> $provider */
    #[DataProvider('uriFactoryProviders')]
    #[BackupStaticProperties(true)]
    public function testCreate(string $provider): void
    {
        $this->skipIfNotAvailable($provider);
        (new ReflectionProperty(UriFactory::class, 'providers'))->setValue(null, [$provider]);
        $uriFactory = UriFactory::create();
        $actual = (new ReflectionProperty(UriFactory::class, 'uriFactory'))->getValue($uriFactory);
        self::assertInstanceOf($provider, $actual);
    }

    /** @return iterable<string, array{class-string<UriFactoryInterface>}> */
    public static function uriFactoryProviders(): iterable
    {
        yield 'slim/psr7' => [SlimUriFactory::class];
        yield 'laminas/laminas-diactoros' => [LaminasUriFactory::class];
        yield 'nyholm/psr7' => [Psr17Factory::class];
        yield 'guzzlehttp/psr7' => [HttpFactory::class];
        yield 'httpsoft/http-message' => [HttpSoftUriFactory::class];
    }

    #[BackupStaticProperties(true)]
    public function testCreateWithoutProvider(): void
    {
        (new ReflectionProperty(UriFactory::class, 'providers'))->setValue(null, ['InvalidUriFactoryClass']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No URI factories found.');
        UriFactory::create();
    }

    /** @psalm-param class-string<UriFactoryInterface> $provider */
    private function skipIfNotAvailable(string $provider): void
    {
        if (class_exists($provider)) {
            return;
        }

        // This can happen when testing without the development packages.
        self::markTestSkipped($provider . ' is not available.');
    }

    /** @param array<string, mixed> $server */
    #[DataProvider('providerCreateFromGlobals')]
    public function testCreateFromGlobals(string $expected, array $server): void
    {
        foreach (self::uriFactoryProviders() as [$provider]) {
            $this->skipIfNotAvailable($provider);
            $uriFactory = new UriFactory(new $provider());
            $uri = $uriFactory->fromGlobals($server);
            self::assertSame($expected, (string) $uri, 'UriFactory: ' . $provider);
        }
    }

    /**
     * @see https://github.com/guzzle/psr7/blob/7ec62dc3f44aa218487dbed81a9bf9bc647be55d/tests/ServerRequestTest.php#L296
     *
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function providerCreateFromGlobals(): iterable
    {
        $server = [
            'REQUEST_URI' => '/blog/article.php?id=10&user=foo',
            'SERVER_PORT' => '443',
            'SERVER_ADDR' => '217.112.82.20',
            'SERVER_NAME' => 'www.example.org',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'POST',
            'QUERY_STRING' => 'id=10&user=foo',
            'DOCUMENT_ROOT' => '/path/to/your/server/root/',
            'HTTP_HOST' => 'www.example.org',
            'HTTPS' => 'on',
            'REMOTE_ADDR' => '193.60.168.69',
            'REMOTE_PORT' => '5390',
            'SCRIPT_NAME' => '/blog/article.php',
            'SCRIPT_FILENAME' => '/path/to/your/server/root/blog/article.php',
            'PHP_SELF' => '/blog/article.php',
        ];

        yield 'HTTPS request' => ['https://www.example.org/blog/article.php?id=10&user=foo', $server];

        yield 'HTTPS request with different on value' => [
            'https://www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTPS' => '1']),
        ];

        yield 'HTTP request' => [
            'http://www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTPS' => 'off', 'SERVER_PORT' => '80']),
        ];

        yield 'HTTP_HOST missing -> fallback to SERVER_NAME' => [
            'https://www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => null]),
        ];

        yield 'HTTP_HOST and SERVER_NAME missing -> fallback to SERVER_ADDR' => [
            'https://217.112.82.20/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => null, 'SERVER_NAME' => null]),
        ];

        yield 'Query string with ?' => [
            'https://www.example.org/path?continue=https://example.com/path?param=1',
            array_merge(
                $server,
                ['REQUEST_URI' => '/path?continue=https://example.com/path?param=1', 'QUERY_STRING' => ''],
            ),
        ];

        yield 'No query String' => [
            'https://www.example.org/blog/article.php',
            array_merge($server, ['REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => '']),
        ];

        yield 'Host header with port' => [
            'https://www.example.org:8324/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'www.example.org:8324']),
        ];

        yield 'IPv6 local loopback address' => [
            'https://[::1]:8000/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => '[::1]:8000']),
        ];

        yield 'Invalid host' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'a:b']),
        ];

        yield 'Host header with userinfo delimiter' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'trusted.example@evil.example']),
        ];

        yield 'Host header with path delimiter' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'example.com/path']),
        ];

        yield 'Host header with query delimiter' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'example.com?x=1']),
        ];

        yield 'Host header with fragment delimiter' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'example.com#frag']),
        ];

        yield 'Host header with backslash delimiter' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'example.com\\evil']),
        ];

        yield 'Host header with space' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'bad host']),
        ];

        yield 'Host header with multiple ports' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'example.com:80:90']),
        ];

        yield 'Host header with invalid ip literal' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => '[bad]']),
        ];

        yield 'Host header with invalid ip literal 2' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => '[b:ad]']),
        ];

        yield 'Host header with unexpected opening bracket' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'foo[bar']),
        ];

        yield 'Host header with unexpected closing bracket' => [
            'https://localhost/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => 'foo]bar']),
        ];

        yield 'Different port with SERVER_PORT' => [
            'https://www.example.org:8324/blog/article.php?id=10&user=foo',
            array_merge($server, ['SERVER_PORT' => '8324']),
        ];

        yield 'Invalid SERVER_PORT is ignored instead of coerced to zero' => [
            'https://www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['SERVER_PORT' => 'not-a-port']),
        ];

        yield 'Non-string SERVER_PORT is ignored' => [
            'https://www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['SERVER_PORT' => ['443']]),
        ];

        yield 'Non-string HTTP_HOST falls back to SERVER_NAME' => [
            'https://www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => ['www.example.org']]),
        ];

        yield 'Non-string SERVER_NAME falls back to SERVER_ADDR' => [
            'https://217.112.82.20/blog/article.php?id=10&user=foo',
            array_merge($server, ['HTTP_HOST' => null, 'SERVER_NAME' => ['www.example.org']]),
        ];

        yield 'REQUEST_URI missing query string' => [
            'https://www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['REQUEST_URI' => '/blog/article.php']),
        ];

        yield 'Non-string REQUEST_URI is treated as missing' => [
            'https://www.example.org/?id=10&user=foo',
            array_merge($server, ['REQUEST_URI' => ['bad']]),
        ];

        yield 'Non-string QUERY_STRING is treated as missing' => [
            'https://www.example.org/blog/article.php',
            array_merge($server, ['REQUEST_URI' => '/blog/article.php', 'QUERY_STRING' => ['bad']]),
        ];

        yield 'Empty server variable' => ['http://localhost/', []];

        yield 'With only PHP_AUTH_USER' => [
            'https://username@www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['PHP_AUTH_USER' => 'username']),
        ];

        yield 'With PHP_AUTH_USER and PHP_AUTH_PW' => [
            'https://username:password@www.example.org/blog/article.php?id=10&user=foo',
            array_merge($server, ['PHP_AUTH_USER' => 'username', 'PHP_AUTH_PW' => 'password']),
        ];
    }
}
