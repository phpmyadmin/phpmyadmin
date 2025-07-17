<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Header;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Scripts;
use PhpMyAdmin\Template;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function array_column;
use function json_decode;

#[CoversClass(ResponseRenderer::class)]
final class ResponseRendererTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        Current::$lang = 'en';
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

    public function testMissingParameterError(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);

        $message = 'Missing parameter: param_name';
        $message .= MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true);
        $message .= '[br]';
        $expected = Message::error($message)->getDisplay();

        $response = $responseRenderer->missingParameterError('param_name');

        self::assertStringContainsString($expected, (string) $response->getBody());
        self::assertSame(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
    }

    public function testHtmlResponse(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);

        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Current::$server = 0;
        Current::$message = null;

        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);
        $responseRenderer->addHTML('<div>TEST</div>');

        $response = $responseRenderer->response();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $header = $responseRenderer->getHeader();
        self::assertSame(
            (new Template(new Config()))->render('base', [
                'header' => [
                    'lang' => 'en',
                    'allow_third_party_framing' => false,
                    'theme_path' => '',
                    'server' => 0,
                    'title' => 'phpMyAdmin',
                    'scripts' => $header->getScripts()->getDisplay(),
                    'body_id' => '',
                    'navigation' => '',
                    'custom_header' => '',
                    'load_user_preferences' => '',
                    'show_hint' => true,
                    'is_warnings_enabled' => true,
                    'is_menu_enabled' => true,
                    'is_logged_in' => true,
                    'menu' => '',
                    'console' => $header->getConsole()->getDisplay(),
                    'messages' => '',
                    'theme_color_mode' => 'light',
                    'theme_color_modes' => ['light'],
                    'theme_id' => '',
                    'current_user' => ['pma_test', 'localhost'],
                    'is_mariadb' => false,
                ],
                'content' => '<div>TEST</div>',
                'footer' => [
                    'is_minimal' => false,
                    'self_url' => 'index.php?route=%2F&server=0&lang=en',
                    'error_messages' => '',
                    'scripts' => <<<'HTML'

                        <script data-cfasync="false">
                        // <![CDATA[
                        window.Console.debugSqlInfo = 'false';

                        // ]]>
                        </script>

                        HTML,
                    'is_demo' => false,
                    'git_revision_info' => [],
                    'footer' => '',
                ],
            ]),
            (string) $response->getBody(),
        );

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
    }

    public function testJsonResponse(): void
    {
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);

        $_SERVER['SCRIPT_NAME'] = 'index.php';
        Current::$server = 0;
        Current::$message = null;

        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(true);
        $responseRenderer->addJSON('message', 'test message');
        $responseRenderer->addJSON('test', 'test');

        $response = $responseRenderer->response();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('application/json; charset=UTF-8', $response->getHeaderLine('Content-Type'));
        $body = (string) $response->getBody();
        self::assertJson($body);
        $header = $responseRenderer->getHeader();
        self::assertEquals(
            [
                'message' => 'test message',
                'test' => 'test',
                'success' => true,
                'title' => '<title>phpMyAdmin</title>',
                'menu' => $header->getMenu()->getDisplay(),
                'scripts' => $header->getScripts()->getFiles(),
                'selflink' => 'index.php?route=%2F&server=0&lang=en',
                'displayMessage' => '',
                'debug' => "'false'",
                'promptPhpErrors' => false,
                'reloadQuerywindow' => ['db' => '', 'table' => '', 'sql_query' => ''],
                'params' => $header->getJsParams(),
            ],
            json_decode($body, true),
        );

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
    }
}
