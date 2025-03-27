<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Footer;
use PhpMyAdmin\Header;
use PhpMyAdmin\ResponseRenderer;
use ReflectionProperty;

/**
 * @covers \PhpMyAdmin\ResponseRenderer
 */
class ResponseRendererTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetAjax(): void
    {
        $_REQUEST = [];
        $response = ResponseRenderer::getInstance();
        $header = $response->getHeader();
        $footerReflection = new ReflectionProperty(ResponseRenderer::class, 'footer');
        $footerReflection->setAccessible(true);
        $footer = $footerReflection->getValue($response);
        self::assertInstanceOf(Footer::class, $footer);
        $headerIsAjax = new ReflectionProperty(Header::class, 'isAjax');
        $headerIsAjax->setAccessible(true);
        $footerIsAjax = new ReflectionProperty(Footer::class, 'isAjax');
        $footerIsAjax->setAccessible(true);

        self::assertFalse($response->isAjax());
        self::assertFalse($headerIsAjax->getValue($header));
        self::assertFalse($footerIsAjax->getValue($footer));

        $response->setAjax(true);
        self::assertTrue($response->isAjax());
        self::assertTrue($headerIsAjax->getValue($header));
        self::assertTrue($footerIsAjax->getValue($footer));

        $response->setAjax(false);
        self::assertFalse($response->isAjax());
        self::assertFalse($headerIsAjax->getValue($header));
        self::assertFalse($footerIsAjax->getValue($footer));
    }
}
