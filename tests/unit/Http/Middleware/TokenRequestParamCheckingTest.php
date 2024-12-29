<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\TokenRequestParamChecking;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(TokenRequestParamChecking::class)]
final class TokenRequestParamCheckingTest extends TestCase
{
    public function testCheckTokenRequestParam(): void
    {
        $_POST = [];

        $middleware = new TokenRequestParamChecking();

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

        $middleware = new TokenRequestParamChecking();

        $_POST['test'] = 'test';
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/');
        $response = $middleware->checkTokenRequestParam($request);
        self::assertNull($response);
        self::assertArrayNotHasKey('test', $_POST);
    }

    public function testCheckTokenRequestParamWithTokenMismatch(): void
    {
        $middleware = new TokenRequestParamChecking();

        $dbi = new DatabaseInterface(new DbiDummy());
        DatabaseInterface::$instance = $dbi;

        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(true);

        $_SESSION[' PMA_token '] = 'mismatch';
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['token' => 'token', 'ajax_request' => 'true']);
        $response = $middleware->checkTokenRequestParam($request);
        self::assertInstanceOf(ResponseInterface::class, $response);
    }
}
