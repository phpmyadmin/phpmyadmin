<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Handler;

use PhpMyAdmin\Application;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Handler\ApplicationHandler;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PHPUnit\Framework\Attributes\BackupStaticProperties;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ReflectionProperty;

#[CoversClass(ApplicationHandler::class)]
final class ApplicationHandlerTest extends TestCase
{
    #[BackupStaticProperties(true)]
    public function testHandleReturnsResponse(): void
    {
        $responseRendererMock = $this->createMock(ResponseRenderer::class);
        $responseRendererMock->expects($this->never())->method('response');
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, $responseRendererMock);
        $request = $this->createStub(ServerRequest::class);
        $responseStub = new Response($this->createStub(ResponseInterface::class));
        $appMock = $this->createMock(Application::class);
        $appMock->expects($this->once())->method('handle')->with($request)->willReturn($responseStub);
        $handler = new ApplicationHandler($appMock);
        $response = $handler->handle($request);
        $this->assertSame($response, $responseStub);
    }

    #[BackupStaticProperties(true)]
    public function testHandleThrowsExit(): void
    {
        $responseStub = new Response($this->createStub(ResponseInterface::class));
        $responseRendererMock = $this->createMock(ResponseRenderer::class);
        $responseRendererMock->expects($this->once())->method('response')->willReturn($responseStub);
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, $responseRendererMock);
        $request = $this->createStub(ServerRequest::class);
        $appMock = $this->createMock(Application::class);
        $appMock->expects($this->once())->method('handle')->with($request)->willThrowException(new ExitException());
        $handler = new ApplicationHandler($appMock);
        $response = $handler->handle($request);
        $this->assertSame($response, $responseStub);
    }

    #[BackupStaticProperties(true)]
    public function testHandleReturnsNull(): void
    {
        $responseStub = new Response($this->createStub(ResponseInterface::class));
        $responseRendererMock = $this->createMock(ResponseRenderer::class);
        $responseRendererMock->expects($this->once())->method('response')->willReturn($responseStub);
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, $responseRendererMock);
        $request = $this->createStub(ServerRequest::class);
        $appMock = $this->createMock(Application::class);
        $appMock->expects($this->once())->method('handle')->with($request)->willReturn(null);
        $handler = new ApplicationHandler($appMock);
        $response = $handler->handle($request);
        $this->assertSame($response, $responseStub);
    }
}
