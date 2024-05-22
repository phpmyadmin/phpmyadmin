<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Header;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Scripts;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testSetAjax(): void
    {
        $_REQUEST = [];
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $response = ResponseRenderer::getInstance();
        self::assertFalse($response->isAjax());
        $response->setAjax(true);
        self::assertTrue($response->isAjax());
        $response->setAjax(false);
        self::assertFalse($response->isAjax());
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

    public function testCheckParametersWithMissingParameters(): void
    {
        $_REQUEST = [];
        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = null;

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);

        $message = 'Missing parameter: param2';
        $message .= MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true);
        $message .= '[br]';
        $expected = Message::error($message)->getDisplay();

        self::assertFalse($responseRenderer->checkParameters(['param1', 'param2']));
        $response = $responseRenderer->response();

        self::assertStringContainsString($expected, (string) $response->getBody());
        self::assertSame(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
    }

    public function testCheckParametersWithAllParameters(): void
    {
        $_REQUEST = [];
        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = 'param2';

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);

        $message = 'Missing parameter: param2';
        $message .= MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true);
        $message .= '[br]';
        $expected = Message::error($message)->getDisplay();

        self::assertTrue($responseRenderer->checkParameters(['param1', 'param2']));
        $response = $responseRenderer->response();

        self::assertStringNotContainsString($expected, (string) $response->getBody());
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
    }
}
