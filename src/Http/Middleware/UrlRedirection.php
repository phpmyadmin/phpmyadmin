<?php

declare(strict_types=1);

namespace PhpMyAdmin\Http\Middleware;

use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\UrlRedirector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UrlRedirection implements MiddlewareInterface
{
    public function __construct(
        private Template $template,
        private ResponseFactory $responseFactory,
        private UserPreferencesHandler $userPreferencesHandler,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('route') !== '/url') {
            return $handler->handle($request);
        }

        $this->userPreferencesHandler->loadUserPreferences(true);

        $urlRedirector = new UrlRedirector(ResponseRenderer::getInstance(), $this->template, $this->responseFactory);

        return $urlRedirector->redirect($request->getQueryParams()['url'] ?? null);
    }
}
