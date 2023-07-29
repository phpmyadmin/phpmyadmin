<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_shift;

final class QueueRequestHandler implements RequestHandlerInterface
{
    /** @psalm-var list<MiddlewareInterface> */
    private array $middleware = [];

    public function __construct(private readonly RequestHandlerInterface $fallbackHandler)
    {
    }

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middleware === []) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = array_shift($this->middleware);

        return $middleware->process($request, $this);
    }
}
