<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Message;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\AbstractController
 */
class AbstractControllerTest extends AbstractTestCase
{
    public function testCheckParametersWithMissingParameters(): void
    {
        $_REQUEST = [];

        $response = new ResponseRenderer();
        $template = new Template();
        $controller = new class ($response, $template) extends AbstractController {
            /**
             * @psalm-param non-empty-list<non-empty-string> $params
             */
            public function testCheckParameters(array $params): void
            {
                parent::checkParameters($params);
            }
        };

        \PhpMyAdmin\ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = null;

        $message = 'index.php: Missing parameter: param2';
        $message .= MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true);
        $message .= '[br]';
        $expected = $template->render('error/generic', [
            'lang' => 'en',
            'dir' => 'ltr',
            'error_message' => Sanitize::sanitizeMessage($message),
        ]);

        $this->expectOutputString($expected);

        $controller->testCheckParameters(['param1', 'param2']);

        $this->assertSame(400, $response->getHttpResponseCode());
    }

    public function testCheckParametersWithAllParameters(): void
    {
        $_REQUEST = [];

        $response = new ResponseRenderer();
        $controller = new class ($response, new Template()) extends AbstractController {
            /**
             * @psalm-param non-empty-list<non-empty-string> $params
             */
            public function testCheckParameters(array $params): void
            {
                parent::checkParameters($params);
            }
        };

        \PhpMyAdmin\ResponseRenderer::getInstance()->setAjax(false);

        $GLOBALS['param1'] = 'param1';
        $GLOBALS['param2'] = 'param2';

        $this->expectOutputString('');

        $controller->testCheckParameters(['param1', 'param2']);

        $this->assertSame(200, $response->getHttpResponseCode());
    }

    public function testSendErrorResponseWithJson(): void
    {
        $response = new ResponseRenderer();
        $response->setAjax(true);

        $controller = new class ($response, new Template()) extends AbstractController {
            /**
             * @psalm-param int<400,599> $statusCode
             */
            public function testSendErrorResponse(string $message, int $statusCode = 400): void
            {
                parent::sendErrorResponse($message, $statusCode);
            }
        };

        $controller->testSendErrorResponse('Error message.', 404);

        $this->assertSame(404, $response->getHttpResponseCode());
        $this->assertFalse($response->hasSuccessState());
        $this->assertSame('', $response->getHTMLResult());
        $this->assertSame([
            'isErrorResponse' => true,
            'message' => 'Error message.',
        ], $response->getJSONResult());
    }

    public function testSendErrorResponseWithHtml(): void
    {
        $response = new ResponseRenderer();
        $response->setAjax(false);

        $controller = new class ($response, new Template()) extends AbstractController {
            /**
             * @psalm-param int<400,599> $statusCode
             */
            public function testSendErrorResponse(string $message, int $statusCode = 400): void
            {
                parent::sendErrorResponse($message, $statusCode);
            }
        };

        $controller->testSendErrorResponse('Error message.', 404);

        $this->assertSame(404, $response->getHttpResponseCode());
        $this->assertFalse($response->hasSuccessState());
        $this->assertSame(
            Message::error('Error message.')->getDisplay(),
            $response->getHTMLResult()
        );
        $this->assertSame([], $response->getJSONResult());
    }
}
