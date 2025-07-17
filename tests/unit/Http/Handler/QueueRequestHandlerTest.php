<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Handler;

use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Handler\QueueRequestHandler;
use PhpMyAdmin\Tests\Http\Handler\Fixtures\FallbackHandler;
use PhpMyAdmin\Tests\Http\Handler\Fixtures\Middleware\EarlyReturn;
use PhpMyAdmin\Tests\Http\Handler\Fixtures\Middleware\First;
use PhpMyAdmin\Tests\Http\Handler\Fixtures\Middleware\Second;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(QueueRequestHandler::class)]
final class QueueRequestHandlerTest extends TestCase
{
    public function testQueue(): void
    {
        $responseFactory = ResponseFactory::create();
        $container = self::createMock(ContainerInterface::class);
        $container->expects(self::exactly(3))->method('get')->willReturnMap([
            [EarlyReturn::class, new EarlyReturn($responseFactory)],
            [First::class, new First()],
            [Second::class, new Second()],
        ]);

        $handler = new QueueRequestHandler($container, new FallbackHandler($responseFactory));
        $handler->add(First::class);
        $handler->add(EarlyReturn::class);
        $handler->add(Second::class);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withAttribute('attribute', ['Initial']);
        $response = $handler->handle($request);
        $response->getBody()->write('Last');
        self::assertSame(
            'Initial, First before, Second before, Fallback, Second after, First after, Last',
            (string) $response->getBody(),
        );
    }

    public function testQueueWithEarlyReturn(): void
    {
        $responseFactory = ResponseFactory::create();
        $container = self::createMock(ContainerInterface::class);
        $container->expects(self::exactly(2))->method('get')->willReturnMap([
            [EarlyReturn::class, new EarlyReturn($responseFactory)],
            [First::class, new First()],
        ]);

        $handler = new QueueRequestHandler($container, new FallbackHandler($responseFactory));
        $handler->add(First::class);
        $handler->add(EarlyReturn::class);
        $handler->add(Second::class);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withAttribute('attribute', ['Initial'])
            ->withAttribute('early', true);
        $response = $handler->handle($request);
        $response->getBody()->write('Last');
        self::assertSame('Initial, First before, Early, First after, Last', (string) $response->getBody());
    }
}
