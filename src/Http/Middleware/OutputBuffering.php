<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

use function is_string;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;

final class OutputBuffering implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            ob_start();
            $response = $handler->handle($request);
            $output = ob_get_clean();
        } catch (Throwable $throwable) {
            ob_end_clean();

            throw $throwable;
        }

        $body = $response->getBody();
        if (is_string($output) && $output !== '' && $body->isWritable()) {
            $body->write($output);
        }

        return $response;
    }
}
