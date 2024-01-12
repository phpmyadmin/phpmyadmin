<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
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

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $GLOBALS['lang'] = 'en';
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetAjax(): void
    {
        $_REQUEST = [];
        $response = ResponseRenderer::getInstance();
        $header = $response->getHeader();
        $footer = (new ReflectionProperty(ResponseRenderer::class, 'footer'))->getValue($response);
        self::assertInstanceOf(Footer::class, $footer);
        $headerIsAjax = new ReflectionProperty(Header::class, 'isAjax');
        $footerIsAjax = new ReflectionProperty(Footer::class, 'isAjax');

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
