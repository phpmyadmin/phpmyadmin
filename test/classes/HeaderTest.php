<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\Header;
use ReflectionProperty;
use function define;
use function defined;

/**
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
        parent::defineVersionConstants();
        parent::setTheme();
        parent::setLanguage();
        if (! defined('PMA_IS_WINDOWS')) {
            define('PMA_IS_WINDOWS', false);
        }
        $GLOBALS['server'] = 0;
        $GLOBALS['message'] = 'phpmyadminmessage';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['server'] = 'server';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = '';
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();
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
     * Test for print view
     */
    public function testPrintView(): void
    {
        $header = new Header();
        $header->enablePrintView();
        $this->assertStringContainsString(
            'Print view',
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
            'CommonParams.setAll',
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
     * Test for getCspHeaders
     */
    public function testGetCspHeaders(): void
    {
        global $cfg;
        $cfg['CSPAllow'] = '';

        $header = new Header();
        $headers = $this->callFunction($header, Header::class, 'getCspHeaders', []);

        $this->assertSame([
            'Content-Security-Policy: default-src \'self\' ;script-src \'self\' \'unsafe-inline\''
            . ' \'unsafe-eval\' ;style-src \'self\' \'unsafe-inline\' ;img-src \'self\''
            . ' data:  *.tile.openstreetmap.org;object-src \'none\';',
            'X-Content-Security-Policy: default-src \'self\' ;options inline-script eval-script;'
            . 'referrer no-referrer;img-src \'self\' data:  *.tile.openstreetmap.org;object-src \'none\';',
            'X-WebKit-CSP: default-src \'self\' ;script-src \'self\'  \'unsafe-inline\' \'unsafe-eval\';'
            . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\' ;img-src \'self\''
            . ' data:  *.tile.openstreetmap.org;object-src \'none\';',
        ], $headers);

        $cfg['CSPAllow'] = 'example.com example.net';

        $header = new Header();
        $headers = $this->callFunction($header, Header::class, 'getCspHeaders', []);

        $this->assertSame([
            'Content-Security-Policy: default-src \'self\' example.com example.net;'
            . 'script-src \'self\' \'unsafe-inline\''
            . ' \'unsafe-eval\' example.com example.net;style-src \'self\' \'unsafe-inline\' example.com example.net;'
            . 'img-src \'self\''
            . ' data: example.com example.net *.tile.openstreetmap.org;object-src \'none\';',
            'X-Content-Security-Policy: default-src \'self\' example.com example.net;options inline-script eval-script;'
            . 'referrer no-referrer;img-src \'self\' data: example.com example.net *.tile.openstreetmap.org;'
            . 'object-src \'none\';',
            'X-WebKit-CSP: default-src \'self\' example.com example.net;'
            . 'script-src \'self\' example.com example.net \'unsafe-inline\' \'unsafe-eval\';'
            . 'referrer no-referrer;style-src \'self\' \'unsafe-inline\' ;img-src \'self\''
            . ' data: example.com example.net *.tile.openstreetmap.org;object-src \'none\';',
        ], $headers);
    }
}
