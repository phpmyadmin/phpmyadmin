<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Console;
use PhpMyAdmin\Core;
use PhpMyAdmin\Header;
use ReflectionProperty;

use function gmdate;

use const DATE_RFC1123;
use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Header
 * @group medium
 */
class HeaderTest extends AbstractTestCase
{
    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();
        parent::setLanguage();

        $GLOBALS['server'] = 0;
        $GLOBALS['message'] = 'phpmyadminmessage';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['server'] = 'server';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        parent::setGlobalConfig();
        $GLOBALS['cfg']['Servers'] = [];
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['verbose'] = 'verbose host';
        $GLOBALS['cfg']['Server']['pmadb'] = '';
        $GLOBALS['cfg']['Server']['user'] = '';
        $GLOBALS['cfg']['Server']['auth_type'] = 'cookie';
    }

    /**
     * Test for disable
     */
    public function testDisable(): void
    {
        $header = new Header();
        $header->disable();
        self::assertSame('', $header->getDisplay());
    }

    /**
     * Test for enable
     */
    public function testEnable(): void
    {
        $GLOBALS['server'] = 0;
        $header = new Header();
        self::assertStringContainsString('<title>phpMyAdmin</title>', $header->getDisplay());
    }

    /**
     * Test for Set BodyId
     */
    public function testSetBodyId(): void
    {
        $header = new Header();
        $header->setBodyId('PMA_header_id');
        self::assertStringContainsString('PMA_header_id', $header->getDisplay());
    }

    /**
     * Test for Get JsParams
     */
    public function testGetJsParams(): void
    {
        $header = new Header();
        self::assertArrayHasKey('common_query', $header->getJsParams());
    }

    /**
     * Test for Get JsParamsCode
     */
    public function testGetJsParamsCode(): void
    {
        $header = new Header();
        self::assertStringContainsString('CommonParams.setAll', $header->getJsParamsCode());
    }

    /**
     * Test for Get Message
     */
    public function testGetMessage(): void
    {
        $header = new Header();
        self::assertStringContainsString('phpmyadminmessage', $header->getMessage());
    }

    /**
     * Test for Disable Warnings
     */
    public function testDisableWarnings(): void
    {
        $reflection = new ReflectionProperty(Header::class, 'warningsEnabled');
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        $header = new Header();
        $header->disableWarnings();

        self::assertFalse($reflection->getValue($header));
    }

    /**
     * @param string|bool $frameOptions
     *
     * @covers \PhpMyAdmin\Core::getNoCacheHeaders
     * @dataProvider providerForTestGetHttpHeaders
     */
    public function testGetHttpHeaders(
        $frameOptions,
        string $cspAllow,
        string $privateKey,
        string $publicKey,
        string $captchaCsp,
        ?string $expectedFrameOptions,
        string $expectedCsp,
        string $expectedXCsp,
        string $expectedWebKitCsp
    ): void {
        global $cfg;

        $header = new Header();
        $date = (string) gmdate(DATE_RFC1123);

        $cfg['AllowThirdPartyFraming'] = $frameOptions;
        $cfg['CSPAllow'] = $cspAllow;
        $cfg['CaptchaLoginPrivateKey'] = $privateKey;
        $cfg['CaptchaLoginPublicKey'] = $publicKey;
        $cfg['CaptchaCsp'] = $captchaCsp;

        $expected = [
            'X-Frame-Options' => $expectedFrameOptions,
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' => $expectedCsp,
            'X-Content-Security-Policy' => $expectedXCsp,
            'X-WebKit-CSP' => $expectedWebKitCsp,
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Expires' => $date,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Pragma' => 'no-cache',
            'Last-Modified' => $date,
            'Content-Type' => 'text/html; charset=utf-8',
        ];
        if ($expectedFrameOptions === null) {
            unset($expected['X-Frame-Options']);
        }

        $headers = $this->callFunction($header, Header::class, 'getHttpHeaders', []);
        self::assertSame($expected, $headers);
    }

    public static function providerForTestGetHttpHeaders(): array
    {
        return [
            [
                '1',
                '',
                '',
                '',
                '',
                'DENY',
                'default-src \'self\' ;script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' ;'
                    . 'style-src \'self\' \'unsafe-inline\' ;img-src \'self\' data:  *.tile.openstreetmap.org;'
                    . 'object-src \'none\';',
                'default-src \'self\' ;options inline-script eval-script;referrer no-referrer;'
                    . 'img-src \'self\' data:  *.tile.openstreetmap.org;object-src \'none\';',
                'default-src \'self\' ;script-src \'self\'  \'unsafe-inline\' \'unsafe-eval\';'
                    . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\' ;'
                    . 'img-src \'self\' data:  *.tile.openstreetmap.org;object-src \'none\';',
            ],
            [
                'SameOrigin',
                'example.com example.net',
                'PrivateKey',
                'PublicKey',
                'captcha.tld csp.tld',
                'SAMEORIGIN',
                'default-src \'self\'  captcha.tld csp.tld example.com example.net;'
                    . 'script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'  '
                    . 'captcha.tld csp.tld example.com example.net;'
                    . 'style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld example.com example.net;'
                    . 'img-src \'self\' data: example.com example.net *.tile.openstreetmap.org captcha.tld csp.tld ;'
                    . 'object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld example.com example.net;'
                    . 'options inline-script eval-script;referrer no-referrer;img-src \'self\' data: example.com '
                    . 'example.net *.tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld example.com example.net;script-src \'self\'  '
                    . 'captcha.tld csp.tld example.com example.net \'unsafe-inline\' \'unsafe-eval\';'
                    . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld ;'
                    . 'img-src \'self\' data: example.com example.net *.tile.openstreetmap.org captcha.tld csp.tld ;'
                    . 'object-src \'none\';',
            ],
            [
                true,
                '',
                'PrivateKey',
                'PublicKey',
                'captcha.tld csp.tld',
                null,
                'default-src \'self\'  captcha.tld csp.tld ;'
                    . 'script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'  captcha.tld csp.tld ;'
                    . 'style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld ;'
                    . 'img-src \'self\' data:  *.tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld ;'
                    . 'options inline-script eval-script;referrer no-referrer;'
                    . 'img-src \'self\' data:  *.tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
                'default-src \'self\'  captcha.tld csp.tld ;'
                    . 'script-src \'self\'  captcha.tld csp.tld  \'unsafe-inline\' \'unsafe-eval\';'
                    . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\'  captcha.tld csp.tld ;'
                    . 'img-src \'self\' data:  *.tile.openstreetmap.org captcha.tld csp.tld ;object-src \'none\';',
            ],
        ];
    }

    public function testSetAjax(): void
    {
        $header = new Header();
        $consoleReflection = new ReflectionProperty(Header::class, 'console');
        if (PHP_VERSION_ID < 80100) {
            $consoleReflection->setAccessible(true);
        }

        $console = $consoleReflection->getValue($header);
        self::assertInstanceOf(Console::class, $console);
        $isAjax = new ReflectionProperty(Header::class, 'isAjax');
        if (PHP_VERSION_ID < 80100) {
            $isAjax->setAccessible(true);
        }

        $consoleIsAjax = new ReflectionProperty(Console::class, 'isAjax');
        if (PHP_VERSION_ID < 80100) {
            $consoleIsAjax->setAccessible(true);
        }

        self::assertFalse($isAjax->getValue($header));
        self::assertFalse($consoleIsAjax->getValue($console));
        $header->setAjax(true);
        self::assertTrue($isAjax->getValue($header));
        self::assertTrue($consoleIsAjax->getValue($console));
        $header->setAjax(false);
        self::assertFalse($isAjax->getValue($header));
        self::assertFalse($consoleIsAjax->getValue($console));
    }
}
