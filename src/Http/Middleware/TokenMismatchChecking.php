<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function __;
use function assert;

final class TokenMismatchChecking implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        /**
         * There is no point in even attempting to process
         * an ajax request if there is a token mismatch
         */
        if ($request->isAjax() && $request->isPost() && $GLOBALS['token_mismatch']) {
            $responseRenderer = ResponseRenderer::getInstance();
            $responseRenderer->setRequestStatus(false);
            $responseRenderer->addJSON('message', Message::error(__('Error: Token mismatch')));

            return $responseRenderer->response();
        }

        return $handler->handle($request);
    }
}
