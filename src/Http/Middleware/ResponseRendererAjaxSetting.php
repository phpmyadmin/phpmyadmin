<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

/**
 * Must run before any middleware that renders a response through the ResponseRenderer,
 * so that AJAX requests get a JSON response.
 */
final readonly class ResponseRendererAjaxSetting implements MiddlewareInterface
{
    public function __construct(private ResponseRenderer $responseRenderer)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $this->responseRenderer->setAjax($request->isAjax());

        return $handler->handle($request);
    }
}
