<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Footer;
use PhpMyAdmin\Header;
use PhpMyAdmin\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;

#[CoversClass(ResponseRenderer::class)]
class ResponseRendererTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetAjax(): void
    {
        $_REQUEST = [];
        $response = ResponseRenderer::getInstance();
        $header = $response->getHeader();
        $footer = (new ReflectionProperty(ResponseRenderer::class, 'footer'))->getValue($response);
        $this->assertInstanceOf(Footer::class, $footer);
        $headerIsAjax = new ReflectionProperty(Header::class, 'isAjax');
        $footerIsAjax = new ReflectionProperty(Footer::class, 'isAjax');

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
