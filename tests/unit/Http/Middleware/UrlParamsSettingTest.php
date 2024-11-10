<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\UrlParamsSetting;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(UrlParamsSetting::class)]
final class UrlParamsSettingTest extends AbstractTestCase
{
    public function testProcess(): void
    {
        UrlParams::$params = [];
        UrlParams::$goto = '';
        UrlParams::$back = '';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['goto' => 'index.php?route=/', 'back' => 'index.php?route=/']);

        $response = self::createStub(ResponseInterface::class);
        $handler = self::createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($request)->willReturn($response);

        $urlParamsSetting = new UrlParamsSetting(self::createStub(Config::class));
        self::assertSame($response, $urlParamsSetting->process($request, $handler));
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('index.php?route=/', UrlParams::$goto);
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('index.php?route=/', UrlParams::$back);
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame(['goto' => 'index.php?route=/'], UrlParams::$params);

        UrlParams::$goto = '';
        UrlParams::$back = '';
    }
}
