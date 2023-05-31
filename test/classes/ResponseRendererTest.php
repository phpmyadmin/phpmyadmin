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
        $this->assertInstanceOf(Footer::class, $footer);
        $headerIsAjax = new ReflectionProperty(Header::class, 'isAjax');
        $headerIsAjax->setAccessible(true);
        $footerIsAjax = new ReflectionProperty(Footer::class, 'isAjax');
        $footerIsAjax->setAccessible(true);

        $this->assertFalse($response->isAjax());
        $this->assertFalse($headerIsAjax->getValue($header));
        $this->assertFalse($footerIsAjax->getValue($footer));

        $response->setAjax(true);
        $this->assertTrue($response->isAjax());
        $this->assertTrue($headerIsAjax->getValue($header));
        $this->assertTrue($footerIsAjax->getValue($footer));

        $response->setAjax(false);
        $this->assertFalse($response->isAjax());
        $this->assertFalse($headerIsAjax->getValue($header));
        $this->assertFalse($footerIsAjax->getValue($footer));
    }
}
