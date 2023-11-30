<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Http\Middleware\UrlParamsSetting;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(UrlParamsSetting::class)]
final class UrlParamsSettingTest extends AbstractTestCase
{
    public function testProcess(): void
    {
        $GLOBALS['urlParams'] = null;
        $GLOBALS['goto'] = null;
        $GLOBALS['back'] = null;
        $_REQUEST['goto'] = 'index.php?route=/';
        $_REQUEST['back'] = 'index.php?route=/';

        $request = self::createStub(ServerRequestInterface::class);
        $response = self::createStub(ResponseInterface::class);
        $handler = self::createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($request)->willReturn($response);

        $urlParamsSetting = new UrlParamsSetting(self::createStub(Config::class));
        self::assertSame($response, $urlParamsSetting->process($request, $handler));
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('index.php?route=/', $GLOBALS['goto']);
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame('index.php?route=/', $GLOBALS['back']);
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertSame(['goto' => 'index.php?route=/'], $GLOBALS['urlParams']);
    }
}
