<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Factory;

use GuzzleHttp\Psr7\HttpFactory as GuzzleHttpFactory;
use Laminas\Diactoros\ServerRequestFactory as LaminasServerRequestFactory;
use Nyholm\Psr7\Factory\Psr17Factory as NyholmPsr17Factory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriInterface;
use ReflectionMethod;
use ReflectionProperty;
use Slim\Psr7\Factory\ServerRequestFactory as SlimServerRequestFactory;

use function array_merge;
use function class_exists;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Http\Factory\ServerRequestFactory
 */
class ServerRequestFactoryTest extends AbstractTestCase
{
    private const IMPLEMENTATION_CLASSES = [
        'slim/psr7' => [
            SlimServerRequestFactory::class,
            'Slim PSR-7',
        ],
        'guzzlehttp/psr7' => [
            GuzzleHttpFactory::class,
            'Guzzle PSR-7',
        ],
        'nyholm/psr7' => [
            NyholmPsr17Factory::class,
            'Nyholm PSR-7',
        ],
        'laminas/laminas-diactoros' => [
            LaminasServerRequestFactory::class,
            'Laminas diactoros PSR-7',
        ],
    ];

    public static function dataProviderPsr7Implementations(): array
    {
        return self::IMPLEMENTATION_CLASSES;
    }

    /**
     * @phpstan-param class-string $className
     */
    private function runOrSkip(string $className, string $humanName): void
    {
        if (! class_exists($className)) {
            $this->markTestSkipped($humanName . ' is missing');
        }

        foreach (self::IMPLEMENTATION_CLASSES as $libName => $details) {
            /** @phpstan-var class-string */
            $classImpl = $details[0];
            if ($classImpl === $className) {
                continue;
            }

            if (! class_exists($classImpl)) {
                continue;
            }

            $this->markTestSkipped($libName . ' exists and will conflict with the test results');
        }
    }

    /**
     * @phpstan-param class-string $className
     *
     * @dataProvider dataProviderPsr7Implementations
     */
    public function testPsr7ImplementationGet(string $className, string $humanName): void
    {
        $this->runOrSkip($className, $humanName);

        $_GET['foo'] = 'bar';
        $_GET['blob'] = 'baz';
        $_SERVER['QUERY_STRING'] = 'foo=bar&blob=baz';
        $_SERVER['REQUEST_URI'] = '/test-page.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'phpmyadmin.local';

        $request = ServerRequestFactory::createFromGlobals();
        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://phpmyadmin.local/test-page.php?foo=bar&blob=baz', $request->getUri()->__toString());
        self::assertFalse($request->isPost());
        self::assertSame('default', $request->getParam('not-exists', 'default'));
        self::assertSame('bar', $request->getParam('foo'));
        self::assertSame('baz', $request->getParam('blob'));
        self::assertSame([
            'foo' => 'bar',
            'blob' => 'baz',
        ], $request->getQueryParams());
    }

    public function testCreateServerRequestFromGlobals(): void
    {
        $_GET['foo'] = 'bar';
        $_GET['blob'] = 'baz';
        $_POST['input1'] = 'value1';
        $_POST['input2'] = 'value2';
        $_POST['input3'] = '';
        $_SERVER['QUERY_STRING'] = 'foo=bar&blob=baz';
        $_SERVER['REQUEST_URI'] = '/test-page.php';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'phpmyadmin.local';

        $property = new ReflectionProperty(ServerRequestFactory::class, 'getAllHeaders');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        $property->setValue(null, static function (): array {
            return ['Content-Type' => 'application/x-www-form-urlencoded'];
        });

        $method = (new ReflectionMethod(ServerRequestFactory::class, 'createServerRequestFromGlobals'));
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $serverRequest = $method->invokeArgs(null, [new ServerRequestFactory()]);

        $request = new ServerRequest($serverRequest);

        self::assertSame(['application/x-www-form-urlencoded'], $request->getHeader('Content-Type'));
        self::assertSame('POST', $request->getMethod());
        self::assertSame('http://phpmyadmin.local/test-page.php?foo=bar&blob=baz', $request->getUri()->__toString());
        self::assertTrue($request->isPost());
        self::assertSame('default', $request->getParam('not-exists', 'default'));
        self::assertSame('bar', $request->getParam('foo'));
        self::assertSame('baz', $request->getParam('blob'));
        self::assertSame([
            'foo' => 'bar',
            'blob' => 'baz',
        ], $request->getQueryParams());

        self::assertSame([
            'input1' => 'value1',
            'input2' => 'value2',
            'input3' => '',
        ], $request->getParsedBody());

        self::assertNull($request->getParsedBodyParam('foo'));
        self::assertSame('value1', $request->getParsedBodyParam('input1'));
        self::assertSame('value2', $request->getParsedBodyParam('input2'));
        self::assertSame('', $request->getParsedBodyParam('input3', 'default'));
    }

    /**
     * @phpstan-param class-string $className
     *
     * @dataProvider dataProviderPsr7Implementations
     */
    public function testPsr7ImplementationCreateServerRequestFactory(string $className, string $humanName): void
    {
        $this->runOrSkip($className, $humanName);

        $serverRequestFactory = new $className();
        self::assertInstanceOf(ServerRequestFactoryInterface::class, $serverRequestFactory);

        $factory = new ServerRequestFactory($serverRequestFactory);
        self::assertInstanceOf(ServerRequestFactory::class, $factory);
    }

    /**
     * @param array<string, mixed> $server
     *
     * @dataProvider providerCreateUriFromGlobals
     */
    public function testCreateUriFromGlobals(string $expected, array $server): void
    {
        $createUriFromGlobals = (new ReflectionMethod(ServerRequestFactory::class, 'createUriFromGlobals'));
        if (PHP_VERSION_ID < 80100) {
            $createUriFromGlobals->setAccessible(true);
        }

        $uri = $createUriFromGlobals->invoke(new ServerRequestFactory(), $server);
        self::assertInstanceOf(UriInterface::class, $uri);
        self::assertSame($expected, (string) $uri);
    }

    /**
     * @see https://github.com/guzzle/psr7/blob/7ec62dc3f44aa218487dbed81a9bf9bc647be55d/tests/ServerRequestTest.php#L296
     *
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function providerCreateUriFromGlobals(): iterable
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
                ['REQUEST_URI' => '/path?continue=https://example.com/path?param=1', 'QUERY_STRING' => '']
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
    }
}
