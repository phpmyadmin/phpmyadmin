<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest as GuzzleHttpServerRequest;
use HttpSoft\Message\ServerRequest as HttpSoftServerRequest;
use HttpSoft\Message\ServerRequestFactory as HttpSoftServerRequestFactory;
use HttpSoft\Message\UriFactory as HttpSoftUriFactory;
use Laminas\Diactoros\ServerRequest as LaminasServerRequest;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Laminas\Diactoros\UriFactory as LaminasUriFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest as NyholmServerRequest;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Factory\UriFactory;
use PhpMyAdmin\Http\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;
use Slim\Psr7\Factory\UriFactory as SlimUriFactory;
use Slim\Psr7\Request as SlimServerRequest;
use Slim\Psr7\Uri;

use function class_exists;

#[CoversClass(ServerRequestFactory::class)]
#[Medium]
final class ServerRequestFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear _SERVER global
        $_SERVER = [
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
            'REQUEST_TIME' => $_SERVER['REQUEST_TIME'],
            'REQUEST_TIME_FLOAT' => $_SERVER['REQUEST_TIME_FLOAT'],
            'PHP_SELF' => $_SERVER['PHP_SELF'],
            'argv' => $_SERVER['argv'],
        ];
        $_GET = [];
        $_POST = [];
    }

    /**
     * @psalm-param class-string<ServerRequestFactoryInterface> $provider
     * @psalm-param class-string<ServerRequestInterface> $expectedServerRequest
     */
    #[DataProvider('providerForTestCreateServerRequest')]
    public function testCreateServerRequest(string $provider, string $expectedServerRequest): void
    {
        $this->skipIfNotAvailable($provider);
        $serverRequestFactory = new ServerRequestFactory(new $provider());
        $serverRequest = $serverRequestFactory->createServerRequest('GET', 'http://example.com/');
        $actual = (new ReflectionProperty(ServerRequest::class, 'serverRequest'))->getValue($serverRequest);
        self::assertInstanceOf($expectedServerRequest, $actual);
    }

    /**
     * @return iterable<string, array{
     *     class-string<ServerRequestFactoryInterface>,
     *     class-string<ServerRequestInterface>
     * }>
     */
    public static function providerForTestCreateServerRequest(): iterable
    {
        yield 'slim/psr7' => [SlimServerRequestFactory::class, SlimServerRequest::class];
        yield 'laminas/laminas-diactoros' => [LaminasServerRequestFactory::class, LaminasServerRequest::class];
        yield 'nyholm/psr7' => [Psr17Factory::class, NyholmServerRequest::class];
        yield 'guzzlehttp/psr7' => [HttpFactory::class, GuzzleHttpServerRequest::class];
        yield 'httpsoft/http-message' => [HttpSoftServerRequestFactory::class, HttpSoftServerRequest::class];
    }

    /** @psalm-param class-string<ServerRequestFactoryInterface> $provider */
    #[DataProvider('providerForTestCreate')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCreate(string $provider): void
    {
        $this->skipIfNotAvailable($provider);
        (new ReflectionProperty(ServerRequestFactory::class, 'providers'))->setValue(null, [$provider]);
        $serverRequestFactory = ServerRequestFactory::create();
        $actual = (new ReflectionProperty(ServerRequestFactory::class, 'serverRequestFactory'))
            ->getValue($serverRequestFactory);
        self::assertInstanceOf($provider, $actual);
    }

    /** @return iterable<string, array{class-string<ServerRequestFactoryInterface>}> */
    public static function providerForTestCreate(): iterable
    {
        yield 'slim/psr7' => [SlimServerRequestFactory::class];
        yield 'laminas/laminas-diactoros' => [LaminasServerRequestFactory::class];
        yield 'nyholm/psr7' => [Psr17Factory::class];
        yield 'guzzlehttp/psr7' => [HttpFactory::class];
        yield 'httpsoft/http-message' => [HttpSoftServerRequestFactory::class];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCreateWithoutProvider(): void
    {
        (new ReflectionProperty(ServerRequestFactory::class, 'providers'))
            ->setValue(null, ['InvalidServerRequestFactoryClass']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No HTTP server request factories found.');
        ServerRequestFactory::create();
    }

    /**
     * @psalm-param class-string<ServerRequestFactoryInterface> $provider
     * @psalm-param class-string<UriFactoryInterface> $uriFactoryProvider
     * @psalm-param class-string<UriInterface> $expectedUri
     */
    #[DataProvider('providerForTestFromGlobals')]
    public function testFromGlobals(string $provider, string $uriFactoryProvider, string $expectedUri): void
    {
        $this->skipIfNotAvailable($provider);
        $this->skipIfNotAvailable($uriFactoryProvider);
        (new ReflectionProperty(UriFactory::class, 'providers'))->setValue(null, [$uriFactoryProvider]);
        $serverRequestFactory = new ServerRequestFactory(new $provider());

        $_GET['foo'] = 'bar';
        $_GET['blob'] = 'baz';
        $_SERVER['QUERY_STRING'] = 'foo=bar&blob=baz';
        $_SERVER['REQUEST_URI'] = '/test-page.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'phpmyadmin.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';

        (new ReflectionProperty(ServerRequestFactory::class, 'getAllHeaders'))->setValue(null, null);

        $serverRequest = $serverRequestFactory->fromGlobals();

        self::assertInstanceOf($expectedUri, $serverRequest->getUri());
        self::assertSame('GET', $serverRequest->getMethod());
        self::assertSame(
            'http://phpmyadmin.local/test-page.php?foo=bar&blob=baz',
            $serverRequest->getUri()->__toString(),
        );
        self::assertFalse($serverRequest->isPost());
        self::assertSame('default', $serverRequest->getParam('not-exists', 'default'));
        self::assertSame('bar', $serverRequest->getParam('foo'));
        self::assertSame('baz', $serverRequest->getParam('blob'));
        self::assertSame(['foo' => 'bar', 'blob' => 'baz'], $serverRequest->getQueryParams());
    }

    /**
     * @psalm-param class-string<ServerRequestFactoryInterface> $provider
     * @psalm-param class-string<UriFactoryInterface> $uriFactoryProvider
     * @psalm-param class-string<UriInterface> $expectedUri
     */
    #[DataProvider('providerForTestFromGlobals')]
    public function testFromGlobals2(string $provider, string $uriFactoryProvider, string $expectedUri): void
    {
        $this->skipIfNotAvailable($provider);
        $this->skipIfNotAvailable($uriFactoryProvider);
        (new ReflectionProperty(UriFactory::class, 'providers'))->setValue(null, [$uriFactoryProvider]);
        $serverRequestFactory = new ServerRequestFactory(new $provider());

        $_GET = [];
        $_GET['foo'] = 'bar';
        $_GET['blob'] = 'baz';
        $_POST = [];
        $_POST['input1'] = 'value1';
        $_POST['input2'] = 'value2';
        $_POST['input3'] = '';
        $_SERVER['QUERY_STRING'] = 'foo=bar&blob=baz';
        $_SERVER['REQUEST_URI'] = '/test-page.php';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'phpmyadmin.local';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['HTTPS'] = 'on';

        $getAllHeaders = static fn (): array => ['Content-Type' => 'application/x-www-form-urlencoded'];
        (new ReflectionProperty(ServerRequestFactory::class, 'getAllHeaders'))->setValue(null, $getAllHeaders);

        $serverRequest = $serverRequestFactory->fromGlobals();

        self::assertInstanceOf($expectedUri, $serverRequest->getUri());
        self::assertSame(['application/x-www-form-urlencoded'], $serverRequest->getHeader('Content-Type'));
        self::assertSame('POST', $serverRequest->getMethod());
        self::assertSame(
            'https://phpmyadmin.local/test-page.php?foo=bar&blob=baz',
            $serverRequest->getUri()->__toString(),
        );
        self::assertTrue($serverRequest->isPost());
        self::assertSame('default', $serverRequest->getParam('not-exists', 'default'));
        self::assertSame('bar', $serverRequest->getParam('foo'));
        self::assertSame('baz', $serverRequest->getParam('blob'));
        self::assertSame(['foo' => 'bar', 'blob' => 'baz'], $serverRequest->getQueryParams());
        self::assertSame(
            ['input1' => 'value1', 'input2' => 'value2', 'input3' => ''],
            $serverRequest->getParsedBody(),
        );
        self::assertNull($serverRequest->getParsedBodyParam('foo'));
        self::assertSame('value1', $serverRequest->getParsedBodyParam('input1'));
        self::assertSame('value2', $serverRequest->getParsedBodyParam('input2'));
        self::assertSame('', $serverRequest->getParsedBodyParam('input3', 'default'));
    }

    /**
     * @psalm-param class-string<ServerRequestFactoryInterface> $provider
     * @psalm-param class-string<UriFactoryInterface> $uriFactoryProvider
     * @psalm-param class-string<UriInterface> $expectedUri
     */
    #[DataProvider('providerForTestFromGlobals')]
    public function testFromGlobals3(string $provider, string $uriFactoryProvider, string $expectedUri): void
    {
        $this->skipIfNotAvailable($provider);
        $this->skipIfNotAvailable($uriFactoryProvider);
        (new ReflectionProperty(UriFactory::class, 'providers'))->setValue(null, [$uriFactoryProvider]);
        $serverRequestFactory = new ServerRequestFactory(new $provider());

        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $getAllHeaders = static fn (): array => ['Content-Type' => 'application/json', 'Content-Length' => '123'];
        (new ReflectionProperty(ServerRequestFactory::class, 'getAllHeaders'))->setValue(null, $getAllHeaders);

        $serverRequest = $serverRequestFactory->fromGlobals();

        self::assertInstanceOf($expectedUri, $serverRequest->getUri());
        self::assertSame('POST', $serverRequest->getMethod());
        self::assertNull($serverRequest->getParsedBody());
        self::assertSame(
            ['Host' => ['example.com'], 'Content-Type' => ['application/json'], 'Content-Length' => ['123']],
            $serverRequest->getHeaders(),
        );
    }

    /**
     * @return iterable<string, array{
     *     class-string<ServerRequestFactoryInterface>,
     *     class-string<UriFactoryInterface>,
     *     class-string<UriInterface>
     * }>
     */
    public static function providerForTestFromGlobals(): iterable
    {
        yield 'slim/psr7' => [
            SlimServerRequestFactory::class,
            SlimUriFactory::class,
            Uri::class,
        ];

        yield 'laminas/laminas-diactoros' => [
            LaminasServerRequestFactory::class,
            LaminasUriFactory::class,
            \Laminas\Diactoros\Uri::class,
        ];

        yield 'nyholm/psr7' => [
            Psr17Factory::class,
            Psr17Factory::class,
            \Nyholm\Psr7\Uri::class,
        ];

        yield 'guzzlehttp/psr7' => [
            HttpFactory::class,
            HttpFactory::class,
            \GuzzleHttp\Psr7\Uri::class,
        ];

        yield 'httpsoft/http-message' => [
            HttpSoftServerRequestFactory::class,
            HttpSoftUriFactory::class,
            \HttpSoft\Message\Uri::class,
        ];
    }

    /** @psalm-param class-string<ServerRequestFactoryInterface|UriFactoryInterface> $provider */
    private function skipIfNotAvailable(string $provider): void
    {
        if (class_exists($provider)) {
            return;
        }

        // This can happen when testing without the development packages.
        self::markTestSkipped($provider . ' is not available.');
    }
}
