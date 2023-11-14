<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Core;
use PhpMyAdmin\Theme\ThemeManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ThemeInitialization implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var ThemeManager $themeManager */
        $themeManager = Core::getContainerBuilder()->get(ThemeManager::class);
        $themeManager->initializeTheme();

        return $handler->handle($request);
    }
}
