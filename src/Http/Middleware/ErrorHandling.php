<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Error\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function restore_error_handler;
use function restore_exception_handler;
use function set_error_handler;
use function set_exception_handler;

final class ErrorHandling implements MiddlewareInterface
{
    public function __construct(private readonly ErrorHandler $errorHandler)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        set_exception_handler($this->errorHandler->handleException(...));
        set_error_handler($this->errorHandler->handleError(...));

        $response = $handler->handle($request);

        restore_error_handler();
        restore_exception_handler();

        return $response;
    }
}
