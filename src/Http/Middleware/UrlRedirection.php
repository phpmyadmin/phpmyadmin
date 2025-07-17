<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UrlRedirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UrlRedirection implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly Template $template,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('route') !== '/url') {
            return $handler->handle($request);
        }

        $container = ContainerBuilder::getContainer();
        $themeManager = $container->get(ThemeManager::class);
        $this->config->loadUserPreferences($themeManager, true);

        $urlRedirector = new UrlRedirector(ResponseRenderer::getInstance(), $this->template, $this->responseFactory);

        return $urlRedirector->redirect($request->getQueryParams()['url'] ?? null);
    }
}
