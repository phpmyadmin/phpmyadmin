<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function assert;

final class EncryptedQueryParamsHandling implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        assert($request instanceof ServerRequest);
        $request = Core::populateRequestWithEncryptedQueryParams($request);

        return $handler->handle($request);
    }
}
