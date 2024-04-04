<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Header;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Scripts;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;

use function array_column;

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
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
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
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
    }

    public function testAddScriptFiles(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $response = ResponseRenderer::getInstance();
        $header = $response->getHeader();
        $headerScripts = (new ReflectionProperty(Header::class, 'scripts'))->getValue($header);
        self::assertInstanceOf(Scripts::class, $headerScripts);
        $files = array_column($headerScripts->getFiles(), 'name');
        self::assertNotContains('server/privileges.js', $files);
        self::assertNotContains('vendor/zxcvbn-ts.js', $files);

        $response->addScriptFiles(['server/privileges.js', 'vendor/zxcvbn-ts.js']);

        $files = array_column($headerScripts->getFiles(), 'name');
        self::assertContains('server/privileges.js', $files);
        self::assertContains('vendor/zxcvbn-ts.js', $files);
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
    }
}
