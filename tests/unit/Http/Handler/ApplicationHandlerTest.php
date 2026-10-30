<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Handler;

use PhpMyAdmin\Application;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Handler\ApplicationHandler;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(ApplicationHandler::class)]
final class ApplicationHandlerTest extends TestCase
{
    public function testHandleReturnsResponse(): void
    {
        $responseRendererMock = $this->createMock(ResponseRenderer::class);
        $responseRendererMock->expects(self::never())->method('response');
        $request = self::createStub(ServerRequest::class);
        $responseStub = new Response(self::createStub(ResponseInterface::class));
        $appMock = $this->createMock(Application::class);
        $appMock->expects(self::once())->method('handle')->with($request)->willReturn($responseStub);
        $handler = new ApplicationHandler($appMock, $responseRendererMock);
        $response = $handler->handle($request);
        self::assertSame($response, $responseStub);
    }

    public function testHandleThrowsExit(): void
    {
        $responseStub = new Response(self::createStub(ResponseInterface::class));
        $responseRendererMock = $this->createMock(ResponseRenderer::class);
        $responseRendererMock->expects(self::once())->method('response')->willReturn($responseStub);
        $request = self::createStub(ServerRequest::class);
        $appMock = $this->createMock(Application::class);
        $appMock->expects(self::once())->method('handle')->with($request)->willThrowException(new ExitException());
        $handler = new ApplicationHandler($appMock, $responseRendererMock);
        $response = $handler->handle($request);
        self::assertSame($response, $responseStub);
    }
}
