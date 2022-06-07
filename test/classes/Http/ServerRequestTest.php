<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http;

use PhpMyAdmin\Http\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @covers \PhpMyAdmin\Http\ServerRequest
 */
class ServerRequestTest extends TestCase
{
    /**
     * @param array<string, string> $get
     * @param array<string, string> $post
     *
     * @dataProvider providerForTestGetRoute
     */
    public function testGetRoute(string $expected, array $get, array $post): void
    {
        $requestStub = $this->createStub(ServerRequestInterface::class);
        $requestStub->method('getQueryParams')->willReturn($get);
        $requestStub->method('getParsedBody')->willReturn($post);
        $request = new ServerRequest($requestStub);
        $this->assertSame($expected, $request->getRoute());
    }

    /**
     * @return array<int, array<int, array<string, string>|string>>
     * @psalm-return array<int, array{string, array<string, string>, array<string, string>}>
     */
    public function providerForTestGetRoute(): iterable
    {
        return [
            ['/', [], []],
            ['/test', ['route' => '/test'], []],
            ['/test', [], ['route' => '/test']],
            ['/test-get', ['route' => '/test-get'], ['route' => '/test-post']],
            ['/database/structure', ['db' => 'db'], []],
            ['/sql', ['db' => 'db', 'table' => 'table'], []],
            ['/test', ['route' => '/test', 'db' => 'db'], []],
            ['/test', ['route' => '/test', 'db' => 'db', 'table' => 'table'], []],
            ['/', [], ['db' => 'db']],
            ['/', [], ['db' => 'db', 'table' => 'table']],
        ];
    }
}
