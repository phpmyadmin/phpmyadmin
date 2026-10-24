<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Theme\ThemeManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class LanguageAndThemeCookieSaving implements MiddlewareInterface
{
    public function __construct(private Config $config, private ThemeManager $themeManager)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->config->setCookie('pma_lang', Current::$lang);
        $this->themeManager->setThemeCookie();

        return $handler->handle($request);
    }
}
