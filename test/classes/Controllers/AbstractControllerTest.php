<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\MySQLDocumentation;
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
            /** @psalm-param non-empty-list<non-empty-string> $params */
            public function testCheckParameters(array $params): bool
            {
                return parent::checkParameters($params);
            }
        };

        \PhpMyAdmin\ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = null;

        $message = 'Missing parameter: param2';
        $message .= MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true);
        $message .= '[br]';
        $expected = Message::error($message)->getDisplay();

        $this->assertFalse($controller->testCheckParameters(['param1', 'param2']));
        $this->assertSame($expected, $response->getHTMLResult());
        $this->assertSame(400, $response->getResponse()->getStatusCode());
    }

    public function testCheckParametersWithAllParameters(): void
    {
        $_REQUEST = [];

        $response = new ResponseRenderer();
        $controller = new class ($response, new Template()) extends AbstractController {
            /** @psalm-param non-empty-list<non-empty-string> $params */
            public function testCheckParameters(array $params): bool
            {
                return parent::checkParameters($params);
            }
        };

        \PhpMyAdmin\ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = 'param2';

        $this->assertTrue($controller->testCheckParameters(['param1', 'param2']));
        $this->assertSame(200, $response->getResponse()->getStatusCode());
    }

    public function testSendErrorResponseWithJson(): void
    {
        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new class ($response, new Template()) extends AbstractController {
            /** @psalm-param StatusCodeInterface::STATUS_* $statusCode */
            public function testSendErrorResponse(string $message, int $statusCode = 400): void
            {
                parent::sendErrorResponse($message, $statusCode);
            }
        };

        $controller->testSendErrorResponse('Error message.', 404);

        $this->assertSame(404, $response->getResponse()->getStatusCode());
        $this->assertFalse($response->hasSuccessState());
        $this->assertSame('', $response->getHTMLResult());
        $this->assertSame(['isErrorResponse' => true, 'message' => 'Error message.'], $response->getJSONResult());
    }

    public function testSendErrorResponseWithHtml(): void
    {
        $response = new ResponseRenderer();
        $response->setAjax(false);

        $controller = new class ($response, new Template()) extends AbstractController {
            /** @psalm-param StatusCodeInterface::STATUS_* $statusCode */
            public function testSendErrorResponse(string $message, int $statusCode = 400): void
            {
                parent::sendErrorResponse($message, $statusCode);
            }
        };

        $controller->testSendErrorResponse('Error message.', 404);

        $this->assertSame(404, $response->getResponse()->getStatusCode());
        $this->assertFalse($response->hasSuccessState());
        $this->assertSame(
            Message::error('Error message.')->getDisplay(),
            $response->getHTMLResult(),
        );
        $this->assertSame([], $response->getJSONResult());
    }
}
