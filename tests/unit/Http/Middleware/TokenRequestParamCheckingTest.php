<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Http\Middleware\TokenRequestParamChecking;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenRequestParamChecking::class)]
final class TokenRequestParamCheckingTest extends TestCase
{
    public function testCheckTokenRequestParam(): void
    {
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        $middleware = new TokenRequestParamChecking();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'token';
        $middleware->checkTokenRequestParam();
        self::assertFalse($GLOBALS['token_mismatch']);
        self::assertTrue($GLOBALS['token_provided']);
        self::assertArrayHasKey('test', $_POST);
        self::assertSame('test', $_POST['test']);
    }

    public function testCheckTokenRequestParamWithGetMethod(): void
    {
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        $middleware = new TokenRequestParamChecking();

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $middleware->checkTokenRequestParam();
        self::assertTrue($GLOBALS['token_mismatch']);
        self::assertFalse($GLOBALS['token_provided']);
    }

    public function testCheckTokenRequestParamWithoutToken(): void
    {
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        $middleware = new TokenRequestParamChecking();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['test'] = 'test';
        $middleware->checkTokenRequestParam();
        self::assertTrue($GLOBALS['token_mismatch']);
        self::assertFalse($GLOBALS['token_provided']);
        self::assertArrayNotHasKey('test', $_POST);
    }

    public function testCheckTokenRequestParamWithTokenMismatch(): void
    {
        $_REQUEST = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        $middleware = new TokenRequestParamChecking();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'mismatch';
        $middleware->checkTokenRequestParam();
        self::assertTrue($GLOBALS['token_mismatch']);
        self::assertTrue($GLOBALS['token_provided']);
        self::assertArrayNotHasKey('test', $_POST);
    }
}
