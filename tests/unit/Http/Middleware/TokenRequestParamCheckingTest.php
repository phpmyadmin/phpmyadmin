<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\TokenRequestParamChecking;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(TokenRequestParamChecking::class)]
final class TokenRequestParamCheckingTest extends TestCase
{
    public function testCheckTokenRequestParam(): void
    {
        $_POST = [];

        $middleware = new TokenRequestParamChecking(new ResponseRenderer());

        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'token';
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['token' => 'token']);
        $response = $middleware->checkTokenRequestParam($request);
        self::assertNull($response);
        self::assertArrayHasKey('test', $_POST);
        self::assertSame('test', $_POST['test']);
    }

    public function testCheckTokenRequestParamWithoutToken(): void
    {
        $_POST = [];

        $middleware = new TokenRequestParamChecking(new ResponseRenderer());

        $_POST['test'] = 'test';
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/');
        $response = $middleware->checkTokenRequestParam($request);
        self::assertNull($response);
        self::assertArrayNotHasKey('test', $_POST);
    }

    public function testCheckTokenRequestParamWithTokenMismatch(): void
    {
        $responseRenderer = new ResponseRenderer();
        $middleware = new TokenRequestParamChecking($responseRenderer);

        $dbi = DatabaseInterface::getInstanceForTest(new DbiDummy());
        DatabaseInterface::$instance = $dbi;

        $_SESSION[' PMA_token '] = 'mismatch';

        $responseRenderer->setAjax(true);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withParsedBody(['token' => 'token']);
        $response = $middleware->checkTokenRequestParam($request);
        self::assertInstanceOf(ResponseInterface::class, $response);
    }
}
