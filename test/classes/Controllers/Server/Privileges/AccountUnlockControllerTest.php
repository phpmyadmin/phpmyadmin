<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Privileges;

use PhpMyAdmin\Controllers\Server\Privileges\AccountUnlockController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\MockObject\Stub;

/**
 * @covers \PhpMyAdmin\Controllers\Server\Privileges\AccountUnlockController
 */
class AccountUnlockControllerTest extends AbstractTestCase
{
    /** @var DatabaseInterface&Stub */
    private $dbiStub;

    /** @var ServerRequest&Stub  */
    private $requestStub;

    /** @var ResponseRenderer */
    private $responseRendererStub;

    /** @var AccountUnlockController */
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

        $this->controller = new AccountUnlockController(
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

        $message = Message::success('The account test.user@test.host has been successfully unlocked.');
        self::assertTrue($this->responseRendererStub->isAjax());
        self::assertEquals(200, $this->responseRendererStub->getHttpResponseCode());
        self::assertTrue($this->responseRendererStub->hasSuccessState());
        self::assertEquals(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }

    public function testWithInvalidAccount(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100402);
        $this->dbiStub->method('tryQuery')->willReturn(false);
        $this->dbiStub->method('getError')->willReturn('Invalid account.');

        ($this->controller)($this->requestStub);

        $message = Message::error('Invalid account.');
        self::assertTrue($this->responseRendererStub->isAjax());
        self::assertEquals(400, $this->responseRendererStub->getHttpResponseCode());
        self::assertFalse($this->responseRendererStub->hasSuccessState());
        self::assertEquals(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }

    public function testWithUnsupportedServer(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100401);

        ($this->controller)($this->requestStub);

        $message = Message::error('Account locking is not supported.');
        self::assertTrue($this->responseRendererStub->isAjax());
        self::assertEquals(400, $this->responseRendererStub->getHttpResponseCode());
        self::assertFalse($this->responseRendererStub->hasSuccessState());
        self::assertEquals(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }
}
