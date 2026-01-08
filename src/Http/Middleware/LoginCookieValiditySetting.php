<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\Current;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LoginCookieValiditySetting implements MiddlewareInterface
{
    public function __construct(private Config $config, private UserPreferencesHandler $userPreferencesHandler)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->config->hasSelectedServer()) {
            $this->userPreferencesHandler->getLoginCookieValidityFromCache(Current::$server);
        }

        return $handler->handle($request);
    }
}
