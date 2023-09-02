<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class ResponseRendererLoading implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax($request->isAjax());
        if (! $this->config->hasSelectedServer()) {
            $responseRenderer->getHeader()->disableMenuAndConsole();
            $responseRenderer->setMinimalFooter();
        }

        return $handler->handle($request);
    }
}
