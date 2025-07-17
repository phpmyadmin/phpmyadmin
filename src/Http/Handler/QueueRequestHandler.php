<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Handler;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_shift;

final class QueueRequestHandler implements RequestHandlerInterface
{
    /** @psalm-var list<class-string<MiddlewareInterface>> */
    private array $middleware = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RequestHandlerInterface $fallbackHandler,
    ) {
    }

    /** @param class-string<MiddlewareInterface> $middleware */
    public function add(string $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middleware === []) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = $this->container->get(array_shift($this->middleware));

        return $middleware->process($request, $this);
    }
}
