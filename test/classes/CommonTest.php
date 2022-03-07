<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Common;

/**
 * @covers \PhpMyAdmin\Common
 */
class CommonTest extends AbstractTestCase
{
    /**
     * @param string $php_self  The PHP_SELF value
     * @param string $request   The REQUEST_URI value
     * @param string $path_info The PATH_INFO value
     * @param string $expected  Expected result
     *
     * @dataProvider providerForTestCleanupPathInfo
     */
    public function testCleanupPathInfo(string $php_self, string $request, string $path_info, string $expected): void
    {
        $_SERVER['PHP_SELF'] = $php_self;
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['PATH_INFO'] = $path_info;
        Common::cleanupPathInfo();
        $this->assertEquals($expected, $GLOBALS['PMA_PHP_SELF']);
    }

    public function providerForTestCleanupPathInfo(): array
    {
        return [
            [
                '/phpmyadmin/index.php/; cookieinj=value/',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '/phpmyadmin/index.php/;%20cookieinj=value///',
                '/; cookieinj=value/',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '//example.com/../phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '//example.com/../../.././phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '/page.php/malicouspathinfo?malicouspathinfo',
                'malicouspathinfo',
                '/page.php',
            ],
            [
                '/phpmyadmin/./index.php',
                '/phpmyadmin/./index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '/phpmyadmin/index.php',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
            [
                '',
                '/phpmyadmin/index.php',
                '',
                '/phpmyadmin/index.php',
            ],
        ];
    }

    public function testCheckTokenRequestParam(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Common::checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['test'] = 'test';
        Common::checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertFalse($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'mismatch';
        Common::checkTokenRequestParam();
        $this->assertTrue($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayNotHasKey('test', $_POST);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['token'] = 'token';
        $_POST['test'] = 'test';
        $_SESSION[' PMA_token '] = 'token';
        Common::checkTokenRequestParam();
        $this->assertFalse($GLOBALS['token_mismatch']);
        $this->assertTrue($GLOBALS['token_provided']);
        $this->assertArrayHasKey('test', $_POST);
        $this->assertEquals('test', $_POST['test']);
    }
}
