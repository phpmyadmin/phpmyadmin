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
use ReflectionProperty;

#[CoversClass(ApplicationHandler::class)]
final class ApplicationHandlerTest extends TestCase
{
    public function testHandleReturnsResponse(): void
    {
        $responseRendererMock = self::createMock(ResponseRenderer::class);
        $responseRendererMock->expects(self::never())->method('response');
        $reflectionProperty = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $reflectionProperty->setValue(null, $responseRendererMock);
        $request = self::createStub(ServerRequest::class);
        $responseStub = new Response(self::createStub(ResponseInterface::class));
        $appMock = self::createMock(Application::class);
        $appMock->expects(self::once())->method('handle')->with($request)->willReturn($responseStub);
        $handler = new ApplicationHandler($appMock);
        $response = $handler->handle($request);
        self::assertSame($response, $responseStub);
        $reflectionProperty->setValue(null, null);
    }

    public function testHandleThrowsExit(): void
    {
        $responseStub = new Response(self::createStub(ResponseInterface::class));
        $responseRendererMock = self::createMock(ResponseRenderer::class);
        $responseRendererMock->expects(self::once())->method('response')->willReturn($responseStub);
        $reflectionProperty = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $reflectionProperty->setValue(null, $responseRendererMock);
        $request = self::createStub(ServerRequest::class);
        $appMock = self::createMock(Application::class);
        $appMock->expects(self::once())->method('handle')->with($request)->willThrowException(new ExitException());
        $handler = new ApplicationHandler($appMock);
        $response = $handler->handle($request);
        self::assertSame($response, $responseStub);
        $reflectionProperty->setValue(null, null);
    }
}
