<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\ResponseRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ResponseRendererLoading implements MiddlewareInterface
{
    public function __construct(private Config $config, private ResponseRenderer $responseRenderer)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->config->hasSelectedServer()) {
            $this->responseRenderer->getHeader()->disableMenuAndConsole();
            $this->responseRenderer->setMinimalFooter();
        }

        return $handler->handle($request);
    }
}
