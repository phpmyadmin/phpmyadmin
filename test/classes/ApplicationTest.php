<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Application;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Application::class)]
final class ApplicationTest extends AbstractTestCase
{
    public function testCheckTokenRequestParam(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Application::checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['test'] = 'test';
        Application::checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'mismatch';
        Application::checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'token';
        Application::checkTokenRequestParam();
        $this->assertFalse($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayHasKey('test', $_POST);
        $this->assertEquals('test', $_POST['test']);
    }
}
