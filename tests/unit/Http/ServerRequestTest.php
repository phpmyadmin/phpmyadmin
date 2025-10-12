<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http;

use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(ServerRequest::class)]
class ServerRequestTest extends TestCase
{
    /**
     * @param array<string, string> $get
     * @param array<string, string> $post
     */
    #[DataProvider('providerForTestGetRoute')]
    public function testGetRoute(string $expected, array $get, array $post): void
    {
        $requestStub = self::createStub(ServerRequestInterface::class);
        $requestStub->method('getQueryParams')->willReturn($get);
        $requestStub->method('getParsedBody')->willReturn($post);
        $request = new ServerRequest($requestStub);
        self::assertSame($expected, $request->getRoute());
    }

    /**
     * @return array<int, array<int, array<string, string>|string>>
     * @psalm-return array<int, array{string, array<string, string>, array<string, string>}>
     */
    public static function providerForTestGetRoute(): iterable
    {
        return [
            ['/', [], []],
            ['/test', ['route' => '/test'], []],
            ['/test', [], ['route' => '/test']],
            ['/test-get', ['route' => '/test-get'], ['route' => '/test-post']],
            ['/', ['db' => 'db'], []],
            ['/', ['db' => 'db', 'table' => 'table'], []],
            ['/test', ['route' => '/test', 'db' => 'db'], []],
            ['/test', ['route' => '/test', 'db' => 'db', 'table' => 'table'], []],
            ['/', [], ['db' => 'db']],
            ['/', [], ['db' => 'db', 'table' => 'table']],
        ];
    }

    public function testGetQueryParam(): void
    {
        $queryParams = ['key1' => 'value1', 'key2' => ['value2'], 'key4' => ''];
        $requestStub = self::createStub(ServerRequestInterface::class);
        $requestStub->method('getQueryParams')->willReturn($queryParams);
        $request = new ServerRequest($requestStub);
        self::assertSame('value1', $request->getQueryParam('key1'));
        self::assertSame('value1', $request->getQueryParam('key1', 'default'));
        self::assertSame(['value2'], $request->getQueryParam('key2'));
        self::assertSame(['value2'], $request->getQueryParam('key2', 'default'));
        self::assertNull($request->getQueryParam('key3'));
        self::assertSame('default', $request->getQueryParam('key3', 'default'));
        self::assertSame('', $request->getQueryParam('key4'));
        self::assertSame('', $request->getQueryParam('key4', 'default'));
    }

    public function testHasBodyParam(): void
    {
        $queryParams = ['key1' => 'value1', 'key2' => ['value2'], 'key4' => ''];
        $requestStub = self::createStub(ServerRequestInterface::class);
        $requestStub->method('getParsedBody')->willReturn($queryParams);
        $request = new ServerRequest($requestStub);
        self::assertTrue($request->hasBodyParam('key1'));
        self::assertTrue($request->hasBodyParam('key2'));
        self::assertFalse($request->hasBodyParam('key3'));
        self::assertTrue($request->hasBodyParam('key4'));
    }

    public function testHasQueryParam(): void
    {
        $queryParams = ['key1' => 'value1', 'key2' => ['value2'], 'key4' => ''];
        $requestStub = self::createStub(ServerRequestInterface::class);
        $requestStub->method('getQueryParams')->willReturn($queryParams);
        $request = new ServerRequest($requestStub);
        self::assertTrue($request->hasQueryParam('key1'));
        self::assertTrue($request->has('key1'));
        self::assertTrue($request->hasQueryParam('key2'));
        self::assertTrue($request->has('key2'));
        self::assertFalse($request->hasQueryParam('key3'));
        self::assertFalse($request->has('key3'));
        self::assertTrue($request->hasQueryParam('key4'));
        self::assertTrue($request->has('key4'));
    }

    /**
     * @param array<string, string>      $headers
     * @param array<string, string>|null $body
     */
    #[DataProvider('isAjaxProvider')]
    public function testIsAjax(bool $expected, string $method, string $uri, array $headers, array|null $body): void
    {
        $request = ServerRequestFactory::create()->createServerRequest($method, $uri)->withParsedBody($body);
        foreach ($headers as $name => $value) {
            $request = $request->withAddedHeader($name, $value);
        }

        self::assertSame($expected, $request->isAjax());
    }

    /** @return iterable<int, array{bool, string, string, array<string, string>, array<string, string>|null}> */
    public static function isAjaxProvider(): iterable
    {
        return [
            [true, 'GET', 'http://example.com/index.php?route=/&ajax_request=1', [], null],
            [true, 'GET', 'http://example.com/index.php?route=/&ajax_request=0', [], null],
            [true, 'GET', 'http://example.com/index.php?route=/&ajax_request=true', [], null],
            [true, 'GET', 'http://example.com/index.php?route=/', ['X-Requested-With' => 'XMLHttpRequest'], null],
            [
                true,
                'GET',
                'http://example.com/index.php?route=/&ajax_request=1',
                ['X-Requested-With' => 'XMLHttpRequest'],
                null,
            ],
            [false, 'GET', 'http://example.com/index.php?route=/', [], null],
            [true, 'POST', 'http://example.com/index.php?route=/&ajax_request=1', [], []],
            [true, 'POST', 'http://example.com/index.php?route=/', ['X-Requested-With' => 'XMLHttpRequest'], []],
            [
                true,
                'POST',
                'http://example.com/index.php?route=/&ajax_request=1',
                ['X-Requested-With' => 'XMLHttpRequest'],
                [],
            ],
            [true, 'POST', 'http://example.com/index.php?route=/&ajax_request=1', [], ['ajax_request' => '1']],
            [
                true,
                'POST',
                'http://example.com/index.php?route=/&ajax_request=1',
                ['X-Requested-With' => 'XMLHttpRequest'],
                ['ajax_request' => '1'],
            ],
            [
                true,
                'POST',
                'http://example.com/index.php?route=/',
                ['X-Requested-With' => 'XMLHttpRequest'],
                ['ajax_request' => '1'],
            ],
            [true, 'POST', 'http://example.com/index.php?route=/', [], ['ajax_request' => '1']],
            [false, 'POST', 'http://example.com/index.php?route=/', [], []],
        ];
    }
}
