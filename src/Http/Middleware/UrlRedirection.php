<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UrlRedirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_string;

final class UrlRedirection implements MiddlewareInterface
{
    public function __construct(private readonly Config $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('route') !== '/url') {
            return $handler->handle($request);
        }

        $container = ContainerBuilder::getContainer();
        /** @var ThemeManager $themeManager */
        $themeManager = $container->get(ThemeManager::class);
        $this->config->loadUserPreferences($themeManager, true);

        return UrlRedirector::redirect($this->getUrlParam($request->getQueryParams()['url'] ?? null));
    }

    private function getUrlParam(mixed $url): string
    {
        return is_string($url) ? $url : '';
    }
}
