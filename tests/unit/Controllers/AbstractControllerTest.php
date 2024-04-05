<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractController::class)]
class AbstractControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testCheckParametersWithMissingParameters(): void
    {
        $_REQUEST = [];

        $response = new ResponseRenderer();
        $template = new Template();
        $controller = new class ($response, $template) extends AbstractController {
            public function __invoke(ServerRequest $request): Response|null
            {
                return null;
            }

            /** @psalm-param non-empty-list<non-empty-string> $params */
            public function testCheckParameters(array $params): bool
            {
                return $this->checkParameters($params);
            }
        };

        \PhpMyAdmin\ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = null;

        $message = 'Missing parameter: param2';
        $message .= MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true);
        $message .= '[br]';
        $expected = Message::error($message)->getDisplay();

        self::assertFalse($controller->testCheckParameters(['param1', 'param2']));
        self::assertSame($expected, $response->getHTMLResult());
        self::assertSame(400, $response->getResponse()->getStatusCode());
    }

    public function testCheckParametersWithAllParameters(): void
    {
        $_REQUEST = [];

        $response = new ResponseRenderer();
        $controller = new class ($response, new Template()) extends AbstractController {
            public function __invoke(ServerRequest $request): Response|null
            {
                return null;
            }

            /** @psalm-param non-empty-list<non-empty-string> $params */
            public function testCheckParameters(array $params): bool
            {
                return $this->checkParameters($params);
            }
        };

        \PhpMyAdmin\ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = 'param2';

        self::assertTrue($controller->testCheckParameters(['param1', 'param2']));
        self::assertSame(200, $response->getResponse()->getStatusCode());
    }
}
