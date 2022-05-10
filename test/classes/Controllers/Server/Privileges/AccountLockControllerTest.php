<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Privileges;

use PhpMyAdmin\Controllers\Server\Privileges\AccountLockController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\MockObject\Stub;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Privileges\AccountLockController
 */
class AccountLockControllerTest extends AbstractTestCase
{
    /** @var DatabaseInterface&Stub */
    private $dbiStub;

    /** @var ServerRequest&Stub  */
    private $requestStub;

    /** @var ResponseRenderer */
    private $responseRendererStub;

    /** @var AccountLockController */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $this->dbiStub = $this->createStub(DatabaseInterface::class);
        $this->dbiStub->method('isMariaDB')->willReturn(true);
        $this->dbiStub->method('escapeString')->willReturnArgument(0);

        $this->requestStub = $this->createStub(ServerRequest::class);
        $this->requestStub->method('getParsedBodyParam')->willReturnOnConsecutiveCalls('test.user', 'test.host');

        $this->responseRendererStub = new ResponseRenderer();
        $this->responseRendererStub->setAjax(false);

        $this->controller = new AccountLockController(
            $this->responseRendererStub,
            new Template(),
            new AccountLocking($this->dbiStub)
        );
    }

    public function testWithValidAccount(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100402);
        $this->dbiStub->method('tryQuery')->willReturn(true);

        ($this->controller)($this->requestStub);

        $message = Message::success('The account test.user@test.host has been successfully locked.');
        $this->assertTrue($this->responseRendererStub->isAjax());
        $this->assertEquals(200, $this->responseRendererStub->getHttpResponseCode());
        $this->assertTrue($this->responseRendererStub->hasSuccessState());
        $this->assertEquals(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }

    public function testWithInvalidAccount(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100402);
        $this->dbiStub->method('tryQuery')->willReturn(false);
        $this->dbiStub->method('getError')->willReturn('Invalid account.');

        ($this->controller)($this->requestStub);

        $message = Message::error('Invalid account.');
        $this->assertTrue($this->responseRendererStub->isAjax());
        $this->assertEquals(400, $this->responseRendererStub->getHttpResponseCode());
        $this->assertFalse($this->responseRendererStub->hasSuccessState());
        $this->assertEquals(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }

    public function testWithUnsupportedServer(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100401);

        ($this->controller)($this->requestStub);

        $message = Message::error('Account locking is not supported.');
        $this->assertTrue($this->responseRendererStub->isAjax());
        $this->assertEquals(400, $this->responseRendererStub->getHttpResponseCode());
        $this->assertFalse($this->responseRendererStub->hasSuccessState());
        $this->assertEquals(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }
}
