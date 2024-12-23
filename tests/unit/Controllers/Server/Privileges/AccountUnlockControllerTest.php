<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server\Privileges;

use PhpMyAdmin\Controllers\Server\Privileges\AccountUnlockController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;

#[CoversClass(AccountUnlockController::class)]
class AccountUnlockControllerTest extends AbstractTestCase
{
    private DatabaseInterface&Stub $dbiStub;

    private ServerRequest $requestStub;

    private ResponseRenderer $responseRendererStub;

    private AccountUnlockController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $this->dbiStub = self::createStub(DatabaseInterface::class);
        $this->dbiStub->method('isMariaDB')->willReturn(true);

        $this->requestStub = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'ajax_request' => 'true',
                'username' => 'test.user',
                'hostname' => 'test.host',
            ]);

        $this->responseRendererStub = new ResponseRenderer();

        $this->controller = new AccountUnlockController(
            $this->responseRendererStub,
            new AccountLocking($this->dbiStub),
        );
    }

    public function testWithValidAccount(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100402);
        $this->dbiStub->method('tryQuery')->willReturn(self::createStub(DummyResult::class));

        ($this->controller)($this->requestStub);

        $message = Message::success('The account test.user@test.host has been successfully unlocked.');
        self::assertSame(200, $this->responseRendererStub->getResponse()->getStatusCode());
        self::assertTrue($this->responseRendererStub->hasSuccessState());
        self::assertSame(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }

    public function testWithInvalidAccount(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100402);
        $this->dbiStub->method('tryQuery')->willReturn(false);
        $this->dbiStub->method('getError')->willReturn('Invalid account.');

        ($this->controller)($this->requestStub);

        $message = Message::error('Invalid account.');
        self::assertSame(400, $this->responseRendererStub->getResponse()->getStatusCode());
        self::assertFalse($this->responseRendererStub->hasSuccessState());
        self::assertSame(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }

    public function testWithUnsupportedServer(): void
    {
        $this->dbiStub->method('getVersion')->willReturn(100401);

        ($this->controller)($this->requestStub);

        $message = Message::error('Account locking is not supported.');
        self::assertSame(400, $this->responseRendererStub->getResponse()->getStatusCode());
        self::assertFalse($this->responseRendererStub->hasSuccessState());
        self::assertSame(['message' => $message->getDisplay()], $this->responseRendererStub->getJSONResult());
    }
}
