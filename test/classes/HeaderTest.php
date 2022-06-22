<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\Header;
use ReflectionProperty;

use function gmdate;

use const DATE_RFC1123;

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
        $this->assertEquals(
            '',
            $header->getDisplay()
        );
    }

    /**
     * Test for enable
     */
    public function testEnable(): void
    {
        $GLOBALS['server'] = 0;
        $header = new Header();
        $this->assertStringContainsString(
            '<title>phpMyAdmin</title>',
            $header->getDisplay()
        );
    }

    /**
     * Test for Set BodyId
     */
    public function testSetBodyId(): void
    {
        $header = new Header();
        $header->setBodyId('PMA_header_id');
        $this->assertStringContainsString(
            'PMA_header_id',
            $header->getDisplay()
        );
    }

    /**
     * Test for Get JsParams
     */
    public function testGetJsParams(): void
    {
        $header = new Header();
        $this->assertArrayHasKey(
            'common_query',
            $header->getJsParams()
        );
    }

    /**
     * Test for Get JsParamsCode
     */
    public function testGetJsParamsCode(): void
    {
        $header = new Header();
        $this->assertStringContainsString(
            'window.CommonParams.setAll',
            $header->getJsParamsCode()
        );
    }

    /**
     * Test for Get Message
     */
    public function testGetMessage(): void
    {
        $header = new Header();
        $this->assertStringContainsString(
            'phpmyadminmessage',
            $header->getMessage()
        );
    }

    /**
     * Test for Disable Warnings
     */
    public function testDisableWarnings(): void
    {
        $reflection = new ReflectionProperty(Header::class, 'warningsEnabled');
        $reflection->setAccessible(true);

        $header = new Header();
        $header->disableWarnings();

        $this->assertFalse($reflection->getValue($header));
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
        $header = new Header();
        $date = (string) gmdate(DATE_RFC1123);

        $GLOBALS['cfg']['AllowThirdPartyFraming'] = $frameOptions;
        $GLOBALS['cfg']['CSPAllow'] = $cspAllow;
        $GLOBALS['cfg']['CaptchaLoginPrivateKey'] = $privateKey;
        $GLOBALS['cfg']['CaptchaLoginPublicKey'] = $publicKey;
        $GLOBALS['cfg']['CaptchaCsp'] = $captchaCsp;

        $expected = [
            'X-Frame-Options' => $expectedFrameOptions,
            'Referrer-Policy' => 'no-referrer',
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
        $this->assertSame($expected, $headers);
    }

    public function providerForTestGetHttpHeaders(): array
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
}
