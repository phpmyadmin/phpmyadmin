<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Handler;

use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Handler\QueueRequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use function implode;

#[CoversClass(QueueRequestHandler::class)]
final class QueueRequestHandlerTest extends TestCase
{
    public function testQueue(): void
    {
        $handler = new QueueRequestHandler($this->getFallbackHandler());
        $handler->add($this->getFirstMiddleware());
        $handler->add($this->getEarlyReturnMiddleware());
        $handler->add($this->getSecondMiddleware());
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withAttribute('attribute', ['Initial']);
        $response = $handler->handle($request);
        $response->getBody()->write('Last');
        $this->assertSame(
            'Initial, First before, Second before, Fallback, Second after, First after, Last',
            (string) $response->getBody(),
        );
    }

    public function testQueueWithEarlyReturn(): void
    {
        $handler = new QueueRequestHandler($this->getFallbackHandler());
        $handler->add($this->getFirstMiddleware());
        $handler->add($this->getEarlyReturnMiddleware());
        $handler->add($this->getSecondMiddleware());
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withAttribute('attribute', ['Initial'])
            ->withAttribute('early', true);
        $response = $handler->handle($request);
        $response->getBody()->write('Last');
        $this->assertSame('Initial, First before, Early, First after, Last', (string) $response->getBody());
    }

    private function getFallbackHandler(): RequestHandler
    {
        return new class implements RequestHandler {
            public function handle(ServerRequest $request): Response
            {
                /** @var string[] $attribute */
                $attribute = $request->getAttribute('attribute');
                $response = ResponseFactory::create()->createResponse();
                $response->getBody()->write(implode(', ', $attribute) . ', ');
                $response->getBody()->write('Fallback, ');

                return $response;
            }
        };
    }

    private function getFirstMiddleware(): Middleware
    {
        return new class implements Middleware {
            public function process(ServerRequest $request, RequestHandler $handler): Response
            {
                /** @var string[] $attribute */
                $attribute = $request->getAttribute('attribute');
                $attribute[] = 'First before';
                $response = $handler->handle($request->withAttribute('attribute', $attribute));
                $response->getBody()->write('First after, ');

                return $response;
            }
        };
    }

    private function getSecondMiddleware(): Middleware
    {
        return new class implements Middleware {
            public function process(ServerRequest $request, RequestHandler $handler): Response
            {
                /** @var string[] $attribute */
                $attribute = $request->getAttribute('attribute');
                $attribute[] = 'Second before';
                $response = $handler->handle($request->withAttribute('attribute', $attribute));
                $response->getBody()->write('Second after, ');

                return $response;
            }
        };
    }

    private function getEarlyReturnMiddleware(): Middleware
    {
        return new class implements Middleware {
            public function process(ServerRequest $request, RequestHandler $handler): Response
            {
                if ($request->getAttribute('early') !== true) {
                    return $handler->handle($request);
                }

                 /** @var string[] $attribute */
                $attribute = $request->getAttribute('attribute');
                $response = ResponseFactory::create()->createResponse();
                $response->getBody()->write(implode(', ', $attribute) . ', ');
                $response->getBody()->write('Early, ');

                return $response;
            }
        };
    }
}
