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
    #[DataProvider('uriFactoryProviders')]
    public function testCreateFromGlobals(string $provider): void
    {
        $this->skipIfNotAvailable($provider);
        $uriFactory = new UriFactory(new $provider());
        $uri = $uriFactory->fromGlobals([
            'SERVER_PORT' => '8080',
            'REQUEST_URI' => '/index.php?route=/server/plugins',
            'PHP_AUTH_USER' => 'username',
            'PHP_AUTH_PW' => 'password',
            'SCRIPT_NAME' => '/index.php',
            'QUERY_STRING' => 'route=/server/plugins',
            'HTTP_HOST' => 'example.com:8080',
        ]);
        self::assertSame('http://username:password@example.com:8080/index.php?route=/server/plugins', (string) $uri);
    }

    /** @psalm-param class-string<UriFactoryInterface> $provider */
    #[DataProvider('uriFactoryProviders')]
    public function testCreateFromGlobals2(string $provider): void
    {
        $this->skipIfNotAvailable($provider);
        $uriFactory = new UriFactory(new $provider());
        $uri = $uriFactory->fromGlobals([
            'SERVER_PORT' => '0',
            'PHP_AUTH_USER' => 'username',
            'SERVER_NAME' => 'example.com',
            'HTTPS' => 'on',
        ]);
        self::assertSame('https://username@example.com', (string) $uri);
    }

    /** @psalm-param class-string<UriFactoryInterface> $provider */
    #[DataProvider('uriFactoryProviders')]
    public function testCreateFromGlobals3(string $provider): void
    {
        $this->skipIfNotAvailable($provider);
        $uriFactory = new UriFactory(new $provider());
        $uri = $uriFactory->fromGlobals([
            'HTTP_HOST' => '[2001:DB8::1]',
            'HTTPS' => 'off',
            'REQUEST_URI' => '/index.php?route=/server/plugins',
        ]);
        self::assertSame('http://[2001:db8::1]/index.php?route=/server/plugins', (string) $uri);
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
}
