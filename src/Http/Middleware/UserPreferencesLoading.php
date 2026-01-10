<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config\UserPreferencesHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UserPreferencesLoading implements MiddlewareInterface
{
    public function __construct(private UserPreferencesHandler $userPreferencesHandler)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->userPreferencesHandler->loadUserPreferences();

        return $handler->handle($request);
    }
}
