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
        $this->assertFalse($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayHasKey('test', $_POST);
        $this->assertEquals('test', $_POST['test']);
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
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);
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
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);
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
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);
    }
}
