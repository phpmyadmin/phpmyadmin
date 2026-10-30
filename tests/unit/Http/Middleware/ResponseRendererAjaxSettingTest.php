<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\ResponseRendererAjaxSetting;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(ResponseRendererAjaxSetting::class)]
final class ResponseRendererAjaxSettingTest extends TestCase
{
    #[DataProvider('providerForTestProcess')]
    public function testProcess(string $requestedWith, bool $expected): void
    {
        $responseRenderer = new ResponseRenderer();
        $responseRenderer->setAjax(! $expected);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withHeader('X-Requested-With', $requestedWith);

        $response = self::createStub(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $middleware = new ResponseRendererAjaxSetting($responseRenderer);
        self::assertSame($response, $middleware->process($request, $handler));
        self::assertSame($expected, $responseRenderer->isAjax());
    }

    /** @return iterable<string, array{string, bool}> */
    public static function providerForTestProcess(): iterable
    {
        yield 'AJAX request' => ['XMLHttpRequest', true];
        yield 'non-AJAX request' => ['', false];
    }
}
